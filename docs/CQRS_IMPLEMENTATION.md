# CQRS Implementation Documentation

## Overview

This document describes the complete Command Query Responsibility Segregation (CQRS) implementation for the quiz application. The system uses Symfony Messenger to provide a robust, scalable, and maintainable architecture.

## Architecture Components

### 1. Command Query Responsibility Segregation (CQRS)

The CQRS pattern separates read and write operations into distinct models:

- **Commands**: Handle write operations (state changes)
- **Queries**: Handle read operations (data retrieval)
- **Events**: Represent domain events that occur in the system

### 2. Message Buses

#### Command Bus
- **Purpose**: Handles all write operations
- **Service**: `command.bus`
- **Middleware**: Validation, logging, performance monitoring, transactions, domain events
- **Examples**: User registration, quiz submission, profile updates

#### Query Bus
- **Purpose**: Handles all read operations
- **Service**: `query.bus`
- **Middleware**: Validation, logging, performance monitoring, caching
- **Examples**: User profile retrieval, quiz questions, analytics data

#### Event Bus
- **Purpose**: Handles domain events
- **Service**: `event.bus`
- **Middleware**: Logging
- **Configuration**: `allow_no_handlers` enabled for optional event handling

### 3. Transport Configuration

#### High Priority Transport
- **Queue**: `high_priority`
- **Usage**: User critical operations (registration, login, quiz submission)
- **Retry**: 5 retries with 1.5x multiplier

#### Normal Priority Transport
- **Queue**: `normal_priority`
- **Usage**: Standard operations (profile updates, content creation)
- **Retry**: 3 retries with 2x multiplier

#### Low Priority Transport
- **Queue**: `low_priority`
- **Usage**: Analytics, reporting, background processing
- **Retry**: 2 retries with 3x multiplier

#### Email Transport
- **Queue**: `email_queue`
- **Usage**: All email notifications
- **Retry**: 4 retries with 2x multiplier

## Domain Implementation

### User Domain

#### Commands
- `RegisterUserCommand`: Register a new user
- `VerifyUserEmailCommand`: Verify user email address
- `UpdateUserProfileCommand`: Update user profile information
- `UpdateUserPreferencesCommand`: Update user preferences
- `ChangeUserPasswordCommand`: Change user password
- `EnableTwoFactorCommand`: Enable two-factor authentication
- `CreateStudyPlanCommand`: Create a study plan
- `RecordAchievementCommand`: Record user achievement

#### Queries
- `GetUserByIdQuery`: Get user by ID
- `GetUserByEmailQuery`: Get user by email
- `GetUserProfileQuery`: Get detailed user profile
- `GetUserPreferencesQuery`: Get user preferences
- `GetUserAchievementsQuery`: Get user achievements
- `GetUserProgressQuery`: Get user learning progress
- `GetStudyPlansQuery`: Get user study plans

#### Events
- `UserRegistered`: Fired when user registers
- `UserEmailVerified`: Fired when email is verified
- `UserProfileUpdated`: Fired when profile is updated
- `StudyPlanCreated`: Fired when study plan is created
- `AchievementEarned`: Fired when achievement is earned

### Quiz Domain

#### Commands
- `CreateQuizCommand`: Create a new quiz
- `SubmitQuizAttemptCommand`: Submit quiz attempt with answers
- `CreateQuestionCommand`: Create a new question
- `UpdateQuestionCommand`: Update existing question
- `DeleteQuestionCommand`: Delete a question
- `CreateCategoryCommand`: Create quiz category

#### Queries
- `GetCategoriesQuery`: Get quiz categories
- `GetQuizQuestionsQuery`: Get questions for a quiz
- `GetQuizAttemptsQuery`: Get user's quiz attempts
- `GetQuestionsByTagQuery`: Get questions by tags
- `GetRecommendedQuestionsQuery`: Get personalized question recommendations

#### Events
- `QuizAttemptStarted`: Fired when quiz attempt begins
- `QuizAttemptCompleted`: Fired when quiz attempt finishes
- `QuestionAnswered`: Fired when individual question is answered
- `DifficultyAdjusted`: Fired when difficulty is automatically adjusted
- `PersonalBestAchieved`: Fired when user achieves personal best

### Analytics Domain

#### Commands
- `RecordPerformanceMetricsCommand`: Record user performance data
- `GenerateReportCommand`: Generate analytics reports
- `UpdateLeaderboardCommand`: Update leaderboard standings
- `ProcessLearningAnalyticsCommand`: Process learning analytics

#### Queries
- `GetUserStatisticsQuery`: Get comprehensive user statistics
- `GetCategoryPerformanceQuery`: Get category-specific performance
- `GetLeaderboardQuery`: Get leaderboard data
- `GetLearningAnalyticsQuery`: Get learning analytics insights
- `GetSystemMetricsQuery`: Get system-wide metrics (admin only)

#### Events
- `PerformanceAnalyzed`: Fired when performance is analyzed
- `LearningPathUpdated`: Fired when learning path changes
- `ReportGenerated`: Fired when report is generated

## Middleware Pipeline

