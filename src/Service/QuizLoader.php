<?php

namespace App\Service;

use App\Entity\Answer;
use App\Entity\Category;
use App\Entity\Question;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

class QuizLoader
{
    private string $quizzesDir;

    public function __construct(
        #[Autowire(service: EntityManagerInterface::class)]
        private EntityManagerInterface $entityManager,
        #[Autowire(param: 'kernel.project_dir')]
        string $projectDir
    ) {
        $this->quizzesDir = $projectDir . '/config/quizzes';
    }

    /**
     * Load all quizzes from configuration files
     */
    public function loadQuizzes(): void
    {
        // Create quizzes directory if it doesn't exist
        if (!is_dir($this->quizzesDir)) {
            mkdir($this->quizzesDir, 0755, true);
        }

        $finder = new Finder();
        $finder->files()->in($this->quizzesDir)->name(['*.yaml', '*.yml']);

        foreach ($finder as $file) {
            $this->loadQuizFromFile($file->getRealPath());
        }
    }

    /**
     * Load a quiz from a configuration file
     */
    private function loadQuizFromFile(string $filePath): void
    {
        $quizData = Yaml::parseFile($filePath);

        if (!isset($quizData['category_name'])) {
            $quizData['category_name'] = $quizData['category'];
        }

        // Handle both 'questions' and direct question array
        if (isset($quizData['questions']) && is_array($quizData['questions'])) {
            $questions = $quizData['questions'];
        } else {
            throw new \InvalidArgumentException("Quiz file must contain a 'questions' array");
        }

        // Normalize question format
        foreach ($questions as $key => $questionData) {
            // Handle 'question' vs 'text'
            if (isset($questionData['question']) && !isset($questionData['text'])) {
                $questions[$key]['text'] = $questionData['question'];
            }

            // Handle answers format
            if (isset($questionData['answers']) && is_array($questionData['answers'])) {
                foreach ($questionData['answers'] as $answerKey => $answerData) {
                    // Handle 'value' vs 'text'
                    if (isset($answerData['value']) && !isset($answerData['text'])) {
                        $questions[$key]['answers'][$answerKey]['text'] = $answerData['value'];
                    }
                }
            }
        }

        $quizData['questions'] = $questions;

        // Find or create the category
        $category = $this->entityManager->getRepository(Category::class)
            ->findOneBy(['name' => $quizData['category_name']]);

        if (!$category) {
            $category = new Category();
            $category->setName($quizData['category_name']);

            if (isset($quizData['category_description'])) {
                $category->setDescription($quizData['category_description']);
            }

            $this->entityManager->persist($category);
        }

        // Process questions
        foreach ($quizData['questions'] as $questionData) {
            if (!isset($questionData['text']) || !isset($questionData['question']) || !isset($questionData['answers']) || !is_array($questionData['answers'])) {
                continue;
            }

            // Check if question already exists
            $existingQuestion = $this->entityManager->getRepository(Question::class)
                ->findOneBy([
                    'text' => $questionData['text'] ?? $questionData['question'],
                    'category' => $category
                ]);

            if ($existingQuestion) {
                continue;
            }

            $question = new Question();
            $question->setText($questionData['text']);
            $question->setCategory($category);

            $this->entityManager->persist($question);

            // Process answers
            foreach ($questionData['answers'] as $answerData) {
                if (!isset($answerData['text']) || !isset($answerData['correct'])) {
                    continue;
                }

                $answer = new Answer();
                $answer->setText($answerData['text']);
                $answer->setIsCorrect((bool) $answerData['correct']);
                $answer->setQuestion($question);

                $this->entityManager->persist($answer);
            }
        }

        $this->entityManager->flush();
    }
}
