# Redis Caching System for Quiz Application

This comprehensive Redis caching implementation provides high-performance caching for the quiz application with intelligent TTL strategies, monitoring, and cache management.

## 🚀 Features

### Core Caching Services
- **QuizCacheService**: Quiz sessions, questions, answers with adaptive TTL
- **AnalyticsCacheService**: Performance metrics, user analytics, dashboard data
- **UserCacheService**: User profiles, preferences, sessions with role-based TTL
- **LeaderboardCacheService**: Rankings and competitive data with real-time optimization

### Advanced Features
- **Multi-Database Redis Setup**: 9 separate Redis databases for different purposes
- **Intelligent TTL Strategies**: Context-aware cache expiration (1min to 24hrs)
- **Event-Driven Invalidation**: Automatic cache invalidation on domain events
- **Performance Monitoring**: Real-time metrics and health monitoring
- **Cache Warming**: Proactive cache population with smart strategies
- **Connection Pooling**: Redis cluster and sentinel support
- **Comprehensive Metrics**: Hit ratios, latency tracking, error monitoring

## 📋 Cache Strategy

### TTL Configuration
```php
// Quiz Sessions: 15-30 minutes (active sessions)
QUIZ_SESSION_TTL = 1800; // 30 minutes

// Questions/Answers: 1 hour (relatively static)
QUIZ_QUESTION_TTL = 3600; // 1 hour

// User Analytics: 5-10 minutes (frequently updated)
USER_ANALYTICS_TTL = 600; // 10 minutes

// Leaderboards: 1-2 minutes (competitive data)
LEADERBOARD_TTL = 120; // 2 minutes

// System Config: 24 hours (rarely changes)
CONFIG_TTL = 86400; // 24 hours
```

### Redis Database Allocation
- **DB 0**: Main application cache
- **DB 1**: Quiz sessions
- **DB 2**: Questions and answers
- **DB 3**: User analytics
- **DB 4**: Leaderboards
- **DB 5**: System configuration
- **DB 6**: Real-time metrics
- **DB 7**: Fragment cache
- **DB 8**: Query result cache

## ⚙️ Configuration

### Environment Variables
```bash
# Basic Redis Configuration
REDIS_HOST=localhost
REDIS_PORT=6379
REDIS_PASSWORD=
REDIS_CLUSTER_ENABLED=false
REDIS_SENTINEL_ENABLED=false

# Database-specific URLs
REDIS_SESSION_URL=redis://localhost:6379/1
REDIS_QUESTIONS_URL=redis://localhost:6379/2
REDIS_ANALYTICS_URL=redis://localhost:6379/3
# ... etc
```

### Symfony Configuration
The caching system is configured in:
- `config/packages/redis.yaml` - Redis connection and pools
- `config/services/cache.yaml` - Service definitions
- `config/packages/cache.yaml` - Updated with Redis pools

## 🛠️ Usage Examples

### Basic Cache Operations

```php
// Inject cache services
public function __construct(
    private QuizCacheService $quizCache,
    private AnalyticsCacheService $analyticsCache,
    private UserCacheService $userCache,
    private LeaderboardCacheService $leaderboardCache
) {}

// Cache quiz session
$sessionData = ['user_id' => 123, 'questions' => [...], 'progress' => 75];
$this->quizCache->cacheQuizSession('session_123', $sessionData, $isActive = true);

// Retrieve quiz session
$session = $this->quizCache->getQuizSession('session_123');

// Cache user analytics with activity-based TTL
$analyticsData = ['scores' => [...], 'activity_level' => 'high'];
$this->analyticsCache->cacheUserAnalytics('user_123', $analyticsData, $isRealtime = true);

// Cache leaderboard with competitive context
$leaderboardData = [['user_id' => 123, 'score' => 950], ...];
$context = ['competitive' => true, 'live_competition' => true];
$this->leaderboardCache->cacheLeaderboard('global', $leaderboardData, $context);
```

### Batch Operations

```php
// Batch cache multiple questions
$questions = [
    'q1' => ['content' => 'What is...', 'answers' => [...]],
    'q2' => ['content' => 'How do...', 'answers' => [...]]
];
$results = $this->quizCache->cacheQuestionsBatch($questions);

// Batch retrieve leaderboards
$leaderboards = $this->leaderboardCache->getLeaderboardsBatch(['global', 'weekly', 'category_php']);
```

### Cache Invalidation

```php
// Invalidate user-specific caches
$this->userCache->invalidateUserCache('user_123');

// Invalidate by tags
$this->cacheInvalidationManager->invalidateByTags(['quiz', 'user:123'], 'User completed quiz');

// Bulk invalidation
$operations = [
    ['type' => 'quiz', 'criteria' => ['user_id' => '123']],
    ['type' => 'analytics', 'criteria' => ['user_id' => '123']]
];
$results = $this->cacheInvalidationManager->bulkInvalidate($operations);
```

## 🖥️ Console Commands

### Cache Monitoring
```bash
# Single health check
php bin/console cache:monitor

# Watch mode (continuous monitoring)
php bin/console cache:monitor --watch --interval=5

# JSON output
php bin/console cache:monitor --format=json
```

### Cache Warmup
```bash
# Warm all caches
php bin/console cache:warmup:quiz all

# Smart warmup based on usage patterns
php bin/console cache:warmup:quiz --smart --priority=critical

# Warm specific cache types
php bin/console cache:warmup:quiz quiz
php bin/console cache:warmup:quiz analytics
php bin/console cache:warmup:quiz user
php bin/console cache:warmup:quiz leaderboard

# Dry run to see what would be warmed
php bin/console cache:warmup:quiz all --dry-run
```

