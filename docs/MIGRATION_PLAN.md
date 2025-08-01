# Migration Plan: Legacy to DDD Architecture

## Overview

This document outlines the step-by-step migration plan from the current flat Symfony architecture to the new Domain-Driven Design (DDD) architecture.

## Current State Analysis

### Existing Components
- **Controllers**: `QuizController`, `HomeController`
- **Entities**: `Category`, `Question`, `Answer`, `QuizResult`, `QuestionFailure`, `CategoryFailure`
- **Repositories**: Doctrine repositories for all entities
- **Services**: `QuizLoader` service
- **Templates**: Twig templates in `templates/quiz/`

### Current Dependencies
```
QuizController -> CategoryRepository
               -> QuestionRepository
               -> QuizResultRepository
               -> QuestionFailureRepository
               -> CategoryFailureRepository
               -> QuizLoader
               -> EntityManager
               -> ChartBuilder
```

## Migration Phases

### Phase 1: Foundation Setup ✅ (Completed)

**Goal**: Establish DDD structure without breaking existing functionality.

**Completed Tasks**:
- ✅ Created bounded context folder structure
- ✅ Implemented shared kernel (base classes, interfaces)
- ✅ Created domain entities with proper encapsulation
- ✅ Defined value objects and domain events
- ✅ Set up domain-specific service configuration
- ✅ Created repository interfaces
- ✅ Updated routing and Doctrine configuration

**Status**: **COMPLETE**

### Phase 2: Infrastructure Implementation

**Goal**: Implement infrastructure layer while maintaining legacy compatibility.

**Tasks**:
1. **Repository Adapters** (Week 1)
   - Create adapters wrapping legacy repositories
   - Implement domain repository interfaces
   - Test compatibility with existing queries

2. **Doctrine Mapping Migration** (Week 1)
   - Create mapping for new domain entities
   - Ensure legacy entities continue to work
   - Test database compatibility

3. **Event Infrastructure** (Week 2)
   - Implement domain event dispatcher
   - Create event handlers for analytics
   - Set up async processing (optional)

**Files to Create**:
```
src/Quiz/Infrastructure/Persistence/
├── DoctrineOrmCategoryRepository.php
├── DoctrineOrmQuestionRepository.php
├── DoctrineOrmQuizResultRepository.php
└── LegacyRepositoryAdapter.php

src/Analytics/Infrastructure/Persistence/
├── DoctrineOrmQuestionFailureRepository.php
└── DoctrineOrmCategoryFailureRepository.php

src/Shared/Infrastructure/Persistence/
└── DoctrineEventDispatcher.php
```

### Phase 3: Application Layer Implementation

**Goal**: Create application services and CQRS handlers.

**Tasks**:
1. **Command Handlers** (Week 3)
   - `CreateCategoryCommandHandler`
   - `SubmitQuizCommandHandler`
   - `LoadQuizzesCommandHandler`

2. **Query Handlers** (Week 3-4)
   - `GetCategoriesQueryHandler`
   - `GetQuizQuestionsQueryHandler`
   - `GetQuizHistoryQueryHandler`
   - `GetQuizStatisticsQueryHandler`

3. **Application Services** (Week 4)
   - `QuizExecutionService`
   - `QuizStatisticsService`
   - `QuizLoaderService`

**Files to Create**:
```
src/Quiz/Application/Handler/
├── CreateCategoryCommandHandler.php
├── SubmitQuizCommandHandler.php
├── GetCategoriesQueryHandler.php
├── GetQuizQuestionsQueryHandler.php
└── GetQuizStatisticsQueryHandler.php

src/Quiz/Application/Service/
├── QuizExecutionService.php
└── QuizStatisticsService.php

src/Analytics/Application/Handler/
├── TrackQuestionFailureCommandHandler.php
└── GetFailureStatisticsQueryHandler.php
```

### Phase 4: Controller Migration

**Goal**: Migrate controllers to use new application layer.

**Tasks**:
1. **Create New Controllers** (Week 5)
   - `Quiz\UI\QuizController`
   - `Analytics\UI\AnalyticsController`

2. **Migrate Existing Logic** (Week 5-6)
   - Move business logic to application services
   - Update controllers to use command/query buses
   - Maintain backward compatibility

3. **Update Templates** (Week 6)
   - Update Twig templates to work with new data structures
   - Ensure all features continue to work

**Migration Strategy for QuizController**:
```php
// Before (legacy)
public function submit(Request $request): Response
{
    $answers = $request->request->all('answers');
    // ... complex business logic ...
    return $this->render('quiz/result.html.twig', $data);
}

// After (DDD)
public function submit(Request $request, CommandBus $commandBus): Response
{
    $command = new SubmitQuizCommand(
        $request->request->all('answers'),
        $request->request->get('category_id')
    );
    
    $result = $commandBus->dispatch($command);
    return $this->render('quiz/result.html.twig', ['result' => $result]);
}
```

