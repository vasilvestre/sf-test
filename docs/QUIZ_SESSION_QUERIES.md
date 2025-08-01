# Quiz Session Query Implementation

This document describes the comprehensive query implementation for the Quiz domain, following CQRS patterns with optimized read models and advanced analytics.

## Overview

The quiz session query system provides four main query types:

1. **GetActiveQuizSessionQuery** - Retrieves user's current active session
2. **GetQuizSessionDetailsQuery** - Provides detailed session information with progress
3. **GetQuizSessionAnalyticsQuery** - Delivers performance analytics and learning insights
4. **GetAdaptiveLearningDataQuery** - Generates personalized recommendations

## Architecture

### Query Classes
- **Location**: `src/Quiz/Application/Query/`
- **Purpose**: Define query parameters and constraints
- **Pattern**: Immutable data objects implementing `QueryInterface`

### Query Handlers
- **Location**: `src/Quiz/Application/Handler/`
- **Purpose**: Execute queries and return optimized read models
- **Pattern**: Single responsibility handlers implementing `QueryHandlerInterface`

### Read Models
- **Location**: `src/Quiz/Application/ReadModel/`
- **Purpose**: Optimized data transfer objects for UI consumption
- **Pattern**: Immutable DTOs with helper methods and array conversion

## Usage Examples

### 1. Getting Active Session

```php
// Create query
$query = new GetActiveQuizSessionQuery(
    userId: 123,
    includeQuestions: true,
    includeProgress: true,
    includeAdaptiveData: false
);

// Execute via query bus
$activeSession = $queryBus->ask($query);

if ($activeSession) {
    echo "Current progress: {$activeSession->progress}%";
    echo "Questions remaining: " . ($activeSession->totalQuestions - $activeSession->currentQuestionIndex);
    
    if ($activeSession->hasTimedOut()) {
        echo "Session has timed out!";
    }
}
```

### 2. Getting Detailed Session Information

```php
$query = new GetQuizSessionDetailsQuery(
    sessionId: 'session-uuid-123',
    userId: 456,
    includeQuestions: true,
    includeAnswers: true,
    includeProgress: true,
    includeAnalytics: true
);

$response = $queryBus->ask($query);

// Access session data
$session = $response['session'];
$progress = $response['progress'];
$analytics = $response['analytics'];

echo "Accuracy: {$progress->accuracyPercentage}%";
echo "Current streak: {$progress->getCurrentStreak()}";
echo "Weakest category: " . ($progress->getWeakestCategory()['category'] ?? 'None');
```

### 3. Getting Performance Analytics

```php
$query = new GetQuizSessionAnalyticsQuery(
    userId: 789,
    fromDate: new \DateTimeImmutable('-30 days'),
    toDate: new \DateTimeImmutable(),
    includeDetailed: true,
    includeTrends: true,
    granularity: 'daily'
);

$analytics = $queryBus->ask($query);

echo "Overall proficiency: {$analytics->getOverallProficiency()}%";
echo "Learning efficiency: {$analytics->getLearningEfficiency()} knowledge/hour";
echo "Optimal difficulty: {$analytics->getOptimalDifficulty()}";

// Get personalized recommendations
foreach ($analytics->getPersonalizedRecommendations() as $recommendation) {
    echo "Action: {$recommendation['action']} (Priority: {$recommendation['priority']})";
}
```

### 4. Getting Adaptive Learning Recommendations

```php
$query = new GetAdaptiveLearningDataQuery(
    userId: 101,
    categoryId: 5,
    recommendationCount: 10,
    recommendationStrategy: 'adaptive',
    includeExplanations: true,
    includeAlternatives: true,
    excludeQuestionIds: ['q1', 'q2', 'q3']
);

$recommendations = $queryBus->ask($query);

echo "Strategy: {$recommendations->getStrategyDescription()}";
echo "Confidence: {$recommendations->getConfidenceDescription()}";

// Get next question
$nextQuestion = $recommendations->getNextQuestion();
if ($nextQuestion) {
    echo "Next question: {$nextQuestion['content']}";
    echo "Difficulty: {$nextQuestion['difficulty']}";
}

// Get explanation
$explanation = $recommendations->getExplanation();
echo "Why this recommendation: {$explanation['reasoning']}";
```

## Read Models Details

### QuizSessionReadModel

Provides optimized session data for UI consumption:

- **Real-time data**: Current progress, remaining time, timeout status
- **Question data**: Current question with answers (if requested)
- **Metadata**: Session configuration, adaptive learning settings
- **Helper methods**: `getRemainingTime()`, `hasTimedOut()`, `getCurrentQuestion()`

### QuizProgressReadModel

Delivers detailed progress tracking:

- **Progress metrics**: Completion percentage, accuracy, timing
- **Category analysis**: Performance breakdown by topic
- **Difficulty analysis**: Performance across difficulty levels
- **Streak tracking**: Current and maximum correct answer streaks
- **Trend analysis**: Recent performance patterns

### LearningAnalyticsReadModel

Provides comprehensive learning insights:

- **Proficiency analysis**: Overall and category-specific mastery levels
- **Learning patterns**: Performance trends, peak times, consistency
- **Knowledge gaps**: Areas needing improvement with severity ratings
- **Recommendations**: Personalized action items with impact estimates
- **Predictions**: Expected performance and improvement forecasts