### Cache Invalidation
```bash
# Invalidate all caches
php bin/console cache:invalidate:quiz all --force

# Invalidate specific cache type
php bin/console cache:invalidate:quiz quiz --reason="New questions added"

# Invalidate user-specific caches
php bin/console cache:invalidate:quiz user --user-id=123

# Invalidate by tags
php bin/console cache:invalidate:quiz tags --tags=quiz --tags=analytics

# Interactive confirmation (default)
php bin/console cache:invalidate:quiz quiz
```

## 📊 Monitoring & Metrics

### Performance Dashboard
```php
$dashboard = $this->cacheMonitoringService->getPerformanceDashboard();

// Returns:
// - Hit ratios by cache type
// - Latency metrics
// - Error rates
// - Top performing caches
// - Underperforming caches
// - Recent activity
// - Performance recommendations
```

### Health Checks
```php
$health = $this->cacheMonitoringService->performHealthCheck();

// Checks:
// - Redis connection health
// - Cache performance metrics
// - Memory usage
// - Error rates
// - Overall system health
```

### Performance Reports
```php
$report = $this->cacheMonitoringService->generatePerformanceReport(
    new \DateTime('-24 hours'),
    new \DateTime()
);

// Includes:
// - Period summary
// - Cache type analysis
// - Performance trends
// - Incidents and issues
// - Optimization recommendations
```

## 🔧 Event-Driven Cache Management

The system automatically handles cache invalidation based on domain events:

```php
// Events that trigger cache invalidation:
'quiz.session.started' => invalidates user quiz cache
'quiz.session.completed' => invalidates user, analytics, leaderboard caches
'user.profile.updated' => invalidates user cache, warms new data
'leaderboard.position.changed' => updates specific position cache
'competition.started' => invalidates competitive caches, enables real-time
```

## ⚡ Performance Optimizations

### Intelligent TTL Strategies
- **Activity-based TTL**: More active users get shorter TTL for fresher data
- **Role-based TTL**: Admins get shorter TTL, regular users longer TTL
- **Competition-aware TTL**: Live competitions get very short TTL
- **Content-based TTL**: Questions with answers get longer TTL

### Connection Management
- **Connection pooling** with health checks
- **Automatic failover** for unhealthy connections
- **Cluster support** for horizontal scaling
- **Sentinel support** for high availability

### Memory Optimization
- **Namespace prefixing** to avoid key collisions
- **LRU eviction policy** for memory management
- **Compression** for large data sets
- **Selective caching** based on data size and access patterns

## 🚨 Monitoring & Alerting

### Key Metrics Tracked
- **Hit Ratio**: Target >80% for optimal performance
- **Latency**: Target <20ms for cache operations
- **Error Rate**: Target <1% for reliability
- **Memory Usage**: Monitor for optimal Redis sizing
- **Connection Health**: Track connection failures and recovery

### Performance Recommendations
The system automatically generates recommendations:
- TTL optimization suggestions
- Memory usage optimization
- Performance tuning recommendations
- Infrastructure scaling suggestions

### Alert Thresholds
- **Critical**: Hit ratio <30%, latency >100ms, error rate >5%
- **Warning**: Hit ratio <50%, latency >50ms, error rate >1%
- **Info**: Performance optimization opportunities

## 🔄 Cache Warming Strategies

### Smart Warming
- **Priority-based**: Critical → Important → Nice-to-have
- **Context-aware**: Time of day, user activity, competitions
- **Pattern-based**: Historical usage patterns
- **Resource-conscious**: Available memory and CPU

### Warming Scenarios
- **Application startup**: Pre-populate critical caches
- **Competition events**: Warm competitive data
- **Peak hours**: Pre-warm frequently accessed data
- **User login**: Warm user-specific data
- **Content updates**: Re-warm affected caches

## 🔒 Security & Best Practices

### Security
- **Password-protected Redis** (configurable)
- **Network isolation** for Redis instances
- **Encrypted connections** (TLS support)
- **Access control** through service abstraction

### Best Practices
- **Separation of concerns**: Different services for different data types
- **Graceful degradation**: Application works without cache
- **Monitoring first**: Comprehensive metrics and health checks
- **Documentation**: Clear cache key naming and TTL strategies
- **Testing**: Cache behavior covered in tests

## 📈 Performance Impact

Expected performance improvements:
- **Database load reduction**: 60-80% reduction in database queries
- **Response time improvement**: 2-5x faster page loads
- **Scalability**: Support 10x more concurrent users
- **Real-time features**: Sub-second leaderboard updates
- **Analytics performance**: Near-instant dashboard loading

## 🛡️ Fault Tolerance

### Failure Handling
- **Cache miss fallback**: Automatic database fallback
- **Connection retry**: Automatic reconnection with backoff
- **Health monitoring**: Continuous connection health checks
- **Circuit breaker**: Prevent cascade failures
- **Graceful degradation**: Application remains functional

### Data Consistency
- **Event-driven invalidation**: Ensures cache consistency
- **Tag-based invalidation**: Selective cache clearing
- **Version-aware caching**: Prevents stale data issues
- **Atomic operations**: Consistent cache updates

This Redis caching system provides a robust, scalable, and high-performance foundation for the quiz application, with comprehensive monitoring, intelligent optimization, and production-ready reliability.