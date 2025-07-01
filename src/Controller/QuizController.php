<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Question;
use App\Repository\CategoryRepository;
use App\Repository\QuestionRepository;
use App\Service\QuizLoader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/quiz')]
class QuizController extends AbstractController
{
    public function __construct(
        #[Autowire(service: QuizLoader::class)] private QuizLoader $quizLoader,
        #[Autowire(service: CategoryRepository::class)] private CategoryRepository $categoryRepository,
        #[Autowire(service: QuestionRepository::class)] private QuestionRepository $questionRepository
    ) {
    }

    #[Route('/', name: 'quiz_index')]
    public function index(): Response
    {
        // Load quizzes from configuration files
        $this->quizLoader->loadQuizzes();

        // Get all categories
        $categories = $this->categoryRepository->findAll();

        return $this->render('quiz/index.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/category/{id}', name: 'quiz_category')]
    public function category(Category $category): Response
    {
        return $this->render('quiz/quiz.html.twig', [
            'category' => $category,
            'questions' => $category->getQuestions(),
            'singleCategory' => true,
        ]);
    }

    #[Route('/all', name: 'quiz_all')]
    public function all(): Response
    {
        $categories = $this->categoryRepository->findAllWithQuestionsAndAnswers();

        $allQuestions = [];
        foreach ($categories as $category) {
            foreach ($category->getQuestions() as $question) {
                $allQuestions[] = $question;
            }
        }

        return $this->render('quiz/quiz.html.twig', [
            'categories' => $categories,
            'questions' => $allQuestions,
            'singleCategory' => false,
        ]);
    }

    #[Route('/submit', name: 'quiz_submit', methods: ['POST'])]
    public function submit(Request $request): Response
    {
        $answers = $request->request->all('answers');
        $correctAnswers = 0;
        $totalQuestions = count($answers);

        foreach ($answers as $questionId => $answerId) {
            $question = $this->questionRepository->find($questionId);
            if (!$question) {
                continue;
            }

            foreach ($question->getAnswers() as $answer) {
                if ($answer->getId() == $answerId && $answer->isIsCorrect()) {
                    $correctAnswers++;
                    break;
                }
            }
        }

        $score = $totalQuestions > 0 ? ($correctAnswers / $totalQuestions) * 100 : 0;

        return $this->render('quiz/result.html.twig', [
            'score' => $score,
            'correctAnswers' => $correctAnswers,
            'totalQuestions' => $totalQuestions,
        ]);
    }
}