### Phase 5: Domain Events Integration

**Goal**: Implement full event-driven architecture.

**Tasks**:
1. **Event Handlers** (Week 7)
   - `QuizCompletedEventHandler` (analytics)
   - `QuestionAnsweredIncorrectlyEventHandler` (failure tracking)

2. **Async Processing** (Week 7-8)
   - Set up Messenger for async events
   - Implement event replay capabilities
   - Add monitoring and error handling

**Files to Create**:
```
src/Analytics/Application/Handler/
├── QuizCompletedEventHandler.php
└── QuestionAnsweredIncorrectlyEventHandler.php

src/Shared/Infrastructure/Messaging/
├── SymfonyMessengerEventBus.php
└── DomainEventMiddleware.php
```

### Phase 6: Legacy Cleanup

**Goal**: Remove legacy code and complete migration.

**Tasks**:
1. **Remove Legacy Controllers** (Week 9)
   - Ensure all functionality migrated
   - Update routing to use new controllers
   - Remove old controller files

2. **Entity Migration** (Week 9-10)
   - Migrate remaining legacy entities
   - Update all database queries
   - Remove legacy repository classes

3. **Service Cleanup** (Week 10)
   - Migrate `QuizLoader` to new architecture
   - Remove legacy service classes
   - Update service configuration

### Phase 7: Testing and Optimization

**Goal**: Ensure system reliability and performance.

**Tasks**:
1. **Test Coverage** (Week 11)
   - Unit tests for all domain entities
   - Integration tests for repositories
   - Functional tests for controllers

2. **Performance Optimization** (Week 11-12)
   - Optimize database queries
   - Implement caching where needed
   - Monitor performance metrics

3. **Documentation** (Week 12)
   - Update API documentation
   - Create developer guides
   - Document deployment procedures

## Risk Mitigation

### 1. Backward Compatibility
- Maintain legacy APIs during migration
- Use feature flags for gradual rollout
- Keep legacy entities functional until migration complete

### 2. Data Integrity
- Thorough testing of data migration scripts
- Backup strategies before major changes
- Rollback procedures for each phase

### 3. Team Coordination
- Clear communication about changes
- Code review requirements for DDD components
- Training sessions on new architecture

### 4. Performance Monitoring
- Baseline performance metrics before migration
- Continuous monitoring during migration
- Performance regression testing

## Migration Commands

### Database Migration
```bash
# Generate migration for new entities
php bin/console make:migration

# Run migrations
php bin/console doctrine:migrations:migrate

# Validate schema
php bin/console doctrine:schema:validate
```

### Testing Migration
```bash
# Run existing tests to ensure compatibility
php bin/phpunit

# Run new DDD tests
php bin/phpunit src/Quiz/Domain/
php bin/phpunit src/Analytics/Domain/
```

### Service Validation
```bash
# Check service container
php bin/console debug:container Quiz
php bin/console debug:container Analytics

# Validate configuration
php bin/console lint:yaml config/
```

## Success Criteria

### Phase Completion Criteria
- [ ] All tests pass (unit, integration, functional)
- [ ] No performance regression (response time < baseline + 10%)
- [ ] All existing features continue to work
- [ ] New DDD features are accessible
- [ ] Code coverage maintains > 80%

### Final Success Criteria
- [ ] Complete removal of legacy code
- [ ] Full DDD implementation with all patterns
- [ ] Documentation is complete and up-to-date
- [ ] Team is trained on new architecture
- [ ] Performance is equal or better than baseline
- [ ] All features work as expected

## Timeline Summary

| Phase | Duration | Key Deliverables | Dependencies |
|-------|----------|------------------|--------------|
| 1. Foundation | Complete | DDD structure, base classes | None |
| 2. Infrastructure | 2 weeks | Repository implementations | Phase 1 |
| 3. Application | 2 weeks | Command/Query handlers | Phase 2 |
| 4. Controllers | 2 weeks | New controllers, templates | Phase 3 |
| 5. Events | 2 weeks | Event handlers, async processing | Phase 4 |
| 6. Cleanup | 2 weeks | Legacy removal | Phase 5 |
| 7. Testing | 2 weeks | Complete test suite | Phase 6 |

**Total Estimated Duration**: 12 weeks

## Next Immediate Actions

1. **Week 1 Priority 1**: Implement `DoctrineOrmCategoryRepository`
2. **Week 1 Priority 2**: Create repository adapters for backward compatibility
3. **Week 1 Priority 3**: Write unit tests for domain entities
4. **Week 2 Priority 1**: Implement event infrastructure
5. **Week 2 Priority 2**: Create first command handler (`SubmitQuizCommand`)

This migration plan ensures a smooth transition while maintaining system stability and backward compatibility throughout the process.