<?php

declare(strict_types=1);

namespace App\Command\Migration;

use App\Entity\QuizResult as LegacyQuizResult;
use App\Quiz\Infrastructure\Persistence\Doctrine\ORM\Entity\Quiz;
use App\Quiz\Infrastructure\Persistence\Doctrine\ORM\Entity\QuizAttempt;
use App\Quiz\Infrastructure\Persistence\Doctrine\ORM\Entity\UserAnswer;
use App\Quiz\Infrastructure\Persistence\Doctrine\ORM\Entity\Question;
use App\User\Infrastructure\Persistence\Doctrine\ORM\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:migrate:quiz-results',
    description: 'Migrate legacy quiz results to enhanced quiz attempt structure'
)]
class MigrateQuizResultsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Migrating Quiz Results');

        // Get legacy quiz results
        $legacyResults = $this->entityManager->getRepository(LegacyQuizResult::class)->findAll();
        
        if (empty($legacyResults)) {
            $io->warning('No legacy quiz results found to migrate');
            return Command::SUCCESS;
        }

        $io->progressStart(count($legacyResults));

        // Create or get default anonymous user for legacy results
        $anonymousUser = $this->getOrCreateAnonymousUser();

        foreach ($legacyResults as $legacyResult) {
            $this->migrateQuizResult($legacyResult, $anonymousUser, $io);
            $io->progressAdvance();
        }

        $io->progressFinish();
        $this->entityManager->flush();

        $io->success(sprintf('Successfully migrated %d quiz results', count($legacyResults)));
        
        return Command::SUCCESS;
    }

    private function migrateQuizResult(LegacyQuizResult $legacyResult, User $user, SymfonyStyle $io): void
    {
        try {
            // Create a synthetic quiz for this result
            $quiz = $this->createSyntheticQuiz($legacyResult);
            
            if (!$quiz) {
                $io->warning(sprintf('Could not create quiz for result ID %d, skipping', $legacyResult->getId()));
                return;
            }

            // Create quiz attempt
            $attempt = new QuizAttempt($user, $quiz, 1);
            $attempt->setStartedAt($legacyResult->getCreatedAt());
            $attempt->setCompletedAt($legacyResult->getCreatedAt());
            $attempt->setFinalScore($legacyResult->getScore());
            $attempt->setMaxPossibleScore((float) $legacyResult->getTotalQuestions());
            $attempt->setPercentageScore($legacyResult->getScore() / $legacyResult->getTotalQuestions() * 100);
            $attempt->setStatus('completed');
            
            // Calculate estimated time based on question count
            $estimatedTime = $legacyResult->getTotalQuestions() * 45; // 45 seconds per question
            $attempt->setTimeSpent($estimatedTime);

            // Set metadata with legacy information
            $attempt->setMetadata([
                'legacy_id' => $legacyResult->getId(),
                'legacy_category_id' => $legacyResult->getCategory()?->getId(),
                'migrated_at' => (new \DateTimeImmutable())->format('c'),
                'migration_version' => '1.0',
                'is_synthetic' => true,
                'legacy_questions_data' => $legacyResult->getQuestionsData()
            ]);

            $this->entityManager->persist($attempt);

            // Create synthetic user answers if questions data exists
            $this->createSyntheticUserAnswers($legacyResult, $attempt);
            
        } catch (\Exception $e) {
            $io->error(sprintf('Failed to migrate quiz result ID %d: %s', $legacyResult->getId(), $e->getMessage()));
        }
    }

    private function getOrCreateAnonymousUser(): User
    {
        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => 'anonymous@legacy-migration.local']);
            
        if (!$user) {
            $user = new User(
                'anonymous@legacy-migration.local',
                'anonymous_legacy',
                password_hash('random_password_' . uniqid(), PASSWORD_DEFAULT),
                ['ROLE_STUDENT']
            );
            
            $this->entityManager->persist($user);
        }
        
        return $user;
    }

    private function createSyntheticQuiz(LegacyQuizResult $legacyResult): ?Quiz
    {
        $category = $legacyResult->getCategory();
        
        if (!$category) {
            return null;
        }

        // Try to find existing synthetic quiz for this category
        $existingQuiz = $this->entityManager->getRepository(Quiz::class)
            ->createQueryBuilder('q')
            ->where('JSON_EXTRACT_PATH_TEXT(q.configuration, \'is_synthetic\') = :synthetic')
            ->andWhere('JSON_EXTRACT_PATH_TEXT(q.configuration, \'legacy_category_id\') = :categoryId')
            ->setParameter('synthetic', 'true')
            ->setParameter('categoryId', (string) $category->getId())
            ->getQuery()
            ->getOneOrNullResult();

        if ($existingQuiz) {
            return $existingQuiz;
        }

        // Create new synthetic quiz
        $quiz = new Quiz(
            sprintf('Legacy Quiz - %s', $category->getName()),
            null,
            sprintf('Synthetic quiz created from legacy quiz results for category: %s', $category->getName())
        );

        $quiz->setPassingScore(70);
        $quiz->setIsPublished(false);
        $quiz->setConfiguration([
            'is_synthetic' => true,
            'legacy_category_id' => $category->getId(),
            'created_from_migration' => true,
            'migration_date' => (new \DateTimeImmutable())->format('c')
        ]);

        $this->entityManager->persist($quiz);
        
        return $quiz;
    }

    private function createSyntheticUserAnswers(LegacyQuizResult $legacyResult, QuizAttempt $attempt): void
    {
        $questionsData = $legacyResult->getQuestionsData();
        
        if (!is_array($questionsData)) {
            return;
        }

        foreach ($questionsData as $index => $questionData) {
            // Try to find the migrated question if we have enough data
            $question = $this->findMigratedQuestion($questionData);
            
            if (!$question) {
                continue;
            }

            $userAnswer = new UserAnswer($attempt, $question);
            
            // Set answer data based on what's available
            if (isset($questionData['selected_answers'])) {
                $userAnswer->setAnswerIds($questionData['selected_answers']);
            }
            
            if (isset($questionData['is_correct'])) {
                $userAnswer->setIsCorrect($questionData['is_correct']);
                $userAnswer->setScoreEarned($questionData['is_correct'] ? 1.0 : 0.0);
            }
            
            // Estimate time spent per question
            $estimatedTime = 45; // 45 seconds default
            $userAnswer->setTimeSpent($estimatedTime);
            
            $this->entityManager->persist($userAnswer);
            $attempt->addUserAnswer($userAnswer);
        }
    }

    private function findMigratedQuestion(array $questionData): ?Question
    {
        if (!isset($questionData['question_id'])) {
            return null;
        }

        // Try to find by legacy ID in metadata
        return $this->entityManager->getRepository(Question::class)
            ->createQueryBuilder('q')
            ->where('JSON_EXTRACT_PATH_TEXT(q.metadata, \'legacy_id\') = :legacyId')
            ->setParameter('legacyId', (string) $questionData['question_id'])
            ->getQuery()
            ->getOneOrNullResult();
    }
}