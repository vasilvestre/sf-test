# Enhanced Quiz Domain Migration Plan

## Overview

This document outlines the migration strategy from the current basic Quiz domain to the enhanced model that supports modern e-learning features while maintaining backward compatibility.

## Current State Analysis

### Existing Entities
- **Category**: Basic name/description structure
- **Question**: Simple text with basic answers
- **Answer**: Text with boolean correctness flag
- **QuizResult**: Basic score tracking

### Current Limitations
- Single question type (multiple choice)
- Plain text content only
- Simple boolean correctness
- No difficulty progression
- Limited analytics
- No rich feedback system

## Enhanced Domain Model

### New Value Objects
- `QuestionType`: Extensible question type system
- `EnhancedDifficultyLevel`: Numeric scale (1-10) with context
- `Content`: Rich content with multimedia support
- `Tag`: Categorization and search capabilities
- `Score`: Complex scoring with breakdowns
- `TimeLimit`: Flexible time constraints
- `QuizTemplate`: Different quiz modes and behaviors

### Enhanced Entities
- `EnhancedQuestion`: Rich questions with multiple types
- `EnhancedAnswer`: Partial credit and feedback support
- `EnhancedCategory`: Hierarchical structure with metadata
- `EnhancedQuiz`: Templates, scoring rules, analytics
- `EnhancedQuizAttempt`: Detailed attempt tracking
- `UserAnswer`: Rich answer capture with timing

### Domain Services
- `QuizGeneratorService`: Intelligent quiz creation
- `DifficultyCalculatorService`: Adaptive difficulty
- `QuestionScoringStrategyFactory`: Pluggable scoring
- `PerformanceAnalyzerService`: Analytics and insights

## Migration Strategy

### Phase 1: Data Preservation and Schema Evolution

#### 1.1 Export Existing Data
```bash
# Export current quiz data
php bin/console app:export-quiz-data --format=json --output=migration/current-data.json
```

#### 1.2 Create Migration Tables
```sql
-- Backup existing tables
CREATE TABLE category_backup AS SELECT * FROM category;
CREATE TABLE question_backup AS SELECT * FROM question;
CREATE TABLE answer_backup AS SELECT * FROM answer;
CREATE TABLE quiz_result_backup AS SELECT * FROM quiz_result;

-- Create enhanced tables with backward compatibility
CREATE TABLE enhanced_category (
    id UUID PRIMARY KEY,
    legacy_id INTEGER REFERENCES category(id),
    name VARCHAR(100) NOT NULL,
    description JSONB,
    parent_id UUID REFERENCES enhanced_category(id),
    slug VARCHAR(120) NOT NULL,
    sort_order INTEGER DEFAULT 0,
    is_active BOOLEAN DEFAULT true,
    tags JSONB DEFAULT '[]',
    metadata JSONB DEFAULT '{}',
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP
);

CREATE TABLE enhanced_question (
    id UUID PRIMARY KEY,
    legacy_id INTEGER REFERENCES question(id),
    content JSONB NOT NULL,
    type VARCHAR(50) NOT NULL DEFAULT 'multiple_choice',
    difficulty_level INTEGER NOT NULL DEFAULT 5,
    scoring_weight DECIMAL(5,2) NOT NULL DEFAULT 1.0,
    explanation JSONB,
    hint JSONB,
    tags JSONB DEFAULT '[]',
    metadata JSONB DEFAULT '{}',
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP
);

CREATE TABLE enhanced_answer (
    id UUID PRIMARY KEY,
    legacy_id INTEGER REFERENCES answer(id),
    question_id UUID REFERENCES enhanced_question(id),
    content JSONB NOT NULL,
    is_correct BOOLEAN NOT NULL DEFAULT false,
    partial_credit_percentage DECIMAL(5,2) NOT NULL DEFAULT 0.0,
    position INTEGER,
    feedback JSONB,
    metadata JSONB DEFAULT '{}',
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP
);
```

