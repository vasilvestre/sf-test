<?php

declare(strict_types=1);

namespace Tests\Quiz\Domain\Entity;

use App\Quiz\Domain\Entity\EnhancedAnswer;
use App\Quiz\Domain\Entity\EnhancedQuestion;
use App\Quiz\Domain\ValueObject\Content;
use App\Quiz\Domain\ValueObject\EnhancedDifficultyLevel;
use App\Quiz\Domain\ValueObject\QuestionType;
use App\Quiz\Domain\ValueObject\Tag;
use PHPUnit\Framework\TestCase;

/**
 * Test suite demonstrating the enhanced Quiz domain model capabilities.
 * Shows advanced features like rich content, multiple question types, and scoring.
 */
final class EnhancedQuizDomainTest extends TestCase
{
    public function test_can_create_multiple_choice_question_with_rich_content(): void
    {
        // Create rich markdown content with code snippet
        $questionContent = Content::markdown(
            "What is the time complexity of this algorithm?\n\n```php\nfor ($i = 0; $i < n; $i++) {\n    echo $i;\n}\n```",
            ['images' => [], 'videos' => []]
        );

        // Create question with advanced difficulty
        $question = EnhancedQuestion::multipleChoice(
            $questionContent,
            EnhancedDifficultyLevel::hard()
        );

        // Add tags for categorization
        $question->addTag(Tag::skill('algorithm-analysis'));
        $question->addTag(Tag::topic('time-complexity'));
        $question->addTag(Tag::language('php'));

        // Add explanation
        $explanation = Content::markdown(
            "This is a simple linear loop that executes n times, so the time complexity is **O(n)**."
        );
        $question->setExplanation($explanation);

        // Create answers with different correctness levels
        $correctAnswer = EnhancedAnswer::correct(Content::plainText('O(n)'));
        $partialAnswer = EnhancedAnswer::partialCredit(Content::plainText('Linear'), 50.0);
        $wrongAnswer1 = EnhancedAnswer::incorrect(Content::plainText('O(1)'));
        $wrongAnswer2 = EnhancedAnswer::incorrect(Content::plainText('O(n²)'));

        $question->addAnswer($correctAnswer);
        $question->addAnswer($partialAnswer);
        $question->addAnswer($wrongAnswer1);
        $question->addAnswer($wrongAnswer2);

        // Verify question structure
        self::assertTrue($question->getType()->isMultipleChoice());
        self::assertTrue($question->getDifficultyLevel()->isHard());
        self::assertCount(3, $question->getTags());
        self::assertCount(4, $question->getAnswers());
        self::assertCount(2, $question->getCorrectAnswers()); // Including partial credit
        self::assertTrue($question->isValid());
        self::assertTrue($question->supportsPartialCredit());
    }

    public function test_can_create_code_completion_question(): void
    {
        // Create code content with language specification
        $codeContent = Content::code(
            "Complete the missing method:\n\nclass User {\n    public function getName(): string {\n        // TODO: Implement\n    }\n}",
            'php'
        );

        $question = EnhancedQuestion::codeCompletion(
            $codeContent,
            EnhancedDifficultyLevel::expert()
        );

        // Add programming-specific tags
        $question->addTag(Tag::language('php'));
        $question->addTag(Tag::topic('object-oriented-programming'));
        $question->addTag(Tag::skill('method-implementation'));

        // Code completion questions require manual grading
        self::assertTrue($question->requiresManualGrading());
        self::assertTrue($question->getType()->isCodeCompletion());
        self::assertTrue($question->getContent()->isCode());
        self::assertEquals('php', $question->getContent()->getLanguage());
    }

    public function test_can_create_true_false_question_with_validation(): void
    {
        $question = EnhancedQuestion::trueFalse(
            Content::plainText('PHP is a compiled language.'),
            EnhancedDifficultyLevel::easy()
        );

        $trueAnswer = EnhancedAnswer::trueFalse(true);
        $falseAnswer = EnhancedAnswer::trueFalse(false);

        $question->addAnswer($trueAnswer);
        $question->addAnswer($falseAnswer);

        // Mark the false answer as correct
        $falseAnswer->markAsCorrect();

        self::assertTrue($question->getType()->isTrueFalse());
        self::assertCount(2, $question->getAnswers());
        self::assertCount(1, $question->getCorrectAnswers());
        self::assertTrue($question->isValid());

        // True/False questions cannot accept more than 2 answers
        self::assertFalse($question->canAddAnswer());
    }

    public function test_question_scoring_with_partial_credit(): void
    {
        $question = EnhancedQuestion::multipleChoice(
            Content::plainText('Which of the following are programming languages?'),
            EnhancedDifficultyLevel::medium()
        );

        // Create answers with different credit values
        $phpAnswer = EnhancedAnswer::correct(Content::plainText('PHP'));
        $pythonAnswer = EnhancedAnswer::correct(Content::plainText('Python'));
        $htmlAnswer = EnhancedAnswer::partialCredit(Content::plainText('HTML'), 25.0); // Partial credit
        $cssAnswer = EnhancedAnswer::incorrect(Content::plainText('CSS'));

        $question->addAnswer($phpAnswer);
        $question->addAnswer($pythonAnswer);
        $question->addAnswer($htmlAnswer);
        $question->addAnswer($cssAnswer);

        // Set question weight
        $question->updateScoringWeight(2.0);

        // Test scoring with user selecting PHP and Python (both correct)
        $userAnswers = [$phpAnswer->getId(), $pythonAnswer->getId()];
        $score = $question->calculateScore($userAnswers);

        self::assertEquals(2.0, $score->getMaxPoints());
        self::assertTrue($score->getPoints() > 0);
        self::assertTrue($score->getPercentage() > 50);
    }

