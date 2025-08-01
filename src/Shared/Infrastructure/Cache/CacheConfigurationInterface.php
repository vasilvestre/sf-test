<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Cache;

/**
 * Configuration interface for cache TTL strategies.
 * Defines cache lifetimes for different data types.
 */
interface CacheConfigurationInterface
{
    // Quiz-related cache TTLs
    public const QUIZ_SESSION_TTL = 1800; // 30 minutes
    public const QUIZ_QUESTION_TTL = 3600; // 1 hour
    public const QUIZ_ANSWER_TTL = 3600; // 1 hour
    public const QUIZ_RESULTS_TTL = 86400; // 24 hours
    
    // Analytics cache TTLs
    public const USER_ANALYTICS_TTL = 600; // 10 minutes
    public const ADMIN_ANALYTICS_TTL = 300; // 5 minutes
    public const LEADERBOARD_TTL = 120; // 2 minutes
    public const REALTIME_METRICS_TTL = 30; // 30 seconds
    
    // System cache TTLs
    public const CONFIG_TTL = 86400; // 24 hours
    public const FRAGMENT_TTL = 900; // 15 minutes
    public const QUERY_RESULT_TTL = 1800; // 30 minutes
    
    // User-related cache TTLs
    public const USER_PROFILE_TTL = 3600; // 1 hour
    public const USER_PREFERENCES_TTL = 7200; // 2 hours
    public const USER_SESSIONS_TTL = 1800; // 30 minutes
    
    // Cache key prefixes
    public const QUIZ_SESSION_PREFIX = 'quiz:session:';
    public const QUIZ_QUESTION_PREFIX = 'quiz:question:';
    public const USER_ANALYTICS_PREFIX = 'analytics:user:';
    public const LEADERBOARD_PREFIX = 'analytics:leaderboard:';
    public const USER_PROFILE_PREFIX = 'user:profile:';
    public const QUERY_RESULT_PREFIX = 'query:result:';
    
    // Cache tags for selective invalidation
    public const TAG_QUIZ = 'quiz';
    public const TAG_USER = 'user';
    public const TAG_ANALYTICS = 'analytics';
    public const TAG_LEADERBOARD = 'leaderboard';
    public const TAG_SESSION = 'session';
    public const TAG_QUESTION = 'question';
    public const TAG_REALTIME = 'realtime';
}