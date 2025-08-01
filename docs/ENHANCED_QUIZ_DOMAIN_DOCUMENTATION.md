# Enhanced Quiz Domain Model Documentation

## Overview

The Enhanced Quiz Domain Model is a complete redesign of the quiz system to support modern e-learning features while maintaining clean DDD architecture. This documentation provides a comprehensive guide to understanding and using the enhanced domain.

## Table of Contents

1. [Domain Architecture](#domain-architecture)
2. [Core Value Objects](#core-value-objects)
3. [Entity Design](#entity-design)
4. [Domain Services](#domain-services)
5. [Scoring System](#scoring-system)
6. [Question Types](#question-types)
7. [Usage Examples](#usage-examples)
8. [Best Practices](#best-practices)

## Domain Architecture

### DDD Principles Applied

The enhanced domain follows strict Domain-Driven Design principles:

- **Rich Domain Model**: Business logic encapsulated in entities and value objects
- **Ubiquitous Language**: Domain concepts expressed clearly in code
- **Aggregate Boundaries**: Clear ownership and consistency boundaries
- **Domain Events**: Important business events captured and communicated
- **Specifications**: Complex business rules expressed as specifications

### Bounded Context Structure

```
Quiz Domain/
├── Entity/              # Aggregate roots and entities
├── ValueObject/         # Immutable value objects
├── Service/            # Domain services
├── Repository/         # Repository interfaces
├── Event/              # Domain events
├── Exception/          # Domain exceptions
└── Specification/      # Business rule specifications
```

## Core Value Objects

### QuestionType

Represents different types of questions supported by the system:

```php
$multipleChoice = QuestionType::multipleChoice();
$singleChoice = QuestionType::singleChoice();
$trueFalse = QuestionType::trueFalse();
$codeCompletion = QuestionType::codeCompletion();
$dragAndDrop = QuestionType::dragAndDrop();
$fillInTheBlank = QuestionType::fillInTheBlank();
$essay = QuestionType::essay();
$matching = QuestionType::matching();

// Check capabilities
if ($questionType->allowsMultipleCorrectAnswers()) {
    // Handle multiple correct answers
}

if ($questionType->requiresManualGrading()) {
    // Queue for manual grading
}
```

### EnhancedDifficultyLevel

Numeric difficulty scale (1-10) with contextual information:

```php
$difficulty = EnhancedDifficultyLevel::fromLevel(7);
$categoryDifficulty = EnhancedDifficultyLevel::hard('advanced-algorithms');

// Comparisons
if ($difficulty1->isHarderThan($difficulty2)) {
    // Adjust quiz accordingly
}

// Adjustments
$adjusted = $difficulty->adjustBy(2); // Increase by 2 levels
$percentage = $difficulty->getPercentage(); // Convert to percentage
```

### Content

Rich content support with multimedia capabilities:

```php
// Plain text
$plainContent = Content::plainText('Simple question text');

// Markdown with formatting
$markdownContent = Content::markdown(
    '# Question\n\nWhat is the **time complexity** of this algorithm?'
);

// Code with syntax highlighting
$codeContent = Content::code(
    '<?php\nfor ($i = 0; $i < $n; $i++) {\n    echo $i;\n}\n?>',
    'php'
);

// LaTeX for mathematical expressions
$mathContent = Content::latex('$E = mc^2$');

// Content with metadata
$richContent = Content::markdown(
    'Question with multimedia',
    [
        'images' => ['diagram.png', 'flowchart.jpg'],
        'videos' => ['explanation.mp4'],
        'interactive' => ['simulation.html']
    ]
);

// Check content capabilities
if ($content->hasImages()) {
    $images = $content->getImages();
}

if ($content->isCode()) {
    $language = $content->getLanguage();
}
```

### Score

Complex scoring with breakdown and analytics:

```php
// Basic score
$score = Score::create(85.5, 100.0);

// Score with breakdown
$detailedScore = Score::create(75.0, 100.0)
    ->addToBreakdown('correct_answers', 15, 20)
    ->addToBreakdown('time_bonus', 5.0, 10.0)
    ->withMetadata([
        'completion_time' => 120,
        'attempts' => 2,
        'confidence_level' => 0.8
    ]);

// Score analysis
if ($score->isPerfect()) {
    // Award achievement
}

if ($score->isPassing(70.0)) {
    // Mark as passed
}

$grade = $score->getGrade(); // A, B, C, D, F
```

### Tag

Hierarchical tagging system for categorization:

```php
$skillTag = Tag::skill('algorithm-analysis');
$topicTag = Tag::topic('data-structures');
$languageTag = Tag::language('python');
$levelTag = Tag::level('intermediate');

// Custom tags
$customTag = Tag::create('industry-specific', 'domain');

// Tag operations
$slug = $tag->getSlug(); // URL-friendly version
$fullName = $tag->toString(); // category:name format
```

### TimeLimit

Flexible time constraint system:

```php
// Total time limit
$totalLimit = TimeLimit::totalTime(3600); // 1 hour

// Per-question limit
$perQuestionLimit = TimeLimit::perQuestion(120); // 2 minutes per question

// Combined limits
$combinedLimit = TimeLimit::combined(1800, 90); // 30 min total, 90s per question

// Helper methods
$minutesLimit = TimeLimit::minutes(45);
$hoursLimit = TimeLimit::hours(2);
$unlimitedTime = TimeLimit::unlimited();

// Check time constraints
if ($timeLimit->isTimeUp($elapsedSeconds, $totalQuestions, $currentQuestion)) {
    // Handle timeout
}
```

### QuizTemplate

Quiz modes and behavior configuration:

```php
// Predefined templates
$practice = QuizTemplate::practiceMode();
$exam = QuizTemplate::examMode(['max_attempts' => 3]);
$challenge = QuizTemplate::challengeMode(['difficulty_progression' => true]);
$review = QuizTemplate::reviewMode(['show_explanations' => true]);

// Custom template
$custom = QuizTemplate::customMode([
    'unlimited_attempts' => true,
    'immediate_feedback' => false,
    'allow_navigation' => true,
    'time_limited' => false
]);

// Template behaviors
if ($template->allowsUnlimitedAttempts()) {
    // No attempt limit
}

if ($template->showsImmediateFeedback()) {
    // Show feedback immediately
}

$maxAttempts = $template->getMaxAttempts(); // null for unlimited
```

## Entity Design

### EnhancedQuestion

The core question entity with rich features:

```php
// Create questions of different types
$multipleChoice = EnhancedQuestion::multipleChoice(
    Content::markdown('Which are valid PHP operators?'),
    EnhancedDifficultyLevel::medium()
);

$codeCompletion = EnhancedQuestion::codeCompletion(
    Content::code('Complete this function...', 'php'),
    EnhancedDifficultyLevel::hard()
);

$essay = EnhancedQuestion::essay(
    Content::plainText('Explain the benefits of microservices architecture'),
    EnhancedDifficultyLevel::expert()
);

// Add rich content
$question->setExplanation(
    Content::markdown('The correct answer is **A** because...')
);

$question->setHint(
    Content::plainText('Think about operator precedence')
);

// Tag management
$question->addTag(Tag::skill('operators'));
$question->addTag(Tag::language('php'));
$question->addTag(Tag::level('intermediate'));

// Answer management
$correctAnswer = EnhancedAnswer::correct(Content::plainText('&&'));
$partialAnswer = EnhancedAnswer::partialCredit(Content::plainText('AND'), 50.0);
$incorrectAnswer = EnhancedAnswer::incorrect(Content::plainText('&'));

$question->addAnswer($correctAnswer);
$question->addAnswer($partialAnswer);
$question->addAnswer($incorrectAnswer);

// Validation
if ($question->isValid()) {
    // Question is ready for use
}

// Scoring
$userAnswers = [$correctAnswer->getId()];
$score = $question->calculateScore($userAnswers);
```

### EnhancedAnswer

Sophisticated answer handling with partial credit:

```php
// Different answer types
$correct = EnhancedAnswer::correct(Content::plainText('Correct answer'));
$partial = EnhancedAnswer::partialCredit(Content::plainText('Partially correct'), 75.0);
$incorrect = EnhancedAnswer::incorrect(Content::plainText('Wrong answer'));

// True/False answers
$trueAnswer = EnhancedAnswer::trueFalse(true);
$falseAnswer = EnhancedAnswer::trueFalse(false);

// Rich content answers
$codeAnswer = EnhancedAnswer::correct(
    Content::code('function solution() { return 42; }', 'javascript')
);

// Feedback
$answer->setFeedback(
    Content::markdown('This is correct because...')
);

// Position for ordering
$answer->setPosition(1);

// Credit management
$answer->setPartialCredit(60.0); // 60% credit
$creditPercentage = $answer->getCreditPercentage();

// Metadata
$answer->addMetadata('common_mistake', false);
$answer->addMetadata('difficulty_indicator', 3);
```

### EnhancedCategory

Hierarchical category system:

```php
// Root categories
$programming = EnhancedCategory::root(
    'Programming',
    Content::markdown('Programming concepts and languages')
);

// Child categories
$phpCategory = EnhancedCategory::child(
    'PHP',
    $programming,
    Content::plainText('PHP programming language')
);

$frameworksCategory = EnhancedCategory::child(
    'Frameworks',
    $phpCategory,
    Content::plainText('PHP frameworks and libraries')
);

// Hierarchy operations
$path = $frameworksCategory->getPath(); // [programming, php, frameworks]
$fullPath = $frameworksCategory->getFullPath(); // "Programming > PHP > Frameworks"
$level = $frameworksCategory->getLevel(); // 2

// Category management
if ($category->hasChildren()) {
    $children = $category->getChildren();
}

if ($category->isDescendantOf($programming)) {
    // Handle descendant
}

// Tagging
$category->addTag(Tag::topic('backend-development'));
$category->addTag(Tag::skill('web-programming'));

// Sorting
$category->setSortOrder(10);
```

### EnhancedQuiz

Complete quiz with advanced features:

```php
// Create quiz with template
$quiz = new EnhancedQuiz(
    'Advanced PHP Quiz',
    QuizTemplate::examMode(),
    EnhancedDifficultyLevel::hard()
);

// Or use factory methods
$practiceQuiz = EnhancedQuiz::practice(
    'PHP Practice',
    EnhancedDifficultyLevel::medium()
);

$examQuiz = EnhancedQuiz::exam(
    'Final Exam',
    TimeLimit::hours(2),
    EnhancedDifficultyLevel::hard()
);

// Content management
$quiz->setDescription(
    Content::markdown('Comprehensive quiz covering **advanced PHP concepts**')
);

// Question management
$quiz->addQuestion($question1);
$quiz->addQuestion($question2);

// Category association
$quiz->addCategory($phpCategory);
$quiz->addCategory($oopCategory);

// Configuration
$quiz->setTimeLimit(TimeLimit::minutes(90));
$quiz->setScoringRules([
    'passing_percentage' => 70,
    'partial_credit_enabled' => true,
    'negative_marking' => false
]);

// Publishing
if ($quiz->isValidForPublication()) {
    $quiz->publish();
}

// Analytics
$maxScore = $quiz->getMaximumScore();
$avgDifficulty = $quiz->getAverageDifficulty();
$questionCount = $quiz->getQuestionCount();
```

### EnhancedQuizAttempt

Detailed attempt tracking with analytics:

```php
// Start attempt
$attempt = EnhancedQuizAttempt::start($userId, $quizId, 1);

// Add answers
$userAnswer1 = UserAnswer::singleChoice($questionId1, $answerId1, 45);
$userAnswer2 = UserAnswer::multipleChoice($questionId2, [$answerId2, $answerId3], 120);
$userAnswer3 = UserAnswer::textBased($questionId3, 'Essay response...', 300);

$attempt->addUserAnswer($userAnswer1);
$attempt->addUserAnswer($userAnswer2);
$attempt->addUserAnswer($userAnswer3);

// Complete attempt
$finalScore = Score::create(85.5, 100.0);
$performanceMetrics = [
    'average_time_per_question' => 95.5,
    'confidence_level' => 0.75,
    'difficulty_progression' => 'stable'
];

$attempt->complete($finalScore, $performanceMetrics);

// Submit for final evaluation
$attempt->submit();

// Analytics
$duration = $attempt->getDuration();
$avgTimePerQuestion = $attempt->getAverageTimePerQuestion();
$answeredCount = $attempt->getAnsweredQuestionCount();
```

## Domain Services

### QuizGeneratorService

Intelligent quiz generation based on criteria:

```php
$criteria = QuizGenerationCriteria::practice(
    'Dynamic PHP Quiz',
    20, // question count
    EnhancedDifficultyLevel::medium()
)
->withCategories([$phpCategoryId, $oopCategoryId])
->withTags([Tag::skill('problem-solving'), Tag::language('php')])
->withQuestionTypes(['multiple_choice', 'single_choice', 'code_completion'])
->withTimeLimit(TimeLimit::minutes(45));

$generatedQuiz = $this->quizGenerator->generateQuiz($criteria);

// Advanced criteria
$examCriteria = QuizGenerationCriteria::exam(
    'Certification Exam',
    50,
    EnhancedDifficultyLevel::hard(),
    TimeLimit::hours(2)
)
->withQuestionTypeDistribution([
    'multiple_choice' => 60,    // 60%
    'single_choice' => 30,      // 30%
    'code_completion' => 10     // 10%
])
->withExcludeQuestionIds($previouslyAskedIds);
```

### DifficultyCalculatorService

Adaptive difficulty management:

```php
// Calculate initial difficulty
$initialDifficulty = $this->difficultyCalculator->calculateInitialDifficulty($question);

// Adjust based on performance
$adjustedDifficulty = $this->difficultyCalculator->adjustDifficultyBasedOnPerformance(
    $question,
    $recentAttempts
);

// Personalized difficulty
$personalizedDifficulty = $this->difficultyCalculator->calculatePersonalizedDifficulty(
    $userId,
    $categoryId
);

// Progressive difficulty
$nextDifficulty = $this->difficultyCalculator->recommendNextDifficulty(
    $currentDifficulty,
    $successRate,
    $consecutiveSuccesses
);

// Optimal range for quiz
$difficultyRange = $this->difficultyCalculator->calculateOptimalDifficultyRange(
    $targetDifficulty,
    $questionCount
);
```

## Scoring System

### Strategy Pattern Implementation

The scoring system uses the Strategy pattern to handle different question types:

```php
// Automatic strategy selection
$strategy = QuestionScoringStrategyFactory::create($question->getType());
$score = $strategy->calculateScore($question, $userAnswers);

// Manual strategy registration
QuestionScoringStrategyFactory::registerStrategy(
    'custom_type',
    new CustomScoringStrategy()
);
```

### Built-in Scoring Strategies

1. **MultipleChoiceScoringStrategy**: Partial credit with penalty for incorrect selections
2. **SingleChoiceScoringStrategy**: Binary correct/incorrect scoring
3. **TrueFalseScoringStrategy**: Simple binary scoring
4. **CodeCompletionScoringStrategy**: Requires manual grading
5. **EssayScoringStrategy**: Requires manual grading
6. **DefaultScoringStrategy**: Fallback for unsupported types

### Custom Scoring Strategies

```php
class WeightedScoringStrategy implements QuestionScoringStrategyInterface
{
    public function calculateScore(EnhancedQuestion $question, array $userAnswers): Score
    {
        $maxPoints = $question->getScoringWeight();
        $weights = $question->getMetadataValue('answer_weights', []);
        
        $totalScore = 0;
        foreach ($userAnswers as $answerId) {
            $weight = $weights[$answerId] ?? 1.0;
            $totalScore += $weight;
        }
        
        $percentage = min(100, ($totalScore / array_sum($weights)) * 100);
        $points = ($percentage / 100) * $maxPoints;
        
        return Score::create($points, $maxPoints)
            ->withMetadata(['weighted_scoring' => true]);
    }
    
    public function supports(EnhancedQuestion $question): bool
    {
        return $question->getMetadataValue('scoring_type') === 'weighted';
    }
}
```

## Question Types

### Supported Types Overview

| Type | Multiple Answers | Manual Grading | Partial Credit | Auto-Generated |
|------|------------------|----------------|----------------|-----------------|
| Multiple Choice | ✅ | ❌ | ✅ | ✅ |
| Single Choice | ❌ | ❌ | ❌ | ✅ |
| True/False | ❌ | ❌ | ❌ | ✅ |
| Code Completion | ❌ | ✅ | ✅ | ❌ |
| Drag and Drop | ✅ | ❌ | ✅ | ✅ |
| Fill in the Blank | ✅ | ❌ | ✅ | ✅ |
| Essay | ❌ | ✅ | ✅ | ❌ |
| Matching | ✅ | ❌ | ✅ | ✅ |

### Implementation Examples

#### Multiple Choice with Partial Credit

```php
$question = EnhancedQuestion::multipleChoice(
    Content::plainText('Which are valid HTTP methods?'),
    EnhancedDifficultyLevel::medium()
);

$question->addAnswer(EnhancedAnswer::correct(Content::plainText('GET')));
$question->addAnswer(EnhancedAnswer::correct(Content::plainText('POST')));
$question->addAnswer(EnhancedAnswer::correct(Content::plainText('PUT')));
$question->addAnswer(EnhancedAnswer::incorrect(Content::plainText('SEND')));

// User selects GET and POST (2 out of 3 correct)
$userAnswers = [$getId('GET'), $getId('POST')];
$score = $question->calculateScore($userAnswers);
// Score: ~67% (partial credit for incomplete selection)
```

#### Code Completion with Manual Grading

```php
$question = EnhancedQuestion::codeCompletion(
    Content::code(
        'Complete the bubble sort implementation:\n\n' .
        'function bubbleSort($arr) {\n' .
        '    // TODO: Implement bubble sort\n' .
        '}',
        'php'
    ),
    EnhancedDifficultyLevel::hard()
);

// Code completion requires manual grading
$userCode = 'for ($i = 0; $i < count($arr); $i++) { /* user code */ }';
$userAnswer = UserAnswer::textBased($question->getId(), $userCode, 600);

// Initially returns zero score pending manual review
$score = $question->calculateScore([$userCode]);
// Later, manually graded by instructor
```

#### Essay Questions

```php
$question = EnhancedQuestion::essay(
    Content::markdown(
        '## Question\n\n' .
        'Discuss the **advantages and disadvantages** of microservices architecture.\n\n' .
        'Your answer should include:\n' .
        '- Scalability considerations\n' .
        '- Operational complexity\n' .
        '- Team organization impacts'
    ),
    EnhancedDifficultyLevel::expert()
);

$question->addMetadata('word_limit', 500);
$question->addMetadata('evaluation_criteria', [
    'technical_accuracy' => 40,
    'depth_of_analysis' => 30,
    'clarity_of_expression' => 20,
    'examples_provided' => 10
]);
```

## Usage Examples

### Creating a Complete Quiz

```php
// 1. Create quiz with template
$quiz = EnhancedQuiz::practice(
    'PHP Fundamentals Practice',
    EnhancedDifficultyLevel::medium()
);

$quiz->setDescription(
    Content::markdown('Practice quiz covering **PHP fundamentals**')
);

// 2. Create and add questions
$question1 = EnhancedQuestion::singleChoice(
    Content::plainText('What does PHP stand for?'),
    EnhancedDifficultyLevel::easy()
);

$question1->addAnswer(EnhancedAnswer::correct(
    Content::plainText('PHP: Hypertext Preprocessor')
));
$question1->addAnswer(EnhancedAnswer::incorrect(
    Content::plainText('Personal Home Page')
));
$question1->addAnswer(EnhancedAnswer::incorrect(
    Content::plainText('Private Home Page')
));

$question1->setExplanation(
    Content::markdown('PHP originally stood for Personal Home Page, but now it\'s a **recursive acronym** for PHP: Hypertext Preprocessor.')
);

$quiz->addQuestion($question1);

// 3. Add more complex questions
$question2 = EnhancedQuestion::multipleChoice(
    Content::code(
        'Which of the following will output "Hello World"?',
        'php'
    ),
    EnhancedDifficultyLevel::medium()
);

$question2->addAnswer(EnhancedAnswer::correct(
    Content::code('echo "Hello World";', 'php')
));
$question2->addAnswer(EnhancedAnswer::correct(
    Content::code('print "Hello World";', 'php')
));
$question2->addAnswer(EnhancedAnswer::incorrect(
    Content::code('output "Hello World";', 'php')
));

$quiz->addQuestion($question2);

// 4. Configure and publish
$quiz->setScoringRules([
    'passing_percentage' => 70,
    'partial_credit_enabled' => true
]);

if ($quiz->isValidForPublication()) {
    $quiz->publish();
}
```

### Adaptive Quiz Generation

```php
// Generate personalized quiz
$userDifficulty = $this->difficultyCalculator->calculatePersonalizedDifficulty($userId);

$criteria = QuizGenerationCriteria::practice(
    'Personalized Practice Quiz',
    15,
    $userDifficulty
)
->withCategories($userPreferences['categories'])
->withTags($userPreferences['focus_areas'])
->withExcludeQuestionIds($recentlyAnsweredQuestions);

$personalizedQuiz = $this->quizGenerator->generateQuiz($criteria);

// Adjust difficulty based on performance
foreach ($personalizedQuiz->getQuestions() as $question) {
    $adjustedDifficulty = $this->difficultyCalculator->adjustDifficultyBasedOnPerformance(
        $question,
        $userRecentAttempts
    );
    
    if (!$adjustedDifficulty->equals($question->getDifficultyLevel())) {
        $question->updateDifficultyLevel($adjustedDifficulty);
    }
}
```

### Performance Analytics

```php
// Analyze quiz performance
$attempts = $this->attemptRepository->findByQuizId($quizId);
$analytics = $this->performanceAnalyzer->analyzeQuizPerformance($attempts);

$insights = [
    'average_score' => $analytics->getAverageScore(),
    'completion_rate' => $analytics->getCompletionRate(),
    'average_time' => $analytics->getAverageCompletionTime(),
    'difficult_questions' => $analytics->getMostFailedQuestions(),
    'easy_questions' => $analytics->getMostSuccessfulQuestions(),
    'improvement_suggestions' => $analytics->getImprovementSuggestions()
];

// Adjust quiz based on analytics
if ($analytics->getAverageScore() > 90) {
    // Quiz might be too easy
    $this->increaseQuizDifficulty($quiz);
} elseif ($analytics->getAverageScore() < 50) {
    // Quiz might be too hard
    $this->decreaseQuizDifficulty($quiz);
}
```

## Best Practices

### Entity Design

1. **Keep Aggregates Small**: Focus on single business concepts
2. **Encapsulate Business Rules**: Logic belongs in the domain, not services
3. **Use Value Objects**: Immutable objects for concepts without identity
4. **Record Domain Events**: Capture important business moments

### Performance Optimization

1. **Lazy Loading**: Load questions and answers only when needed
2. **Caching Strategy**: Cache frequently accessed content and metadata
3. **Database Indexing**: Index on commonly queried fields
4. **Batch Operations**: Process multiple entities together when possible

### Testing Strategy

1. **Unit Tests**: Test individual entities and value objects
2. **Integration Tests**: Test repository implementations
3. **Domain Tests**: Test complex business scenarios
4. **Performance Tests**: Ensure scalability requirements are met

### Security Considerations

1. **Input Validation**: Validate all content and metadata
2. **Content Sanitization**: Sanitize HTML and markdown content
3. **Access Control**: Implement proper authorization
4. **Audit Trail**: Log important domain events

This enhanced Quiz domain model provides a solid foundation for building sophisticated e-learning applications while maintaining clean, testable, and maintainable code architecture.