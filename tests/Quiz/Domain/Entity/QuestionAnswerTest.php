<?php

declare(strict_types=1);

namespace App\Tests\Quiz\Domain\Entity;

use App\Quiz\Domain\Entity\QuestionAnswer;
use App\Quiz\Domain\Entity\EnhancedQuestion;
use App\Quiz\Domain\Entity\EnhancedAnswer;
use App\Quiz\Domain\ValueObject\Content;
use App\Quiz\Domain\ValueObject\QuestionType;
use App\Quiz\Domain\ValueObject\EnhancedDifficultyLevel;
use App\Shared\Domain\ValueObject\Id;
use PHPUnit\Framework\TestCase;

/**
 * Test class for QuestionAnswer entity.
 * Validates core functionality and business logic.
 */
final class QuestionAnswerTest extends TestCase
{
    private EnhancedQuestion $question;

    protected function setUp(): void
    {
        // Create a sample multiple choice question
        $this->question = new EnhancedQuestion(
            Content::plainText('What is 2 + 2?'),
            QuestionType::multipleChoice(),
            EnhancedDifficultyLevel::easy(),
            1.0
        );
        
        $this->question->setId(Id::fromInt(1));
        
        // Add some answers
        $correctAnswer = new EnhancedAnswer(
            Content::plainText('4'),
            true,
            100.0
        );
        $incorrectAnswer1 = new EnhancedAnswer(
            Content::plainText('3'),
            false,
            0.0
        );
        $incorrectAnswer2 = new EnhancedAnswer(
            Content::plainText('5'),
            false,
            0.0
        );
        
        $this->question->addAnswer($correctAnswer);
        $this->question->addAnswer($incorrectAnswer1);
        $this->question->addAnswer($incorrectAnswer2);
    }

    public function test_it_should_create_question_answer_with_correct_answer(): void
    {
        $questionAnswer = new QuestionAnswer(
            Id::fromInt(1),
            $this->question,
            ['correct_answer_id'], // Simulating correct answer
            30.5, // Time spent in seconds
            []
        );

        $this->assertEquals($this->question, $questionAnswer->getQuestion());
        $this->assertEquals(['correct_answer_id'], $questionAnswer->getSubmittedAnswers());
        $this->assertEquals(30.5, $questionAnswer->getTimeSpent());
        $this->assertInstanceOf(\DateTimeImmutable::class, $questionAnswer->getAnsweredAt());
    }

    public function test_it_should_validate_time_spent(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Time spent cannot be negative');

        new QuestionAnswer(
            Id::fromInt(1),
            $this->question,
            ['answer'],
            -5.0, // Negative time
            []
        );
    }

    public function test_it_should_validate_maximum_time_spent(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Time spent exceeds maximum allowed (1 hour)');

        new QuestionAnswer(
            Id::fromInt(1),
            $this->question,
            ['answer'],
            3700.0, // More than 1 hour
            []
        );
    }

    public function test_it_should_provide_performance_metrics(): void
    {
        $questionAnswer = new QuestionAnswer(
            Id::fromInt(1),
            $this->question,
            ['answer_id'],
            45.0,
            ['hints_used' => 1]
        );

        $metrics = $questionAnswer->getPerformanceMetrics();
        
        $this->assertArrayHasKey('question_id', $metrics);
        $this->assertArrayHasKey('question_type', $metrics);
        $this->assertArrayHasKey('difficulty_level', $metrics);
        $this->assertArrayHasKey('time_spent', $metrics);
        $this->assertArrayHasKey('hints_used', $metrics);
        $this->assertEquals(45.0, $metrics['time_spent']);
        $this->assertEquals(1, $metrics['hints_used']);
    }

    public function test_it_should_detect_hints_usage(): void
    {
        $questionAnswerWithHints = new QuestionAnswer(
            Id::fromInt(1),
            $this->question,
            ['answer'],
            30.0,
            ['hints_used' => 2]
        );

        $questionAnswerWithoutHints = new QuestionAnswer(
            Id::fromInt(2),
            $this->question,
            ['answer'],
            30.0,
            []
        );

        $this->assertTrue($questionAnswerWithHints->hintsUsed());
        $this->assertEquals(2, $questionAnswerWithHints->getHintsUsedCount());
        
        $this->assertFalse($questionAnswerWithoutHints->hintsUsed());
        $this->assertEquals(0, $questionAnswerWithoutHints->getHintsUsedCount());
    }

    public function test_it_should_check_quality_standards(): void
    {
        // Too fast answer - should not meet quality standards
        $tooFastAnswer = new QuestionAnswer(
            Id::fromInt(1),
            $this->question,
            ['answer'],
            0.5, // Less than 1 second
            []
        );

        // Good answer - should meet quality standards
        $goodAnswer = new QuestionAnswer(
            Id::fromInt(2),
            $this->question,
            ['answer'],
            15.0,
            []
        );

        $this->assertFalse($tooFastAnswer->meetsQualityStandards());
        $this->assertTrue($goodAnswer->meetsQualityStandards());
    }

    public function test_it_should_validate_true_false_answers(): void
    {
        $trueFalseQuestion = new EnhancedQuestion(
            Content::plainText('The sky is blue.'),
            QuestionType::trueFalse(),
            EnhancedDifficultyLevel::easy()
        );

        // Valid true/false answer
        $validAnswer = new QuestionAnswer(
            Id::fromInt(1),
            $trueFalseQuestion,
            [true],
            10.0,
            []
        );

        $this->assertEquals([true], $validAnswer->getSubmittedAnswers());

        // Invalid true/false answer (multiple values)
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('True/False questions must have exactly one boolean answer');

        new QuestionAnswer(
            Id::fromInt(2),
            $trueFalseQuestion,
            [true, false], // Multiple answers for true/false
            10.0,
            []
        );
    }

    public function test_it_should_validate_essay_answers(): void
    {
        $essayQuestion = new EnhancedQuestion(
            Content::plainText('Explain the concept of object-oriented programming.'),
            QuestionType::essay(),
            EnhancedDifficultyLevel::medium()
        );

        // Valid essay answer
        $validAnswer = new QuestionAnswer(
            Id::fromInt(1),
            $essayQuestion,
            ['Object-oriented programming is a paradigm...'],
            300.0,
            []
        );

        $this->assertEquals(['Object-oriented programming is a paradigm...'], $validAnswer->getSubmittedAnswers());

        // Invalid essay answer (multiple strings)
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Essay and code completion questions must have exactly one string answer');

        new QuestionAnswer(
            Id::fromInt(2),
            $essayQuestion,
            ['First part', 'Second part'], // Multiple strings for essay
            300.0,
            []
        );
    }

    public function test_it_should_provide_feedback(): void
    {
        $questionAnswer = new QuestionAnswer(
            Id::fromInt(1),
            $this->question,
            ['correct_answer'],
            25.0,
            []
        );

        $feedback = $questionAnswer->getFeedback();
        
        $this->assertIsString($feedback);
        $this->assertNotEmpty($feedback);
    }
}