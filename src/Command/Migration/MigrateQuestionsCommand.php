<?php

declare(strict_types=1);

namespace App\Command\Migration;

use App\Entity\Question as LegacyQuestion;
use App\Entity\Answer as LegacyAnswer;
use App\Quiz\Infrastructure\Persistence\Doctrine\ORM\Entity\Category;
use App\Quiz\Infrastructure\Persistence\Doctrine\ORM\Entity\Question;
use App\Quiz\Infrastructure\Persistence\Doctrine\ORM\Entity\QuestionType;
use App\Quiz\Infrastructure\Persistence\Doctrine\ORM\Entity\Answer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:migrate:questions',
    description: 'Migrate legacy questions to enhanced question structure with rich content'
)]
class MigrateQuestionsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Migrating Questions');

        // Get legacy questions
        $legacyQuestions = $this->entityManager->getRepository(LegacyQuestion::class)->findAll();
        
        if (empty($legacyQuestions)) {
            $io->warning('No legacy questions found to migrate');
            return Command::SUCCESS;
        }

        $io->progressStart(count($legacyQuestions));

        $multipleChoiceType = $this->entityManager->getRepository(QuestionType::class)
            ->find('multiple_choice');

        if (!$multipleChoiceType) {
            $io->error('Multiple choice question type not found. Please run the schema migration first.');
            return Command::FAILURE;
        }

        foreach ($legacyQuestions as $legacyQuestion) {
            $this->migrateQuestion($legacyQuestion, $multipleChoiceType, $io);
            $io->progressAdvance();
        }

        $io->progressFinish();
        $this->entityManager->flush();

        $io->success(sprintf('Successfully migrated %d questions', count($legacyQuestions)));
        
        return Command::SUCCESS;
    }

    private function migrateQuestion(LegacyQuestion $legacyQuestion, QuestionType $questionType, SymfonyStyle $io): void
    {
        try {
            // Find the migrated category
            $category = $this->findMigratedCategory($legacyQuestion->getCategory());
            
            if (!$category) {
                $io->warning(sprintf('Category not found for question "%s", skipping', 
                    substr($legacyQuestion->getText(), 0, 50)));
                return;
            }

            // Create rich content structure
            $content = [
                'type' => 'text',
                'text' => $legacyQuestion->getText(),
                'format' => 'html',
                'media' => [],
                'metadata' => [
                    'legacy_id' => $legacyQuestion->getId(),
                    'migrated_at' => (new \DateTimeImmutable())->format('c')
                ]
            ];

            $newQuestion = new Question(
                $category,
                $questionType,
                $content
            );

            // Set enhanced properties
            $newQuestion->setDifficultyLevel($this->estimateDifficulty($legacyQuestion));
            $newQuestion->setEstimatedTime($this->estimateTime($legacyQuestion));
            $newQuestion->setScoringWeight(1.0);
            $newQuestion->setTags($this->extractTags($legacyQuestion));
            $newQuestion->setIsActive(true);

            // Store migration metadata
            $newQuestion->setMetadata([
                'legacy_id' => $legacyQuestion->getId(),
                'migrated_at' => (new \DateTimeImmutable())->format('c'),
                'migration_version' => '1.0',
                'original_text_length' => strlen($legacyQuestion->getText()),
                'answer_count' => $legacyQuestion->getAnswers()->count()
            ]);

            $this->entityManager->persist($newQuestion);

            // Migrate answers
            $this->migrateAnswers($legacyQuestion, $newQuestion);
            
        } catch (\Exception $e) {
            $io->error(sprintf('Failed to migrate question "%s": %s', 
                substr($legacyQuestion->getText(), 0, 50), $e->getMessage()));
        }
    }

    private function migrateAnswers(LegacyQuestion $legacyQuestion, Question $newQuestion): void
    {
        $position = 0;
        
        foreach ($legacyQuestion->getAnswers() as $legacyAnswer) {
            $answerContent = [
                'type' => 'text',
                'text' => $legacyAnswer->getText(),
                'format' => 'html',
                'media' => [],
                'metadata' => [
                    'legacy_id' => $legacyAnswer->getId(),
                    'migrated_at' => (new \DateTimeImmutable())->format('c')
                ]
            ];

            $newAnswer = new Answer(
                $newQuestion,
                $answerContent,
                $legacyAnswer->isIsCorrect(),
                $position++
            );

            // Set enhanced properties
            $newAnswer->setScoreValue($legacyAnswer->isIsCorrect() ? 1.0 : 0.0);
            $newAnswer->setMetadata([
                'legacy_id' => $legacyAnswer->getId(),
                'migrated_at' => (new \DateTimeImmutable())->format('c'),
                'migration_version' => '1.0'
            ]);

            $this->entityManager->persist($newAnswer);
            $newQuestion->addAnswer($newAnswer);
        }
    }

    private function findMigratedCategory(\App\Entity\Category $legacyCategory): ?Category
    {
        // First try to find by metadata reference
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('c')
           ->from(Category::class, 'c')
           ->where('JSON_EXTRACT_PATH_TEXT(c.metadata, \'legacy_id\') = :legacyId')
           ->setParameter('legacyId', (string) $legacyCategory->getId());

        $result = $qb->getQuery()->getOneOrNullResult();
        
        if ($result) {
            return $result;
        }

        // Fallback: try to find by name
        return $this->entityManager->getRepository(Category::class)
            ->findOneBy(['name' => $legacyCategory->getName()]);
    }

    private function estimateDifficulty(LegacyQuestion $legacyQuestion): int
    {
        $text = $legacyQuestion->getText();
        $answerCount = $legacyQuestion->getAnswers()->count();
        
        // Simple heuristic based on text length and answer count
        $difficulty = 5; // default
        
        if (strlen($text) > 200) $difficulty += 1;
        if ($answerCount > 4) $difficulty += 1;
        if (str_contains(strtolower($text), 'complex') || str_contains(strtolower($text), 'advanced')) {
            $difficulty += 2;
        }
        if (str_contains(strtolower($text), 'basic') || str_contains(strtolower($text), 'simple')) {
            $difficulty -= 1;
        }
        
        return max(1, min(10, $difficulty));
    }

    private function estimateTime(LegacyQuestion $legacyQuestion): int
    {
        $textLength = strlen($legacyQuestion->getText());
        $answerCount = $legacyQuestion->getAnswers()->count();
        
        // Base time of 30 seconds
        $time = 30;
        
        // Add time based on text length (roughly 200 words per minute reading)
        $time += (int) ($textLength / 5); // ~5 chars per word, 200 words per minute
        
        // Add time for each answer option
        $time += $answerCount * 5;
        
        return max(15, min(300, $time)); // Between 15 seconds and 5 minutes
    }

    private function extractTags(LegacyQuestion $legacyQuestion): array
    {
        $text = strtolower($legacyQuestion->getText());
        $tags = [];
        
        // Extract common programming and technical terms as tags
        $keywords = [
            'php', 'symfony', 'doctrine', 'twig', 'javascript', 'css', 'html',
            'database', 'sql', 'mysql', 'postgresql', 'security', 'authentication',
            'authorization', 'testing', 'unit', 'integration', 'api', 'rest',
            'json', 'xml', 'cache', 'performance', 'optimization', 'architecture',
            'design', 'pattern', 'solid', 'mvc', 'crud', 'validation', 'form',
            'routing', 'middleware', 'bundle', 'service', 'dependency', 'injection'
        ];
        
        foreach ($keywords as $keyword) {
            if (str_contains($text, $keyword)) {
                $tags[] = $keyword;
            }
        }
        
        // Add category-based tag
        if ($legacyQuestion->getCategory()) {
            $tags[] = strtolower($legacyQuestion->getCategory()->getName());
        }
        
        return array_unique($tags);
    }
}