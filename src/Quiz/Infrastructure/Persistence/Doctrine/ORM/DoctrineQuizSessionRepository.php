<?php

declare(strict_types=1);

namespace App\Quiz\Infrastructure\Persistence\Doctrine\ORM;

use App\Quiz\Domain\Entity\QuizSession;
use App\Quiz\Domain\Repository\QuizSessionRepositoryInterface;
use App\Shared\Domain\ValueObject\Id;
use App\User\Domain\Entity\UserId;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Doctrine ORM implementation of QuizSessionRepositoryInterface.
 */
final class DoctrineQuizSessionRepository extends ServiceEntityRepository implements QuizSessionRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuizSession::class);
    }

    public function findById(Id $id): ?QuizSession
    {
        return $this->find($id->toString());
    }

    public function findActiveByUserId(UserId $userId): ?QuizSession
    {
        return $this->createQueryBuilder('qs')
            ->where('qs.userId = :userId')
            ->andWhere('qs.isCompleted = false')
            ->setParameter('userId', $userId->toString())
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByUserId(
        UserId $userId,
        ?bool $isCompleted = null,
        ?int $limit = null,
        ?int $offset = null
    ): array {
        $qb = $this->createQueryBuilder('qs')
            ->where('qs.userId = :userId')
            ->setParameter('userId', $userId->toString())
            ->orderBy('qs.startedAt', 'DESC');

        if ($isCompleted !== null) {
            $qb->andWhere('qs.isCompleted = :isCompleted')
               ->setParameter('isCompleted', $isCompleted);
        }

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }

        return $qb->getQuery()->getResult();
    }

    public function findByUserAndDateRange(
        UserId $userId,
        \DateTimeImmutable $fromDate,
        \DateTimeImmutable $toDate
    ): array {
        return $this->createQueryBuilder('qs')
            ->where('qs.userId = :userId')
            ->andWhere('qs.startedAt >= :fromDate')
            ->andWhere('qs.startedAt <= :toDate')
            ->setParameter('userId', $userId->toString())
            ->setParameter('fromDate', $fromDate)
            ->setParameter('toDate', $toDate)
            ->orderBy('qs.startedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(QuizSession $session): void
    {
        $this->getEntityManager()->persist($session);
        $this->getEntityManager()->flush();
    }

    public function delete(QuizSession $session): void
    {
        $this->getEntityManager()->remove($session);
        $this->getEntityManager()->flush();
    }

    public function getUserPerformanceStats(UserId $userId): array
    {
        $result = $this->createQueryBuilder('qs')
            ->select([
                'COUNT(qs.id) as totalSessions',
                'AVG(qs.score) as averageScore',
                'MAX(qs.score) as bestScore',
                'MIN(qs.score) as worstScore',
                'SUM(qs.totalTimeSpent) as totalTimeSpent',
                'AVG(qs.totalTimeSpent) as averageTimeSpent'
            ])
            ->where('qs.userId = :userId')
            ->andWhere('qs.isCompleted = true')
            ->setParameter('userId', $userId->toString())
            ->getQuery()
            ->getSingleResult();

        return [
            'totalSessions' => (int) $result['totalSessions'],
            'averageScore' => (float) $result['averageScore'] ?? 0.0,
            'bestScore' => (float) $result['bestScore'] ?? 0.0,
            'worstScore' => (float) $result['worstScore'] ?? 0.0,
            'totalTimeSpent' => (float) $result['totalTimeSpent'] ?? 0.0,
            'averageTimeSpent' => (float) $result['averageTimeSpent'] ?? 0.0,
        ];
    }

    public function getUserLearningAnalytics(
        UserId $userId,
        ?\DateTimeImmutable $fromDate = null,
        ?\DateTimeImmutable $toDate = null
    ): array {
        $qb = $this->createQueryBuilder('qs')
            ->where('qs.userId = :userId')
            ->andWhere('qs.isCompleted = true')
            ->setParameter('userId', $userId->toString())
            ->orderBy('qs.completedAt', 'DESC');

        if ($fromDate !== null) {
            $qb->andWhere('qs.completedAt >= :fromDate')
               ->setParameter('fromDate', $fromDate);
        }

        if ($toDate !== null) {
            $qb->andWhere('qs.completedAt <= :toDate')
               ->setParameter('toDate', $toDate);
        }

        $sessions = $qb->getQuery()->getResult();

        // Calculate learning analytics
        $analytics = [
            'sessions' => [],
            'progressTrend' => [],
            'difficultyProgression' => [],
            'categoryPerformance' => [],
            'timeSpentAnalysis' => [],
        ];

        foreach ($sessions as $session) {
            $sessionData = [
                'id' => $session->getId()->toString(),
                'score' => $session->getScore(),
                'timeSpent' => $session->getTotalTimeSpent(),
                'difficulty' => $session->getTargetDifficulty()->getLevel(),
                'completedAt' => $session->getCompletedAt(),
                'questionAnswers' => $session->getQuestionAnswers(),
            ];

            $analytics['sessions'][] = $sessionData;
            
            // Add to progress trend
            $analytics['progressTrend'][] = [
                'date' => $session->getCompletedAt()->format('Y-m-d'),
                'score' => $session->getScore(),
            ];

            // Track difficulty progression
            $difficultyLevel = $session->getTargetDifficulty()->getLevel();
            if (!isset($analytics['difficultyProgression'][$difficultyLevel])) {
                $analytics['difficultyProgression'][$difficultyLevel] = [];
            }
            $analytics['difficultyProgression'][$difficultyLevel][] = $session->getScore();
        }

        return $analytics;
    }

    public function getAdaptiveLearningData(
        UserId $userId,
        ?int $categoryId = null,
        int $limit = 100
    ): array {
        $qb = $this->createQueryBuilder('qs')
            ->where('qs.userId = :userId')
            ->andWhere('qs.isCompleted = true')
            ->setParameter('userId', $userId->toString())
            ->orderBy('qs.completedAt', 'DESC')
            ->setMaxResults($limit);

        $sessions = $qb->getQuery()->getResult();

        $adaptiveData = [
            'recentPerformance' => [],
            'knowledgeGaps' => [],
            'strongAreas' => [],
            'difficultyProfile' => [],
            'learningVelocity' => 0.0,
            'recommendedDifficulty' => 5,
        ];

        if (empty($sessions)) {
            return $adaptiveData;
        }

        // Analyze recent performance
        $recentScores = [];
        $difficultyPerformance = [];
        
        foreach ($sessions as $session) {
            $score = $session->getScore();
            $difficulty = $session->getTargetDifficulty()->getLevel();
            
            $recentScores[] = $score;
            
            if (!isset($difficultyPerformance[$difficulty])) {
                $difficultyPerformance[$difficulty] = [];
            }
            $difficultyPerformance[$difficulty][] = $score;
        }

        // Calculate learning velocity (improvement rate)
        if (count($recentScores) >= 2) {
            $early = array_slice($recentScores, -10, 5);
            $recent = array_slice($recentScores, -5);
            
            if (!empty($early) && !empty($recent)) {
                $earlyAvg = array_sum($early) / count($early);
                $recentAvg = array_sum($recent) / count($recent);
                $adaptiveData['learningVelocity'] = $recentAvg - $earlyAvg;
            }
        }

        // Determine knowledge gaps and strong areas
        foreach ($difficultyPerformance as $difficulty => $scores) {
            $avgScore = array_sum($scores) / count($scores);
            
            if ($avgScore < 60) {
                $adaptiveData['knowledgeGaps'][] = [
                    'difficulty' => $difficulty,
                    'averageScore' => $avgScore,
                    'attempts' => count($scores),
                ];
            } elseif ($avgScore > 80) {
                $adaptiveData['strongAreas'][] = [
                    'difficulty' => $difficulty,
                    'averageScore' => $avgScore,
                    'attempts' => count($scores),
                ];
            }
        }

        // Recommend next difficulty level
        $currentAverage = array_sum($recentScores) / count($recentScores);
        if ($currentAverage > 80) {
            $adaptiveData['recommendedDifficulty'] = min(10, max(array_keys($difficultyPerformance)) + 1);
        } elseif ($currentAverage < 60) {
            $adaptiveData['recommendedDifficulty'] = max(1, min(array_keys($difficultyPerformance)) - 1);
        } else {
            $adaptiveData['recommendedDifficulty'] = (int) round(array_sum(array_keys($difficultyPerformance)) / count($difficultyPerformance));
        }

        $adaptiveData['recentPerformance'] = array_slice($recentScores, -10);
        $adaptiveData['difficultyProfile'] = $difficultyPerformance;

        return $adaptiveData;
    }

    public function getSessionProgressAnalytics(Id $sessionId): array
    {
        $session = $this->findById($sessionId);
        
        if (!$session) {
            return [];
        }

        $analytics = [
            'totalQuestions' => $session->getTotalQuestions(),
            'answeredQuestions' => count($session->getQuestionAnswers()),
            'progress' => $session->getProgress(),
            'currentScore' => $session->getScore(),
            'timeSpent' => $session->getTotalTimeSpent(),
            'averageTimePerQuestion' => 0.0,
            'questionAnalytics' => [],
        ];

        $answers = $session->getQuestionAnswers();
        if (!empty($answers)) {
            $totalTime = array_reduce($answers, function ($sum, $answer) {
                return $sum + $answer->getTimeSpent();
            }, 0.0);
            
            $analytics['averageTimePerQuestion'] = $totalTime / count($answers);
            
            foreach ($answers as $answer) {
                $analytics['questionAnalytics'][] = [
                    'questionId' => $answer->getQuestion()->getId()->toString(),
                    'correct' => $answer->isCorrect(),
                    'score' => $answer->getScore(),
                    'timeSpent' => $answer->getTimeSpent(),
                    'difficulty' => $answer->getQuestion()->getDifficultyLevel()->getLevel(),
                ];
            }
        }

        return $analytics;
    }

    public function findSimilarUsers(UserId $userId, int $limit = 10): array
    {
        // Get current user's performance profile
        $userStats = $this->getUserPerformanceStats($userId);
        
        if ($userStats['totalSessions'] === 0) {
            return [];
        }

        // Find users with similar performance patterns
        // This is a simplified implementation - in practice, you'd use more sophisticated ML algorithms
        $averageScore = $userStats['averageScore'];
        $tolerance = 10.0; // 10% tolerance

        $qb = $this->createQueryBuilder('qs')
            ->select('qs.userId', 'AVG(qs.score) as avgScore', 'COUNT(qs.id) as sessionCount')
            ->where('qs.isCompleted = true')
            ->andWhere('qs.userId != :userId')
            ->groupBy('qs.userId')
            ->having('avgScore BETWEEN :minScore AND :maxScore')
            ->andHaving('sessionCount >= :minSessions')
            ->orderBy('ABS(avgScore - :targetScore)', 'ASC')
            ->setParameter('userId', $userId->toString())
            ->setParameter('minScore', $averageScore - $tolerance)
            ->setParameter('maxScore', $averageScore + $tolerance)
            ->setParameter('targetScore', $averageScore)
            ->setParameter('minSessions', max(1, $userStats['totalSessions'] * 0.5))
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }
}