#### 1.3 Data Migration Scripts
```php
// Migration command to transform existing data
class MigrateQuizDataCommand extends Command
{
    public function execute(): int
    {
        // Migrate categories
        $this->migrateCategories();
        
        // Migrate questions
        $this->migrateQuestions();
        
        // Migrate answers
        $this->migrateAnswers();
        
        // Migrate quiz results to attempts
        $this->migrateQuizResults();
        
        return Command::SUCCESS;
    }
    
    private function migrateCategories(): void
    {
        $categories = $this->legacyCategoryRepository->findAll();
        
        foreach ($categories as $legacyCategory) {
            $enhanced = new EnhancedCategory(
                $legacyCategory->getName(),
                $legacyCategory->getDescription() ? 
                    Content::plainText($legacyCategory->getDescription()) : null
            );
            
            $enhanced->addMetadata('legacy_id', $legacyCategory->getId());
            $this->enhancedCategoryRepository->save($enhanced);
        }
    }
    
    private function migrateQuestions(): void
    {
        $questions = $this->legacyQuestionRepository->findAll();
        
        foreach ($questions as $legacyQuestion) {
            $content = Content::plainText($legacyQuestion->getText());
            $difficulty = $this->estimateDifficulty($legacyQuestion);
            
            $enhanced = EnhancedQuestion::multipleChoice($content, $difficulty);
            $enhanced->addMetadata('legacy_id', $legacyQuestion->getId());
            
            $this->enhancedQuestionRepository->save($enhanced);
        }
    }
}
```

### Phase 2: Gradual Feature Introduction

#### 2.1 Feature Flags
```yaml
# config/packages/app_quiz.yaml
app_quiz:
    features:
        enhanced_domain: true
        rich_content: false      # Start disabled
        multiple_question_types: false
        adaptive_difficulty: false
        analytics: false
```

#### 2.2 Backward Compatibility Layer
```php
// Adapter to maintain existing API compatibility
class LegacyQuestionAdapter
{
    public function __construct(
        private EnhancedQuestion $enhancedQuestion
    ) {}
    
    public function getText(): string
    {
        return $this->enhancedQuestion->getContent()->getText();
    }
    
    public function getAnswers(): array
    {
        return array_map(
            fn(EnhancedAnswer $answer) => new LegacyAnswerAdapter($answer),
            $this->enhancedQuestion->getAnswers()
        );
    }
    
    // ... other legacy methods
}
```

#### 2.3 Progressive API Updates
```php
// Add enhanced endpoints while keeping legacy ones
class QuizController
{
    // Legacy endpoint (maintain compatibility)
    #[Route('/api/questions', methods: ['GET'])]
    public function getQuestions(): JsonResponse
    {
        $questions = $this->legacyQuestionService->getQuestions();
        return $this->json($questions);
    }
    
    // New enhanced endpoint
    #[Route('/api/v2/questions', methods: ['GET'])]
    public function getEnhancedQuestions(): JsonResponse
    {
        $questions = $this->enhancedQuestionService->getQuestions();
        return $this->json($questions);
    }
}
```

### Phase 3: Feature Enablement

#### 3.1 Rich Content Support
```php
// Enable rich content gradually
if ($this->featureFlags->isEnabled('rich_content')) {
    // Support markdown, code, images
    $content = Content::markdown($input['content'], $input['metadata']);
} else {
    // Fall back to plain text
    $content = Content::plainText($input['content']);
}
```

#### 3.2 Multiple Question Types
```php
// Add question type support
$questionType = $this->featureFlags->isEnabled('multiple_question_types') 
    ? QuestionType::fromString($input['type'])
    : QuestionType::multipleChoice();
```

#### 3.3 Adaptive Difficulty
```php
// Enable difficulty calculation
if ($this->featureFlags->isEnabled('adaptive_difficulty')) {
    $difficulty = $this->difficultyCalculator->calculatePersonalizedDifficulty($userId);
} else {
    $difficulty = EnhancedDifficultyLevel::medium();
}
```

### Phase 4: Data Validation and Testing

#### 4.1 Data Integrity Checks
```php
class ValidateDataMigrationCommand extends Command
{
    public function execute(): int
    {
        $this->validateCategoryMigration();
        $this->validateQuestionMigration();
        $this->validateAnswerMigration();
        $this->validateQuizResultMigration();
        
        return Command::SUCCESS;
    }
    
    private function validateQuestionMigration(): void
    {
        $legacyCount = $this->legacyQuestionRepository->count();
        $enhancedCount = $this->enhancedQuestionRepository->count();
        
        if ($legacyCount !== $enhancedCount) {
            throw new \Exception("Question count mismatch: $legacyCount vs $enhancedCount");
        }
        
        // Validate each question individually
        foreach ($this->legacyQuestionRepository->findAll() as $legacy) {
            $enhanced = $this->enhancedQuestionRepository->findByLegacyId($legacy->getId());
            if (!$enhanced) {
                throw new \Exception("Missing enhanced question for legacy ID: {$legacy->getId()}");
            }
            
            if ($enhanced->getContent()->getText() !== $legacy->getText()) {
                throw new \Exception("Content mismatch for question {$legacy->getId()}");
            }
        }
    }
}
```