    public function test_question_difficulty_progression(): void
    {
        $beginnerLevel = EnhancedDifficultyLevel::beginner();
        $expertLevel = EnhancedDifficultyLevel::expert();

        self::assertTrue($beginnerLevel->isEasierThan($expertLevel));
        self::assertTrue($expertLevel->isHarderThan($beginnerLevel));
        self::assertEquals(8, $beginnerLevel->distanceFrom($expertLevel));

        // Test difficulty adjustment
        $adjustedUp = $beginnerLevel->adjustBy(3);
        self::assertEquals(4, $adjustedUp->getLevel());

        $adjustedDown = $expertLevel->adjustBy(-2);
        self::assertEquals(7, $adjustedDown->getLevel());
    }

    public function test_content_types_and_metadata(): void
    {
        // Test different content types
        $markdownContent = Content::markdown('# Header\n\nSome **bold** text');
        $codeContent = Content::code('<?php echo "Hello"; ?>', 'php');
        $latexContent = Content::latex('$E = mc^2$');

        self::assertTrue($markdownContent->isMarkdown());
        self::assertTrue($codeContent->isCode());
        self::assertTrue($latexContent->isLatex());

        // Test content with metadata
        $contentWithImages = Content::markdown(
            'Question with image',
            ['images' => ['diagram.png'], 'videos' => ['explanation.mp4']]
        );

        self::assertTrue($contentWithImages->hasImages());
        self::assertTrue($contentWithImages->hasVideos());
        self::assertCount(1, $contentWithImages->getImages());
        self::assertCount(1, $contentWithImages->getVideos());
    }

    public function test_tag_system_and_categorization(): void
    {
        $skillTag = Tag::skill('problem-solving');
        $topicTag = Tag::topic('algorithms');
        $languageTag = Tag::language('javascript');
        $customTag = Tag::create('advanced', 'level');

        self::assertEquals('skill', $skillTag->getCategory());
        self::assertEquals('topic', $topicTag->getCategory());
        self::assertEquals('language', $languageTag->getCategory());
        self::assertEquals('level', $customTag->getCategory());

        self::assertTrue($skillTag->isInCategory('skill'));
        self::assertFalse($skillTag->isInCategory('topic'));

        self::assertEquals('problem-solving', $skillTag->getSlug());
        self::assertEquals('skill:problem-solving', $skillTag->toString());
    }

    public function test_enhanced_answer_features(): void
    {
        $answer1 = EnhancedAnswer::correct(Content::plainText('Correct answer'));
        $answer2 = EnhancedAnswer::partialCredit(Content::plainText('Partially correct'), 75.0);
        $answer3 = EnhancedAnswer::incorrect(Content::plainText('Wrong answer'));

        // Test correctness and credit
        self::assertTrue($answer1->isCorrect());
        self::assertEquals(100.0, $answer1->getCreditPercentage());

        self::assertTrue($answer2->isCorrect()); // Partial credit counts as correct
        self::assertTrue($answer2->hasPartialCredit());
        self::assertEquals(75.0, $answer2->getCreditPercentage());

        self::assertFalse($answer3->isCorrect());
        self::assertEquals(0.0, $answer3->getCreditPercentage());

        // Test feedback
        $feedback = Content::markdown('This is **correct** because...');
        $answer1->setFeedback($feedback);
        self::assertNotNull($answer1->getFeedback());
        self::assertTrue($answer1->getFeedback()->isMarkdown());

        // Test position management
        $answer1->setPosition(1);
        $answer2->setPosition(2);
        self::assertEquals(1, $answer1->getPosition());
        self::assertEquals(2, $answer2->getPosition());
    }

    public function test_domain_events_are_recorded(): void
    {
        $question = EnhancedQuestion::singleChoice(
            Content::plainText('Sample question'),
            EnhancedDifficultyLevel::medium()
        );

        // Check that creation event was recorded
        $events = $question->getRecordedEvents();
        self::assertCount(1, $events);
        self::assertEquals('quiz.question.created', $events[0]->getEventName());

        // Update difficulty and check event
        $question->updateDifficultyLevel(EnhancedDifficultyLevel::hard());
        $events = $question->getRecordedEvents();
        self::assertCount(2, $events);
        self::assertEquals('quiz.question.difficulty_changed', $events[1]->getEventName());

        // Add answer and check event
        $answer = EnhancedAnswer::correct(Content::plainText('Answer'));
        $question->addAnswer($answer);
        $events = $question->getRecordedEvents();
        self::assertCount(3, $events);
        self::assertEquals('quiz.question.answer_added', $events[2]->getEventName());
    }
}