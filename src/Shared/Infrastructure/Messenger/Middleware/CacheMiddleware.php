<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Messenger\Middleware;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

/**
 * Middleware for caching query results.
 */
final class CacheMiddleware implements MiddlewareInterface
{
    private const DEFAULT_TTL = 3600; // 1 hour

    public function __construct(
        private readonly CacheItemPoolInterface $cache
    ) {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $message = $envelope->getMessage();
        
        // Only cache queries (read operations)
        if (!$this->isQuery($message)) {
            return $stack->next()->handle($envelope, $stack);
        }

        $cacheKey = $this->generateCacheKey($message);
        $cacheItem = $this->cache->getItem($cacheKey);

        if ($cacheItem->isHit()) {
            // Return cached result
            $result = $cacheItem->get();
            return $envelope->with(new HandledStamp($result, 'cache'));
        }

        // Handle the query and cache the result
        $envelope = $stack->next()->handle($envelope, $stack);
        
        $handledStamp = $envelope->last(HandledStamp::class);
        if ($handledStamp) {
            $result = $handledStamp->getResult();
            $cacheItem->set($result);
            $cacheItem->expiresAfter($this->getTtl($message));
            $this->cache->save($cacheItem);
        }

        return $envelope;
    }

    private function isQuery(object $message): bool
    {
        $reflection = new \ReflectionClass($message);
        return str_contains($reflection->getName(), 'Query');
    }

    private function generateCacheKey(object $message): string
    {
        $className = get_class($message);
        $serialized = serialize($message);
        return 'query_' . md5($className . $serialized);
    }

    private function getTtl(object $message): int
    {
        // You can implement custom TTL logic based on query type
        return self::DEFAULT_TTL;
    }
}