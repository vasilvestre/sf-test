<?php

declare(strict_types=1);

namespace App\Tests\Quiz\Domain\Event;

use App\Quiz\Domain\Event\QuizSessionStarted;
use App\Quiz\Domain\Event\QuizSessionCompleted;
use App\Quiz\Domain\Event\QuestionAnswered;
use App\Quiz\Domain\ValueObject\EnhancedDifficultyLevel;
use App\Shared\Domain\ValueObject\Id;
use App\User\Domain\Entity\UserId;
use PHPUnit\Framework\TestCase;

/**
 * Test class for Quiz Session domain events.
 * Validates event creation and data structure.
 */
final class QuizSessionEventsTest extends TestCase
{
    public function test_quiz_session_started_event_creation(): void
    {
        $sessionId = Id::fromInt(1);
        $userId = UserId::fromInt(123);
        $targetDifficulty = EnhancedDifficultyLevel::medium();

        $event = new QuizSessionStarted(
            $sessionId,
            $userId,
            10, // total questions
            $targetDifficulty,
            true, // adaptive learning
            false // practice mode
        );

        $this->assertEquals($sessionId, $event->getSessionId());
        $this->assertEquals($userId, $event->getUserId());
        $this->assertEquals(10, $event->getTotalQuestions());
        $this->assertEquals($targetDifficulty, $event->getTargetDifficulty());
        $this->assertTrue($event->isAdaptiveLearning());
        $this->assertFalse($event->isPracticeMode());
        $this->assertEquals('quiz.session.started', $event->getEventName());
        $this->assertInstanceOf(\DateTimeImmutable::class, $event->getOccurredOn());
    }

    public function test_quiz_session_completed_event_creation(): void
    {
        $sessionId = Id::fromInt(1);
        $userId = UserId::fromInt(123);
        $adaptiveLearningData = [
            ['questionId' => '1', 'correct' => true, 'score' => 1.0],
            ['questionId' => '2', 'correct' => false, 'score' => 0.0],
        ];

        $event = new QuizSessionCompleted(
            $sessionId,
            $userId,
            85.5, // final score
            8, // correct answers
            10, // total questions
            450.0, // total time spent
            $adaptiveLearningData
        );

        $this->assertEquals($sessionId, $event->getSessionId());
        $this->assertEquals($userId, $event->getUserId());
        $this->assertEquals(85.5, $event->getFinalScore());
        $this->assertEquals(8, $event->getCorrectAnswers());
        $this->assertEquals(10, $event->getTotalQuestions());
        $this->assertEquals(450.0, $event->getTotalTimeSpent());
        $this->assertEquals($adaptiveLearningData, $event->getAdaptiveLearningData());
    }

    public function test_quiz_session_completed_event_calculations(): void
    {
        $event = new QuizSessionCompleted(
            Id::fromInt(1),
            UserId::fromInt(123),
            80.0, // final score
            8, // correct answers
            10, // total questions
            300.0, // total time spent (5 minutes)
            []
        );

        $this->assertEquals(80.0, $event->getAccuracy()); // 8/10 * 100
        $this->assertEquals(30.0, $event->getAverageTimePerQuestion()); // 300/10
        $this->assertEquals('good', $event->getPerformanceLevel());
        $this->assertTrue($event->isSuccessful()); // 80% >= 70% default
        $this->assertFalse($event->isSuccessful(85.0)); // 80% < 85%
    }

    public function test_question_answered_event_creation(): void
    {
        $sessionId = Id::fromInt(1);
        $questionId = Id::fromInt(42);
        $submittedAnswers = ['answer_1', 'answer_3'];

        $event = new QuestionAnswered(
            $sessionId,
            $questionId,
            $submittedAnswers,
            true, // is correct
            0.85, // score (85%)
            45.5 // time spent
        );

        $this->assertEquals($sessionId, $event->getSessionId());
        $this->assertEquals($questionId, $event->getQuestionId());
        $this->assertEquals($submittedAnswers, $event->getSubmittedAnswers());
        $this->assertTrue($event->isCorrect());
        $this->assertEquals(0.85, $event->getScore());
        $this->assertEquals(45.5, $event->getTimeSpent());
        $this->assertEquals('quiz.question.answered', $event->getEventName());
    }

    public function test_events_provide_analytics_payload(): void
    {
        // Test QuizSessionStarted analytics
        $startedEvent = new QuizSessionStarted(
            Id::fromInt(1),
            UserId::fromInt(123),
            5,
            EnhancedDifficultyLevel::hard(),
            true,
            true
        );

        $startedAnalytics = $startedEvent->getAnalyticsPayload();
        $this->assertEquals('quiz_session_started', $startedAnalytics['event_type']);
        $this->assertArrayHasKey('configuration', $startedAnalytics);
        $this->assertArrayHasKey('timestamp', $startedAnalytics);

        // Test QuizSessionCompleted analytics
        $completedEvent = new QuizSessionCompleted(
            Id::fromInt(1),
            UserId::fromInt(123),
            90.0,
            9,
            10,
            180.0,
            [
                ['correct' => true, 'difficulty' => 'easy', 'timeSpent' => 15],
                ['correct' => false, 'difficulty' => 'medium', 'timeSpent' => 25],
                ['correct' => true, 'difficulty' => 'hard', 'timeSpent' => 35],
            ]
        );

        $completedAnalytics = $completedEvent->getAnalyticsPayload();
        $this->assertEquals('quiz_session_completed', $completedAnalytics['event_type']);
        $this->assertArrayHasKey('results', $completedAnalytics);
        $this->assertArrayHasKey('timing', $completedAnalytics);
        $this->assertArrayHasKey('adaptive_learning', $completedAnalytics);

        // Test QuestionAnswered analytics
        $answeredEvent = new QuestionAnswered(
            Id::fromInt(1),
            Id::fromInt(5),
            ['option_b'],
            true,
            1.0,
            30.0
        );

        $answeredAnalytics = $answeredEvent->getAnalyticsPayload();
        $this->assertEquals('question_answered', $answeredAnalytics['event_type']);
        $this->assertArrayHasKey('answer_data', $answeredAnalytics);
        $this->assertArrayHasKey('timestamp', $answeredAnalytics);
    }

    public function test_quiz_session_completed_provides_recommendations(): void
    {
        // Low score scenario
        $lowScoreEvent = new QuizSessionCompleted(
            Id::fromInt(1),
            UserId::fromInt(123),
            55.0, // Low score
            5,
            10,
            120.0, // 12 seconds per question (too fast)
            []
        );

        $recommendations = $lowScoreEvent->getRecommendations();
        $this->assertContains('Consider reviewing the material before taking another quiz', $recommendations);
        $this->assertContains('Take more time to carefully read and consider each question', $recommendations);

        // High score scenario
        $highScoreEvent = new QuizSessionCompleted(
            Id::fromInt(2),
            UserId::fromInt(123),
            95.0, // High score
            10,
            10,
            300.0, // 30 seconds per question (good pace)
            []
        );

        $recommendations = $highScoreEvent->getRecommendations();
        $this->assertContains('Excellent performance! Consider trying more challenging topics', $recommendations);
    }
}