#### 4.2 Performance Testing
```php
class PerformanceTestCommand extends Command
{
    public function execute(): int
    {
        $this->testQuestionRetrieval();
        $this->testQuizGeneration();
        $this->testScoring();
        
        return Command::SUCCESS;
    }
    
    private function testQuestionRetrieval(): void
    {
        $start = microtime(true);
        
        // Test large question set retrieval
        $questions = $this->enhancedQuestionRepository->findByCriteria(
            QuizGenerationCriteria::practice('Performance Test', 100, EnhancedDifficultyLevel::medium())
        );
        
        $duration = microtime(true) - $start;
        
        if ($duration > 1.0) { // Should complete under 1 second
            throw new \Exception("Question retrieval too slow: {$duration}s");
        }
        
        echo "Retrieved " . count($questions) . " questions in {$duration}s\n";
    }
}
```

### Phase 5: Legacy System Retirement

#### 5.1 Deprecation Notices
```php
// Add deprecation notices to legacy endpoints
#[Route('/api/questions', methods: ['GET'])]
#[Deprecated('Use /api/v2/questions instead. This endpoint will be removed in version 3.0')]
public function getQuestions(): JsonResponse
{
    // Log deprecation usage
    $this->logger->warning('Legacy question endpoint used', [
        'endpoint' => '/api/questions',
        'user_agent' => $this->request->headers->get('User-Agent'),
        'ip' => $this->request->getClientIp(),
    ]);
    
    return $this->json($this->legacyQuestionService->getQuestions());
}
```

#### 5.2 Usage Monitoring
```yaml
# Monitor legacy endpoint usage
monolog:
    handlers:
        deprecation:
            type: stream
            path: '%kernel.logs_dir%/deprecation.log'
            level: warning
            channels: ['deprecation']
```

#### 5.3 Cleanup Phase
```sql
-- After confirming all systems use enhanced model
DROP TABLE question CASCADE;
DROP TABLE answer CASCADE;
DROP TABLE category CASCADE;
DROP TABLE quiz_result CASCADE;

-- Remove legacy ID columns
ALTER TABLE enhanced_question DROP COLUMN legacy_id;
ALTER TABLE enhanced_answer DROP COLUMN legacy_id;
ALTER TABLE enhanced_category DROP COLUMN legacy_id;
```

## Risk Mitigation

### Data Loss Prevention
- Complete data backup before migration
- Incremental migration with rollback capability
- Parallel running of old and new systems
- Comprehensive validation at each step

### Performance Considerations
- Database indexing strategy for new schema
- Query optimization for complex criteria
- Caching strategy for frequently accessed data
- Load testing with realistic data volumes

### User Experience
- Gradual feature rollout to prevent confusion
- Clear communication about new capabilities
- Training materials for content creators
- Feedback collection and rapid iteration

## Success Metrics

### Technical Metrics
- Zero data loss during migration
- Response time improvements (target: <200ms for question retrieval)
- Successful deployment of new features without downtime
- 100% backward compatibility during transition

### Business Metrics
- Increased quiz creation rate with new question types
- Improved user engagement with rich content
- Better learning outcomes with adaptive difficulty
- Enhanced analytics providing actionable insights

## Timeline

### Month 1: Foundation
- Week 1-2: Enhanced domain model implementation
- Week 3-4: Migration scripts and validation tools

### Month 2: Migration
- Week 1: Data export and backup
- Week 2: Schema evolution and data migration
- Week 3: Validation and testing
- Week 4: Performance optimization

### Month 3: Feature Rollout
- Week 1: Rich content support
- Week 2: Multiple question types
- Week 3: Adaptive difficulty
- Week 4: Analytics and reporting

### Month 4: Optimization
- Week 1-2: Performance tuning
- Week 3: User feedback integration
- Week 4: Legacy system retirement planning

This migration plan ensures a safe, gradual transition to the enhanced Quiz domain while maintaining system stability and user experience throughout the process.