### Command Middleware (in order)
1. **ValidationMiddleware**: Input validation and sanitization
2. **LoggingMiddleware**: Command execution logging
3. **PerformanceMiddleware**: Execution time monitoring
4. **TransactionMiddleware**: Database transaction management
5. **DomainEventMiddleware**: Domain event dispatching

### Query Middleware (in order)
1. **ValidationMiddleware**: Input validation
2. **LoggingMiddleware**: Query execution logging
3. **PerformanceMiddleware**: Performance monitoring
4. **CacheMiddleware**: Result caching

### Event Middleware
1. **LoggingMiddleware**: Event processing logging

## Usage Examples

### Using Command Bus

```php
use App\Shared\Application\Service\CommandBus;
use App\User\Application\Command\RegisterUserCommand;

public function registerUser(CommandBus $commandBus): void
{
    $command = new RegisterUserCommand(
        email: 'user@example.com',
        username: 'newuser',
        plainPassword: 'password123',
        role: 'ROLE_STUDENT'
    );
    
    $userId = $commandBus->dispatch($command);
}
```

### Using Query Bus

```php
use App\Shared\Application\Service\QueryBus;
use App\User\Application\Query\GetUserProfileQuery;

public function getUserProfile(QueryBus $queryBus, int $userId): array
{
    $query = new GetUserProfileQuery(
        userId: $userId,
        includeAchievements: true,
        includePreferences: true
    );
    
    return $queryBus->ask($query);
}
```

### Domain Events

```php
// In your domain entity
$user = new User($email, $username, $hashedPassword);
$user->recordEvent(new UserRegistered(
    $user->getId(),
    $user->getEmail(),
    $user->getUsername(),
    $user->getRole(),
    new \DateTimeImmutable()
));

// Events are automatically dispatched after successful command handling
```

## Performance Features

### Caching
- Query results are automatically cached based on query parameters
- Cache TTL can be configured per query type
- Cache invalidation on related data changes

### Async Processing
- Commands routed to appropriate priority queues
- Non-critical operations processed asynchronously
- Email sending handled in background

### Monitoring
- Execution time logging for all commands and queries
- Slow operation warnings (>1s for commands, >500ms for queries)
- Memory usage tracking
- Performance metrics collection

## Error Handling

### Retry Strategies
- Automatic retry with exponential backoff
- Different retry policies per transport
- Dead letter queue for failed messages

### Transaction Management
- Commands wrapped in database transactions
- Automatic rollback on errors
- Consistent data state guaranteed

### Logging
- Comprehensive error logging
- Context information included
- Performance metrics captured

## Testing

### Unit Tests
- Command and query handlers
- Middleware functionality
- Domain event handling
- Validation logic

### Integration Tests
- End-to-end command/query flows
- Event dispatching verification
- Database transaction testing
- Cache behavior validation

### Performance Tests
- Command execution benchmarks
- Query response time testing
- Async processing throughput
- System load testing

## Configuration

### Environment Variables
```bash
# Transport DSNs
MESSENGER_TRANSPORT_HIGH_DSN=doctrine://default?queue_name=high_priority&auto_setup=0
MESSENGER_TRANSPORT_NORMAL_DSN=doctrine://default?queue_name=normal_priority&auto_setup=0
MESSENGER_TRANSPORT_LOW_DSN=doctrine://default?queue_name=low_priority&auto_setup=0
MESSENGER_TRANSPORT_EMAIL_DSN=doctrine://default?queue_name=email_queue&auto_setup=0

# Email configuration
DEFAULT_FROM_EMAIL=noreply@quiz-app.com
```

### Service Registration
All handlers are automatically registered via service autoconfiguration:
- Command handlers tagged with `messenger.message_handler` for `command.bus`
- Query handlers tagged with `messenger.message_handler` for `query.bus`
- Event handlers tagged with `messenger.message_handler` for `event.bus`

## Console Commands

### Message Processing
```bash
# Consume high priority messages
php bin/console messenger:consume high_priority

# Consume all transports
php bin/console messenger:consume

# Show failed messages
php bin/console messenger:failed:show

# Retry failed messages
php bin/console messenger:failed:retry
```

### Statistics
```bash
# Show queue statistics
php bin/console messenger:stats

# Monitor queue sizes
watch -n 5 'php bin/console messenger:stats'
```

## Benefits

### Scalability
- Independent scaling of read and write operations
- Async processing prevents blocking operations
- Queue-based architecture handles traffic spikes

### Maintainability
- Clear separation of concerns
- Single responsibility principle enforced
- Easy to add new features without affecting existing code

### Performance
- Optimized read operations with caching
- Background processing for heavy operations
- Efficient resource utilization

### Reliability
- Transaction management ensures data consistency
- Retry mechanisms handle temporary failures
- Comprehensive error logging and monitoring

### Testability
- Isolated command and query handlers
- Mockable dependencies
- Clear interfaces for testing

## Migration Strategy

The CQRS implementation is designed to work alongside existing code:

1. **Gradual Migration**: Legacy controllers can be updated incrementally
2. **Backward Compatibility**: Existing repositories and services remain functional
3. **Progressive Enhancement**: New features built with CQRS from the start
4. **Dual Mode**: Both old and new patterns can coexist during transition

This CQRS implementation provides a solid foundation for building scalable, maintainable, and high-performance applications while maintaining flexibility for future enhancements.