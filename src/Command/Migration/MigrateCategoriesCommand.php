<?php

declare(strict_types=1);

namespace App\Command\Migration;

use App\Entity\Category as LegacyCategory;
use App\Quiz\Infrastructure\Persistence\Doctrine\ORM\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\String\Slugger\SluggerInterface;

#[AsCommand(
    name: 'app:migrate:categories',
    description: 'Migrate legacy categories to enhanced category structure'
)]
class MigrateCategoriesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SluggerInterface $slugger
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Migrating Categories');

        // Get legacy categories
        $legacyCategories = $this->entityManager->getRepository(LegacyCategory::class)->findAll();
        
        if (empty($legacyCategories)) {
            $io->warning('No legacy categories found to migrate');
            return Command::SUCCESS;
        }

        $io->progressStart(count($legacyCategories));

        foreach ($legacyCategories as $legacyCategory) {
            $this->migrateCategory($legacyCategory, $io);
            $io->progressAdvance();
        }

        $io->progressFinish();
        $this->entityManager->flush();

        $io->success(sprintf('Successfully migrated %d categories', count($legacyCategories)));
        
        return Command::SUCCESS;
    }

    private function migrateCategory(LegacyCategory $legacyCategory, SymfonyStyle $io): void
    {
        try {
            $slug = $this->slugger->slug($legacyCategory->getName())->lower()->toString();
            
            // Check if category already exists
            $existingCategory = $this->entityManager->getRepository(Category::class)
                ->findOneBy(['slug' => $slug]);
                
            if ($existingCategory) {
                $io->note(sprintf('Category "%s" already exists, skipping', $legacyCategory->getName()));
                return;
            }

            $newCategory = new Category(
                $legacyCategory->getName(),
                $slug,
                $legacyCategory->getDescription()
            );

            // Set enhanced properties
            $newCategory->setDifficultyLevel(5); // Default difficulty
            $newCategory->setSortOrder(0);
            $newCategory->setIsActive(true);
            $newCategory->setIcon($this->getDefaultIcon($legacyCategory->getName()));
            $newCategory->setColor($this->getDefaultColor($legacyCategory->getName()));

            // Store legacy ID in metadata for reference
            $newCategory->setMetadata([
                'legacy_id' => $legacyCategory->getId(),
                'migrated_at' => (new \DateTimeImmutable())->format('c'),
                'migration_version' => '1.0'
            ]);

            $this->entityManager->persist($newCategory);
            
        } catch (\Exception $e) {
            $io->error(sprintf('Failed to migrate category "%s": %s', $legacyCategory->getName(), $e->getMessage()));
        }
    }

    private function getDefaultIcon(string $categoryName): string
    {
        $iconMap = [
            'php' => 'fab fa-php',
            'symfony' => 'fab fa-symfony',
            'javascript' => 'fab fa-js',
            'database' => 'fas fa-database',
            'security' => 'fas fa-shield-alt',
            'testing' => 'fas fa-vial',
            'architecture' => 'fas fa-building',
            'algorithms' => 'fas fa-code',
            'web' => 'fas fa-globe',
            'api' => 'fas fa-plug',
        ];

        $name = strtolower($categoryName);
        foreach ($iconMap as $keyword => $icon) {
            if (str_contains($name, $keyword)) {
                return $icon;
            }
        }

        return 'fas fa-book';
    }

    private function getDefaultColor(string $categoryName): string
    {
        $colorMap = [
            'php' => '#777BB4',
            'symfony' => '#000000',
            'javascript' => '#F7DF1E',
            'database' => '#336791',
            'security' => '#DC3545',
            'testing' => '#28A745',
            'architecture' => '#6C757D',
            'algorithms' => '#17A2B8',
            'web' => '#FD7E14',
            'api' => '#6F42C1',
        ];

        $name = strtolower($categoryName);
        foreach ($colorMap as $keyword => $color) {
            if (str_contains($name, $keyword)) {
                return $color;
            }
        }

        return '#007BFF';
    }
}