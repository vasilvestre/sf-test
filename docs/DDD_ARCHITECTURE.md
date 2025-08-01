# DDD Architecture Documentation

## Overview

This document describes the Domain-Driven Design (DDD) architecture implementation for the Symfony Quiz Application. The application has been restructured from a flat architecture to a proper DDD bounded context architecture.

## Bounded Contexts

### 1. Quiz Domain (`src/Quiz/`)
**Purpose**: Core quiz functionality including questions, categories, answers, and quiz execution.

**Responsibilities**:
- Question and category management
- Quiz execution logic
- Answer validation and scoring
- Quiz result recording

**Key Entities**:
- `Category`: Quiz categories with questions
- `Question`: Individual quiz questions with answers
- `Answer`: Possible answers to questions
- `QuizResult`: Results of completed quizzes

**Key Value Objects**:
- `CategoryName`: Category name with validation
- `DifficultyLevel`: Question difficulty (easy, medium, hard)
- `Score`: Quiz score as percentage with business rules

**Domain Events**:
- `QuizCompleted`: Fired when a quiz is completed
- `QuestionAnsweredIncorrectly`: Fired when a question is answered wrong

### 2. Analytics Domain (`src/Analytics/`)
**Purpose**: Performance tracking, statistics, and failure analysis.

**Responsibilities**:
- Track question failure rates
- Track category failure rates
- Generate performance statistics
- Provide insights for learning improvement

**Key Entities**:
- `QuestionFailure`: Tracks failed questions with counts
- `CategoryFailure`: Tracks failed categories with counts

### 3. User Domain (`src/User/`)
**Purpose**: User management, authentication, and user preferences (future implementation).

**Responsibilities**:
- User registration and authentication
- User profiles and preferences
- User session management
- User progress tracking

### 4. Shared Kernel (`src/Shared/`)
**Purpose**: Common utilities, base classes, and shared abstractions.

**Components**:
- Base entity classes (`AggregateRoot`)
- Value object interfaces and base classes
- Domain event interfaces
- Common value objects (`Id`, `Score`, `Text`)
- CQRS interfaces (`CommandInterface`, `QueryInterface`)

## Architecture Layers

Each bounded context follows the Hexagonal Architecture pattern with three main layers:

### Domain Layer (`Domain/`)
- **Entities**: Aggregate roots containing business logic
- **Value Objects**: Immutable objects representing domain concepts
- **Repository Interfaces**: Contracts for data access
- **Domain Services**: Complex business logic that doesn't belong to entities
- **Domain Events**: Events representing important business occurrences
- **Exceptions**: Domain-specific exceptions

### Application Layer (`Application/`)
- **Commands**: Write operations (CQRS)
- **Queries**: Read operations (CQRS)
- **Handlers**: Command and query handlers
- **Services**: Application services orchestrating use cases

### Infrastructure Layer (`Infrastructure/`)
- **Persistence**: Doctrine repository implementations
- **Messaging**: Event handling and message bus integration
- **Cache**: Caching implementations

### UI Layer (`UI/`)
- **Controllers**: HTTP request handlers
- **Forms**: Symfony form types
- **DTOs**: Data transfer objects for API responses

## Key Design Patterns

### 1. Aggregate Pattern
Each bounded context has aggregate roots that ensure consistency boundaries:
- `Category` aggregate manages questions
- `QuizResult` aggregate manages quiz completion
- `QuestionFailure`/`CategoryFailure` aggregates manage analytics data

### 2. Value Objects
Immutable objects representing domain concepts:
- `CategoryName`: Encapsulates category naming rules
- `DifficultyLevel`: Type-safe difficulty levels
- `Score`: Business rules for scoring

### 3. Domain Events
Events representing important business occurrences:
- Decoupled event handling
- Async processing capabilities
- Audit trail support

### 4. Repository Pattern
Clean separation between domain and persistence:
- Domain repository interfaces
- Infrastructure implementations
- Testable business logic

### 5. CQRS (Command Query Responsibility Segregation)
Separate read and write operations:
- Commands for state changes
- Queries for data retrieval
- Clear separation of concerns

## Configuration

### Service Configuration
- **Domain-specific services**: `config/services/[domain].yaml`
- **Dependency injection**: Automatic registration by namespace
- **Repository binding**: Interface to implementation mapping

### Doctrine Mapping
- **Bounded context mappings**: `config/packages/doctrine/ddd_mappings.yaml`
- **Separate namespaces**: Each domain has its own entity namespace
- **Legacy compatibility**: Existing entities remain functional

### Routing
- **Domain routes**: `config/routes/domain/[domain].yaml`
- **Prefixed by domain**: Each domain has its own URL prefix
- **Legacy routes**: Existing routes remain functional

## Migration Strategy

### Phase 1: Foundation (Current)
- ✅ Create DDD folder structure
- ✅ Implement base classes and interfaces
- ✅ Create domain entities and value objects
- ✅ Set up configuration for DDD structure

### Phase 2: Repository Migration
- Implement infrastructure repositories
- Create adapters for legacy repositories
- Migrate existing queries to new repositories

### Phase 3: Application Layer
- Implement command and query handlers
- Create application services
- Migrate controller logic to application layer

### Phase 4: Domain Events
- Implement event dispatching
- Create event handlers for analytics
- Set up async processing

### Phase 5: Legacy Cleanup
- Remove legacy entities and repositories
- Complete migration of business logic
- Update tests to use new structure

## Benefits

### 1. Clear Domain Boundaries
- Separated concerns by business domain
- Reduced coupling between contexts
- Better code organization

### 2. Testability
- Domain logic independent of infrastructure
- Easy unit testing with interfaces
- Clear separation of concerns

### 3. Maintainability
- Focused, cohesive modules
- Clear dependencies
- Easy to understand and modify

### 4. Scalability
- Independent deployment of contexts
- Team ownership of specific domains
- Technology flexibility per domain

### 5. Business Alignment
- Code structure reflects business domains
- Easier communication with domain experts
- Natural boundaries for feature development

## Development Guidelines

### 1. Domain-First Development
- Start with domain entities and value objects
- Define repository interfaces in domain layer
- Implement infrastructure after domain is stable

### 2. Event-Driven Architecture
- Use domain events for cross-context communication
- Async processing for non-critical operations
- Event sourcing for audit trails

### 3. Test-Driven Development
- Write tests for domain logic first
- Use interfaces for easy mocking
- Test command and query handlers independently

### 4. SOLID Principles
- Single responsibility per class
- Open/closed principle with interfaces
- Dependency inversion with abstractions

## Next Steps

1. **Complete Infrastructure Layer**: Implement all repository interfaces
2. **Application Services**: Create command and query handlers
3. **Event System**: Implement domain event dispatching
4. **Migration Tools**: Create tools to migrate legacy data
5. **Testing Strategy**: Comprehensive test suite for new architecture
6. **Documentation**: User guides and API documentation
7. **Performance Optimization**: Optimize queries and caching
8. **Monitoring**: Add logging and metrics for new structure