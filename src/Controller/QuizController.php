<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\QuizResult;
use App\Repository\CategoryRepository;
use App\Repository\QuestionRepository;
use App\Repository\QuizResultRepository;
use App\Service\QuizLoader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

#[Route('/quiz')]
class QuizController extends AbstractController
{
    public function __construct(
        #[Autowire(service: QuizLoader::class)] private readonly QuizLoader                         $quizLoader,
        #[Autowire(service: CategoryRepository::class)] private readonly CategoryRepository         $categoryRepository,
        #[Autowire(service: QuestionRepository::class)] private readonly QuestionRepository         $questionRepository,
        #[Autowire(service: EntityManagerInterface::class)] private readonly EntityManagerInterface $entityManager,
        #[Autowire(service: QuizResultRepository::class)] private readonly QuizResultRepository     $quizResultRepository,
        private readonly ChartBuilderInterface                                                      $chartBuilder
    ) {
    }

    #[Route('/', name: 'quiz_index')]
    public function index(): Response
    {
        // Load quizzes from configuration files
        $this->quizLoader->loadQuizzes();

        // Get all categories
        $categories = $this->categoryRepository->findAll();

        // Get quiz statistics
        $totalQuizzesTaken = $this->quizResultRepository->getTotalQuizzesTaken();
        $averageSuccessRate = $this->quizResultRepository->getAverageSuccessRate();

        // Get chart data
        $chartData = $this->quizResultRepository->getChartData();

        // Create chart using Symfony UX Chart.js
        $chart = $this->createChart($chartData);

        // Get category-specific statistics
        $categoryStats = [];
        foreach ($categories as $category) {
            $categoryStats[$category->getId()] = [
                'totalQuizzes' => $this->quizResultRepository->getTotalQuizzesTaken($category->getId()),
                'successRate' => $this->quizResultRepository->getAverageSuccessRate($category->getId()),
            ];
        }

        return $this->render('quiz/index.html.twig', [
            'categories' => $categories,
            'totalQuizzesTaken' => $totalQuizzesTaken,
            'averageSuccessRate' => $averageSuccessRate,
            'chart' => $chart,
            'categoryStats' => $categoryStats,
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
        $categoryId = $request->request->get('category_id');
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

        // Save the quiz result
        $quizResult = new QuizResult();
        $quizResult->setScore($score);
        $quizResult->setCorrectAnswers($correctAnswers);
        $quizResult->setTotalQuestions($totalQuestions);

        // Set category if it's a single category quiz
        $category = null;
        if ($categoryId) {
            $category = $this->categoryRepository->find($categoryId);
            if ($category) {
                $quizResult->setCategory($category);
            }
        }

        $this->entityManager->persist($quizResult);
        $this->entityManager->flush();

        // Get recent results for the chart
        $chartData = $this->quizResultRepository->getChartData($categoryId);

        // Create chart using Symfony UX Chart.js
        $chart = $this->createChart($chartData);

        // Get statistics
        $categoryStats = null;
        $overallSuccessRate = $this->quizResultRepository->getAverageSuccessRate();

        if ($category) {
            $categoryStats = [
                'totalQuizzes' => $this->quizResultRepository->getTotalQuizzesTaken($category->getId()),
                'successRate' => $this->quizResultRepository->getAverageSuccessRate($category->getId()),
                'name' => $category->getName()
            ];
        }

        // Get all categories for the filter dropdown
        $categories = $this->categoryRepository->findAll();

        return $this->render('quiz/result.html.twig', [
            'score' => $score,
            'correctAnswers' => $correctAnswers,
            'totalQuestions' => $totalQuestions,
            'chart' => $chart,
            'quizResult' => $quizResult,
            'categoryStats' => $categoryStats,
            'overallSuccessRate' => $overallSuccessRate,
            'categories' => $categories,
        ]);
    }


    /**
     * Create a Chart object for quiz results
     *
     * @param array $chartData The chart data from repository
     * @return Chart The configured chart
     */
    private function createChart(array $chartData): Chart
    {
        $chart = $this->chartBuilder->createChart(Chart::TYPE_LINE);

        // Prepare data
        $labels = [];
        $scores = [];

        foreach ($chartData as $item) {
            $labels[] = $item['createdAt'] instanceof \DateTimeInterface ? $item['createdAt']->format('Y-m-d') : $item['createdAt'];
            $scores[] = $item['score'];
        }

        // Set chart data
        $chart->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Quiz Score (%)',
                    'backgroundColor' => 'rgba(52, 152, 219, 0.1)',
                    'borderColor' => '#3498db',
                    'data' => $scores,
                    'fill' => true,
                    'tension' => 0.1,
                    'borderWidth' => 2,
                ],
            ],
        ]);

        // Set chart options
        $chart->setOptions([
            'responsive' => true,
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'max' => 100,
                    'title' => [
                        'display' => true,
                        'text' => 'Score (%)'
                    ]
                ],
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Date'
                    ]
                ]
            ],
            'plugins' => [
                'title' => [
                    'display' => true,
                    'text' => 'Quiz Performance Trend'
                ]
            ]
        ]);

        return $chart;
    }

    #[Route('/chart-data/{categoryId}', name: 'quiz_chart_data', methods: ['GET'])]
    public function getChartData(?int $categoryId = null): Response
    {
        // Get chart data for the specified category (or all categories if null)
        $chartData = $this->quizResultRepository->getChartData($categoryId);

        // Create chart using Symfony UX Chart.js
        $chart = $this->createChart($chartData);

        // Return the chart data as JSON
        return $this->json([
            'chart' => $chart->getOptions(),
            'data' => $chart->getData(),
        ]);
    }
}
