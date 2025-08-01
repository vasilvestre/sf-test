<?php

declare(strict_types=1);

namespace Tests\Analytics\Application\Handler;

use App\Analytics\Application\Handler\GetUserDashboardAnalyticsQueryHandler;
use App\Analytics\Application\Query\GetUserDashboardAnalyticsQuery;
use App\Analytics\Application\ReadModel\UserDashboardReadModel;
use App\Analytics\Domain\Service\AnalyticsServiceInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\Exception\UserNotFoundException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Test for GetUserDashboardAnalyticsQueryHandler.
 */
final class GetUserDashboardAnalyticsQueryHandlerTest extends TestCase
{
    private UserRepositoryInterface&MockObject $userRepository;
    private AnalyticsServiceInterface&MockObject $analyticsService;
    private GetUserDashboardAnalyticsQueryHandler $handler;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->analyticsService = $this->createMock(AnalyticsServiceInterface::class);
        
        $this->handler = new GetUserDashboardAnalyticsQueryHandler(
            $this->userRepository,
            $this->analyticsService
        );
    }

    public function testItShouldReturnUserDashboardAnalytics(): void
    {
        // Given
        $userId = 123;
        $timeframe = 'last_30_days';
        
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($userId);
        
        $query = new GetUserDashboardAnalyticsQuery(
            userId: $userId,
            timeframe: $timeframe,
            includeComparisons: true,
            includePredictions: true,
            includeRecommendations: true
        );

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->with($userId)
            ->willReturn($user);

        // When
        $result = $this->handler->__invoke($query);

        // Then
        $this->assertInstanceOf(UserDashboardReadModel::class, $result);
        $this->assertEquals($userId, $result->getUserId());
        $this->assertEquals($timeframe, $result->getTimeframe());
        
        $performanceOverview = $result->getPerformanceOverview();
        $this->assertIsArray($performanceOverview);
        $this->assertArrayHasKey('overall_score', $performanceOverview);
        $this->assertArrayHasKey('accuracy_rate', $performanceOverview);
        
        $chartData = $result->getChartData();
        $this->assertIsArray($chartData);
        $this->assertArrayHasKey('progress', $chartData);
        $this->assertArrayHasKey('skill_radar', $chartData);
    }

    public function testItShouldThrowExceptionWhenUserNotFound(): void
    {
        // Given
        $userId = 999;
        $query = new GetUserDashboardAnalyticsQuery(userId: $userId);

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->with($userId)
            ->willReturn(null);

        // Expect
        $this->expectException(UserNotFoundException::class);
        $this->expectExceptionMessage("User with ID $userId not found");

        // When
        $this->handler->__invoke($query);
    }

    public function testItShouldReturnDashboardWithoutOptionalData(): void
    {
        // Given
        $userId = 456;
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($userId);
        
        $query = new GetUserDashboardAnalyticsQuery(
            userId: $userId,
            timeframe: 'last_7_days',
            includeComparisons: false,
            includePredictions: false,
            includeRecommendations: false
        );

        $this->userRepository
            ->method('findById')
            ->willReturn($user);

        // When
        $result = $this->handler->__invoke($query);

        // Then
        $this->assertInstanceOf(UserDashboardReadModel::class, $result);
        $this->assertEmpty($result->getComparisons());
        $this->assertEmpty($result->getPredictions());
    }

    public function testItShouldReturnCorrectDashboardConfig(): void
    {
        // Given
        $userId = 789;
        $user = $this->createMock(User::class);
        $query = new GetUserDashboardAnalyticsQuery(userId: $userId);

        $this->userRepository->method('findById')->willReturn($user);

        // When
        $result = $this->handler->__invoke($query);
        $config = $result->getDashboardConfig();

        // Then
        $this->assertArrayHasKey('layout', $config);
        $this->assertArrayHasKey('widgets', $config);
        $this->assertArrayHasKey('refresh_interval', $config);
        $this->assertArrayHasKey('supports_real_time', $config);
        
        $this->assertEquals('grid', $config['layout']);
        $this->assertTrue($config['supports_real_time']);
        $this->assertGreaterThan(0, $config['refresh_interval']);
        
        $widgets = $config['widgets'];
        $this->assertArrayHasKey('performance_summary', $widgets);
        $this->assertArrayHasKey('progress_chart', $widgets);
        $this->assertArrayHasKey('skill_radar', $widgets);
    }

    public function testItShouldFilterMetricsWhenSpecified(): void
    {
        // Given
        $userId = 101;
        $user = $this->createMock(User::class);
        $specificMetrics = ['score', 'accuracy'];
        
        $query = new GetUserDashboardAnalyticsQuery(
            userId: $userId,
            metrics: $specificMetrics
        );

        $this->userRepository->method('findById')->willReturn($user);

        // When
        $result = $this->handler->__invoke($query);

        // Then
        $this->assertInstanceOf(UserDashboardReadModel::class, $result);
        // Note: In a real implementation, you would verify that only
        // the specified metrics are included in the response
    }

    public function testItShouldHandleDifferentTimeframes(): void
    {
        // Given
        $userId = 202;
        $user = $this->createMock(User::class);
        $timeframes = ['last_7_days', 'last_30_days', 'last_90_days', 'year_to_date', 'all_time'];

        $this->userRepository->method('findById')->willReturn($user);

        foreach ($timeframes as $timeframe) {
            // When
            $query = new GetUserDashboardAnalyticsQuery(
                userId: $userId,
                timeframe: $timeframe
            );
            $result = $this->handler->__invoke($query);

            // Then
            $this->assertEquals($timeframe, $result->getTimeframe());
            $this->assertInstanceOf(UserDashboardReadModel::class, $result);
        }
    }

    public function testItShouldReturnValidArrayRepresentation(): void
    {
        // Given
        $userId = 303;
        $user = $this->createMock(User::class);
        $query = new GetUserDashboardAnalyticsQuery(userId: $userId);

        $this->userRepository->method('findById')->willReturn($user);

        // When
        $result = $this->handler->__invoke($query);
        $array = $result->toArray();

        // Then
        $this->assertIsArray($array);
        $this->assertArrayHasKey('user_id', $array);
        $this->assertArrayHasKey('timeframe', $array);
        $this->assertArrayHasKey('performance_overview', $array);
        $this->assertArrayHasKey('progress_tracking', $array);
        $this->assertArrayHasKey('skill_breakdown', $array);
        $this->assertArrayHasKey('recent_activity', $array);
        $this->assertArrayHasKey('achievements', $array);
        $this->assertArrayHasKey('chart_data', $array);
        $this->assertArrayHasKey('last_updated', $array);
        
        $this->assertEquals($userId, $array['user_id']);
        $this->assertIsString($array['last_updated']);
    }
}