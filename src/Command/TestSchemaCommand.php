<?php

declare(strict_types=1);

namespace App\Command;

use App\Quiz\Infrastructure\Persistence\Doctrine\ORM\Entity\Category;
use App\Quiz\Infrastructure\Persistence\Doctrine\ORM\Entity\Question;
use App\Quiz\Infrastructure\Persistence\Doctrine\ORM\Entity\QuestionType;
use App\Quiz\Infrastructure\Persistence\Doctrine\ORM\Entity\Answer;
use App\User\Infrastructure\Persistence\Doctrine\ORM\Entity\User;
use App\User\Infrastructure\Persistence\Doctrine\ORM\Entity\UserProfile;
use App\User\Infrastructure\Persistence\Doctrine\ORM\Entity\UserPreferences;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:database:test-schema',
    description: 'Test the new database schema with sample data'
)]
class TestSchemaCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Testing Database Schema');

        try {
            // Test User Domain
            $io->section('Testing User Domain');
            $user = $this->createTestUser($io);

            // Test Quiz Domain
            $io->section('Testing Quiz Domain');
            $this->createTestQuizData($user, $io);

            // Test Relationships
            $io->section('Testing Relationships');
            $this->testRelationships($user, $io);

            $this->entityManager->flush();

            $io->success('Database schema test completed successfully!');
            
        } catch (\Exception $e) {
            $io->error('Schema test failed: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function createTestUser(SymfonyStyle $io): User
    {
        $io->text('Creating test user...');

        $user = new User(
            'test@example.com',
            'testuser',
            password_hash('password123', PASSWORD_DEFAULT)
        );

        $this->entityManager->persist($user);
        $this->entityManager->flush(); // Flush to get the ID

        // Create user profile
        $profile = new UserProfile();
        $profile->setUser($user);
        $profile->setFirstName('Test');
        $profile->setLastName('User');
        $profile->setBio('This is a test user for schema validation');
        $profile->setTimezone('Europe/Paris');
        $profile->setLocale('en');

        $this->entityManager->persist($profile);
        $user->setProfile($profile);

        // Create user preferences
        $preferences = new UserPreferences();
        $preferences->setUser($user);
        $preferences->setDifficultyPreference(7);
        $preferences->setTheme('dark');
        $preferences->setNotificationsEnabled(true);
        $preferences->setAutoAdvance(false);
        $preferences->setShowExplanations(true);
        $preferences->setSoundEnabled(false);
        $preferences->setPreferencesJson([
            'custom_setting_1' => 'value1',
            'custom_setting_2' => true
        ]);

        $this->entityManager->persist($preferences);
        $user->setPreferences($preferences);

        $io->success('✓ Test user created successfully');
        
        return $user;
    }

    private function createTestQuizData(User $user, SymfonyStyle $io): void
    {
        $io->text('Creating test quiz data...');

        // Create test category
        $category = new Category(
            'Test Category',
            'test-category',
            'A test category for schema validation'
        );
        $category->setDifficultyLevel(5);
        $category->setIcon('fas fa-test');
        $category->setColor('#007BFF');
        $category->setMetadata(['test' => true, 'created_by' => 'test_command']);

        $this->entityManager->persist($category);

        // Get question type
        $questionType = $this->entityManager->getRepository(QuestionType::class)
            ->find('multiple_choice');

        if (!$questionType) {
            throw new \RuntimeException('Multiple choice question type not found');
        }

        // Create test question
        $question = new Question(
            $category,
            $questionType,
            [
                'type' => 'text',
                'text' => 'What is the capital of France?',
                'format' => 'html',
                'media' => [],
                'metadata' => ['test' => true]
            ],
            $user
        );

        $question->setDifficultyLevel(3);
        $question->setEstimatedTime(30);
        $question->setScoringWeight(1.0);
        $question->setTags(['geography', 'capital', 'france']);
        $question->setExplanation([
            'type' => 'text',
            'text' => 'Paris is the capital and largest city of France.',
            'format' => 'html'
        ]);
        $question->setMetadata(['test' => true, 'created_by' => 'test_command']);

        $this->entityManager->persist($question);

        // Create test answers
        $answers = [
            ['Paris', true],
            ['London', false],
            ['Berlin', false],
            ['Madrid', false]
        ];

        foreach ($answers as $index => [$text, $isCorrect]) {
            $answer = new Answer(
                $question,
                [
                    'type' => 'text',
                    'text' => $text,
                    'format' => 'html',
                    'metadata' => ['test' => true]
                ],
                $isCorrect,
                $index
            );

            $answer->setScoreValue($isCorrect ? 1.0 : 0.0);
            $answer->setMetadata(['test' => true]);

            $this->entityManager->persist($answer);
            $question->addAnswer($answer);
        }

        $io->success('✓ Test quiz data created successfully');
    }

    private function testRelationships(User $user, SymfonyStyle $io): void
    {
        $io->text('Testing entity relationships...');

        // Test user relationships
        $profile = $user->getProfile();
        if (!$profile || $profile->getUser() !== $user) {
            throw new \RuntimeException('User profile relationship failed');
        }

        $preferences = $user->getPreferences();
        if (!$preferences || $preferences->getUser() !== $user) {
            throw new \RuntimeException('User preferences relationship failed');
        }

        // Test question-answer relationships
        $questions = $this->entityManager->getRepository(Question::class)
            ->findBy(['createdBy' => $user]);

        if (empty($questions)) {
            throw new \RuntimeException('No questions found for test user');
        }

        $question = $questions[0];
        $answers = $question->getAnswers();

        if ($answers->count() !== 4) {
            throw new \RuntimeException('Expected 4 answers, got ' . $answers->count());
        }

        $correctAnswers = $question->getCorrectAnswers();
        if ($correctAnswers->count() !== 1) {
            throw new \RuntimeException('Expected 1 correct answer, got ' . $correctAnswers->count());
        }

        // Test category relationships
        $category = $question->getCategory();
        if (!$category) {
            throw new \RuntimeException('Question category relationship failed');
        }

        $categoryQuestions = $category->getQuestions();
        if (!$categoryQuestions->contains($question)) {
            throw new \RuntimeException('Category questions relationship failed');
        }

        $io->success('✓ All entity relationships working correctly');
    }
}