### QuestionRecommendationReadModel

Delivers intelligent question suggestions:

- **Recommendation engine**: ML-driven question selection
- **Strategy explanation**: Why these questions were recommended
- **Confidence scoring**: Algorithm confidence in recommendations
- **Alternative options**: Fallback questions if primary choices unavailable
- **Distribution analysis**: Difficulty and category spread

## Caching Strategy

### Cache Levels

1. **Session Cache** (5 minutes)
   - Active session data
   - Question recommendations
   - Adaptive learning data

2. **Progress Cache** (3 minutes)
   - Detailed session information
   - Progress analytics

3. **Analytics Cache** (10 minutes)
   - Learning analytics
   - Performance insights
   - Long-term trends

### Cache Keys

Cache keys are automatically generated based on query parameters:

```php
// Example cache keys
"active_quiz_session_123"
"quiz_session_details_session-uuid-123"
"quiz_analytics_456_all_all_all_session_2024-01-01_2024-01-31"
"adaptive_learning_789_none_5_adaptive_10_medium_abc123"
```

## Performance Optimizations

### Database Queries

- **Read-optimized**: Separate queries for different data needs
- **Selective loading**: Only load requested data (questions, answers, analytics)
- **Batch processing**: Efficient aggregation for analytics
- **Index optimization**: Support for common query patterns

### Memory Usage

- **Lazy loading**: Data loaded only when requested
- **Streaming**: Large datasets processed in chunks
- **Object pooling**: Reuse of expensive objects
- **Garbage collection**: Explicit cleanup of large datasets

### Algorithm Efficiency

- **Recommendation caching**: Pre-computed suggestions
- **Incremental analysis**: Update only changed data
- **Parallel processing**: Multiple analytics computed simultaneously
- **Smart sampling**: Statistical sampling for large datasets

## Error Handling

### Exception Hierarchy

```php
// Domain exceptions
QuizSessionNotFoundException
InvalidQuizDataException
QuestionNotFoundException

// Application exceptions
InsufficientDataException
AlgorithmTimeoutException
CacheUnavailableException
```

### Graceful Degradation

- **Cache misses**: Fall back to database queries
- **Algorithm failures**: Return basic recommendations
- **Data unavailable**: Provide estimated values
- **Timeout handling**: Return partial results

## Testing Strategy

### Unit Tests

- **Query validation**: Parameter validation and constraints
- **Handler logic**: Business logic and data transformation
- **Read model creation**: Object construction and helper methods
- **Cache behavior**: Caching and invalidation logic

### Integration Tests

- **Repository interaction**: Database query execution
- **Cache integration**: Redis/Memcached interaction
- **Algorithm performance**: Recommendation quality
- **End-to-end flows**: Complete query execution

### Performance Tests

- **Load testing**: High-concurrency query execution
- **Memory profiling**: Memory usage under load
- **Cache hit rates**: Caching effectiveness
- **Response times**: Query execution speed

## Configuration

### Service Configuration

Add to `config/services/quiz.yaml`:

```yaml
services:
    # Query Handlers
    App\Quiz\Application\Handler\GetActiveQuizSessionQueryHandler:
        arguments:
            $cache: '@cache.app'
    
    App\Quiz\Application\Handler\GetQuizSessionDetailsQueryHandler:
        arguments:
            $cache: '@cache.app'
    
    App\Quiz\Application\Handler\GetQuizSessionAnalyticsQueryHandler:
        arguments:
            $cache: '@cache.app'
    
    App\Quiz\Application\Handler\GetAdaptiveLearningDataQueryHandler:
        arguments:
            $cache: '@cache.app'
    
    # Repository Implementation (to be created)
    App\Quiz\Domain\Repository\QuizSessionRepositoryInterface:
        alias: App\Quiz\Infrastructure\Persistence\DoctrineOrmQuizSessionRepository
```

### Cache Configuration

Add to `config/packages/cache.yaml`:

```yaml
framework:
    cache:
        app: cache.adapter.redis
        pools:
            quiz.session.cache:
                adapter: cache.app
                default_lifetime: 300
            quiz.analytics.cache:
                adapter: cache.app
                default_lifetime: 600
```

## Migration Considerations

### Existing Code

1. **Legacy queries**: Gradually replace with new CQRS queries
2. **Data migration**: Map existing data to new read models
3. **API compatibility**: Maintain backward compatibility
4. **Performance impact**: Monitor query performance during migration

### Database Schema

1. **Indexes**: Add indexes for new query patterns
2. **Views**: Create database views for complex analytics
3. **Partitioning**: Consider partitioning for large datasets
4. **Archiving**: Plan for historical data management

## Monitoring and Observability

### Metrics

- **Query performance**: Execution time, cache hit rates
- **Usage patterns**: Most common queries, peak times
- **Error rates**: Exception frequency, timeout occurrences
- **Resource usage**: Memory consumption, CPU usage

### Logging

- **Query execution**: Parameters, execution time, results
- **Cache operations**: Hits, misses, evictions
- **Algorithm performance**: Recommendation quality, confidence scores
- **Error details**: Exception context, stack traces

### Alerting

- **Performance degradation**: Slow queries, high error rates
- **Cache issues**: Low hit rates, connection problems
- **Data quality**: Missing data, inconsistent results
- **Resource limits**: Memory usage, connection pool exhaustion