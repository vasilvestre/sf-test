# Analytics API Documentation

## Overview

The Analytics API provides comprehensive dashboard and analytics capabilities for the quiz application. It offers real-time metrics, user performance tracking, learning progress insights, and administrative analytics.

## Base URL

```
/api/analytics
```

## Authentication

All endpoints require authentication. Include the JWT token in the Authorization header:

```
Authorization: Bearer <your-jwt-token>
```

## Endpoints

### 1. User Dashboard Analytics

**GET** `/api/analytics/dashboard/user`

Get comprehensive user dashboard analytics including performance overview, progress tracking, and personalized insights.

#### Query Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `timeframe` | string | `last_30_days` | Time period for analytics (`last_7_days`, `last_30_days`, `last_90_days`, `year_to_date`, `all_time`) |
| `include_comparisons` | boolean | `true` | Include peer and global comparisons |
| `include_predictions` | boolean | `true` | Include performance predictions |
| `include_recommendations` | boolean | `true` | Include personalized recommendations |
| `metrics` | string | - | Comma-separated list of specific metrics to include |

#### Response

```json
{
  "success": true,
  "data": {
    "user_id": 123,
    "timeframe": "last_30_days",
    "performance_overview": {
      "overall_score": 78.5,
      "accuracy_rate": 82.3,
      "completion_rate": 89.7,
      "time_efficiency": 76.1,
      "improvement_rate": 12.4,
      "current_streak": 7,
      "longest_streak": 15,
      "total_quizzes": 45,
      "total_questions": 680,
      "correct_answers": 560,
      "performance_grade": "B+",
      "rank_percentile": 68
    },
    "progress_tracking": {
      "daily_progress": [...],
      "weekly_summary": {...},
      "goals": [...]
    },
    "skill_breakdown": {
      "categories": {...},
      "mastery_distribution": {...},
      "improvement_areas": [...]
    },
    "recent_activity": [...],
    "achievements": {...},
    "recommendations": [...],
    "comparisons": {...},
    "predictions": {...},
    "chart_data": {...},
    "last_updated": "2024-01-04T15:30:00Z"
  },
  "dashboard_config": {
    "layout": "grid",
    "widgets": {...},
    "refresh_interval": 300,
    "supports_real_time": true
  },
  "cache_ttl": 300
}
```

### 2. Learning Progress Dashboard

**GET** `/api/analytics/dashboard/learning-progress`

Get detailed learning progress analytics including goals, skill assessment, and learning path recommendations.

#### Query Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `timeframe` | string | `last_30_days` | Time period for analytics |
| `category_id` | integer | - | Filter by specific category |
| `include_goals` | boolean | `true` | Include goal tracking data |
| `include_skills` | boolean | `true` | Include skill assessment |
| `include_path` | boolean | `true` | Include learning path recommendations |
| `include_weak_areas` | boolean | `true` | Include weak area analysis |

#### Response

```json
{
  "success": true,
  "data": {
    "user_id": 123,
    "timeframe": "last_30_days",
    "learning_goals": [...],
    "skill_assessment": {...},
    "learning_path": {...},
    "weak_areas": [...],
    "strength_areas": [...],
    "recommendations": [...],
    "progress_metrics": {...},
    "milestones": [...],
    "competency_map": {...},
    "last_updated": "2024-01-04T15:30:00Z"
  },
  "progress_summary": {
    "total_goals": 3,
    "completed_goals": 1,
    "completion_rate": 33.3,
    "current_level": "Intermediate",
    "next_milestone": {...},
    "days_active": 28,
    "streak_current": 7,
    "streak_longest": 15
  },
  "prioritized_recommendations": [...],
  "dashboard_config": {...},
  "cache_ttl": 600
}
```

### 3. Admin Dashboard Analytics

**GET** `/api/analytics/dashboard/admin`

**Required Role:** `ROLE_ADMIN`

Get comprehensive administrative analytics including system overview, user engagement, and performance metrics.

#### Query Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `timeframe` | string | `last_30_days` | Time period for analytics |
| `include_engagement` | boolean | `true` | Include user engagement metrics |
| `include_performance` | boolean | `true` | Include system performance data |
| `include_content` | boolean | `true` | Include content analytics |
| `include_trends` | boolean | `true` | Include trend analysis |
| `include_export` | boolean | `false` | Include export data metadata |
| `organization_id` | integer | - | Filter by organization (multi-tenant) |

#### Response

```json
{
  "success": true,
  "data": {
    "timeframe": "last_30_days",
    "system_overview": {
      "total_users": 2847,
      "active_users": 1923,
      "new_users_today": 23,
      "total_quizzes": 8452,
      "average_session_duration": 24.5,
      "user_satisfaction_score": 8.7,
      "system_health_score": 98.2,
      "uptime_percentage": 99.8
    },
    "user_engagement": {...},
    "content_analytics": {...},
    "performance_metrics": {...},
    "trend_analysis": {...},
    "alerts": [...],
    "reports": {...},
    "last_updated": "2024-01-04T15:30:00Z"
  },
  "kpis": {...},
  "critical_alerts": [...],
  "dashboard_config": {...},
  "report_metadata": {...},
  "cache_ttl": 120
}
```

### 4. Real-Time Metrics

**GET** `/api/analytics/metrics/real-time`

Get real-time metrics for live dashboard updates.

#### Query Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `types` | string | `active_users,quiz_completions,leaderboard` | Comma-separated metric types |
| `include_performance` | boolean | `true` | Include performance data |
| `include_health` | boolean | `true` | Include system health |
| `refresh_interval` | integer | `30` | Refresh interval in seconds |
| `organization_id` | integer | - | Filter by organization |

#### Response

```json
{
  "success": true,
  "data": {
    "active_users": {
      "total_online": 234,
      "taking_quiz": 67,
      "browsing": 145,
      "idle": 22,
      "recent_activity": [...],
      "geographic_distribution": {...}
    },
    "live_quiz_sessions": {...},
    "recent_completions": {...},
    "leaderboard_updates": {...},
    "system_health": {...},
    "performance_metrics": {...},
    "alerts": [...],
    "timestamp": "2024-01-04T15:30:00Z"
  },
  "summary": {
    "users_online": 234,
    "active_sessions": 67,
    "recent_completions_count": 12,
    "system_status": "healthy",
    "alerts_count": 1,
    "last_update": "15:30:00"
  },
  "websocket_event": {...},
  "performance_indicators": {...},
  "activity_feed": [...],
  "has_critical_issues": false,
  "cache_ttl": 0
}
```

### 5. Comparative Analytics

**GET** `/api/analytics/comparative/{cohort}`

Get comparative analytics between users and groups.

#### Path Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `cohort` | string | Cohort type (`peers`, `organization`, `global`, `custom`) |

#### Query Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `timeframe` | string | `last_30_days` | Time period for comparison |
| `metrics` | string | `score,accuracy,speed,consistency` | Metrics to compare |
| `include_rankings` | boolean | `true` | Include ranking data |
| `include_percentiles` | boolean | `true` | Include percentile data |
| `include_growth` | boolean | `true` | Include growth comparison |
| `user_ids` | string | - | Comma-separated user IDs for custom cohort |
| `organization_id` | integer | - | Filter by organization |

#### Response

```json
{
  "success": true,
  "data": {
    "user_id": 123,
    "cohort_type": "peers",
    "timeframe": "last_30_days",
    "user_metrics": {
      "score": 78.5,
      "accuracy": 82.3,
      "speed": 145.2,
      "consistency": 76.8
    },
    "cohort_data": {
      "cohort_size": 1247,
      "cohort_description": "Users with similar skill level and activity",
      "average_metrics": {...},
      "percentile_distribution": {...}
    },
    "comparisons": {...},
    "rankings": {...},
    "percentiles": {...},
    "growth_comparison": {...},
    "last_updated": "2024-01-04T15:30:00Z"
  },
  "cache_ttl": 600
}
```

### 6. Trend Analysis

**GET** `/api/analytics/trends/{timeframe}`

Get trend analysis for historical insights.

#### Path Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `timeframe` | string | Timeframe (`daily`, `weekly`, `monthly`, `quarterly`, `yearly`) |

#### Query Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `metric` | string | `score` | Metric to analyze (`score`, `accuracy`, `engagement`, `completion_rate`, `time_spent`) |
| `granularity` | string | `daily` | Data granularity (`hourly`, `daily`, `weekly`, `monthly`) |
| `start_date` | string | - | Start date (ISO 8601 format) |
| `end_date` | string | - | End date (ISO 8601 format) |
| `user_id` | integer | - | User-specific trends |
| `category_id` | integer | - | Category-specific trends |
| `organization_id` | integer | - | Organization filter |
| `include_forecast` | boolean | `true` | Include forecast data |
| `include_seasonality` | boolean | `true` | Include seasonality analysis |

#### Response

```json
{
  "success": true,
  "data": {
    "timeframe": "monthly",
    "metric": "score",
    "granularity": "daily",
    "period": {
      "start_date": "2023-07-01",
      "end_date": "2024-01-04"
    },
    "historical_data": [...],
    "trend_analysis": {
      "direction": "increasing",
      "strength": "moderate",
      "total_change": 15.7,
      "percentage_change": 23.4,
      "average_value": 72.1,
      "min_value": 58.2,
      "max_value": 89.5,
      "volatility": 12.3,
      "momentum": "accelerating"
    },
    "forecast": {
      "method": "linear_regression",
      "periods_forecasted": 7,
      "forecast_data": [...],
      "model_accuracy": 0.78
    },
    "seasonality": {
      "seasonal_pattern_detected": true,
      "pattern_type": "weekly",
      "peak_periods": ["Tuesday", "Wednesday", "Thursday"],
      "low_periods": ["Saturday", "Sunday"]
    },
    "insights": [...],
    "last_updated": "2024-01-04T15:30:00Z"
  },
  "cache_ttl": 1800
}
```

### 7. Export Analytics Data

**GET** `/api/analytics/export/{format}`

Export analytics data in various formats.

#### Path Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `format` | string | Export format (`csv`, `excel`, `pdf`, `json`) |

#### Query Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `type` | string | `user_dashboard` | Data type (`user_dashboard`, `learning_progress`, `comparative`, `trends`) |
| `timeframe` | string | `last_30_days` | Time period |
| `user_id` | integer | - | User ID (required for user-specific exports) |

#### Response

File download with appropriate Content-Type and Content-Disposition headers.

### 8. Analytics Configuration

**GET** `/api/analytics/config`

Get analytics configuration and metadata.

#### Response

```json
{
  "success": true,
  "config": {
    "available_timeframes": [
      "last_7_days", "last_30_days", "last_90_days", 
      "year_to_date", "all_time"
    ],
    "available_metrics": [
      "score", "accuracy", "speed", "consistency", "improvement_rate"
    ],
    "available_cohorts": ["peers", "organization", "global", "custom"],
    "available_granularities": ["hourly", "daily", "weekly", "monthly"],
    "export_formats": ["csv", "excel", "pdf", "json"],
    "real_time_refresh_intervals": [10, 30, 60, 120, 300],
    "dashboard_layouts": ["grid", "learning_focused", "admin_grid"],
    "widget_types": [
      "metric_cards", "line_chart", "radar_chart", "activity_feed",
      "achievement_showcase", "progress_rings", "skill_heatmap"
    ]
  },
  "cache_ttl": 3600
}
```

## WebSocket Endpoints

### Live Updates

**GET** `/ws/analytics/live-updates`

Get real-time analytics updates (simulated WebSocket via HTTP).

#### Query Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `connection_id` | string | - | Unique connection identifier |
| `subscriptions` | string | `user_metrics,leaderboard,system_health` | Comma-separated subscriptions |
| `refresh_interval` | integer | `30` | Refresh interval in seconds |

### Subscribe to Channels

**POST** `/ws/analytics/subscribe`

Subscribe to specific analytics channels.

#### Request Body

```json
{
  "connection_id": "conn_123",
  "channels": ["user_metrics", "leaderboard", "quiz_sessions"],
  "refresh_interval": 30
}
```

### Unsubscribe from Channels

**POST** `/ws/analytics/unsubscribe`

Unsubscribe from analytics channels.

#### Request Body

```json
{
  "connection_id": "conn_123",
  "channels": ["quiz_sessions"]
}
```

## Error Handling

All endpoints return consistent error responses:

```json
{
  "error": "Error description",
  "message": "Detailed error message",
  "code": "ERROR_CODE" // Optional
}
```

### Common HTTP Status Codes

- `200 OK` - Success
- `400 Bad Request` - Invalid parameters
- `401 Unauthorized` - Authentication required
- `403 Forbidden` - Insufficient permissions
- `404 Not Found` - Resource not found
- `500 Internal Server Error` - Server error

## Rate Limiting

API endpoints are rate-limited based on user role:

- **Regular Users**: 100 requests per minute
- **Admin Users**: 300 requests per minute
- **Real-time endpoints**: 60 requests per minute

## Caching

Responses include cache TTL headers:

- User dashboards: 5 minutes
- Learning progress: 10 minutes
- Admin dashboards: 2 minutes
- Real-time metrics: No caching
- Trend analysis: 30 minutes
- Configuration: 1 hour

## Best Practices

1. **Use appropriate timeframes** for your use case
2. **Enable real-time updates** only when necessary
3. **Filter data** using query parameters to reduce payload size
4. **Cache responses** on the client side when appropriate
5. **Handle errors gracefully** and provide user feedback
6. **Use WebSocket endpoints** for live dashboard updates
7. **Respect rate limits** and implement backoff strategies

## Examples

### Basic User Dashboard

```javascript
fetch('/api/analytics/dashboard/user?timeframe=last_7_days', {
  headers: {
    'Authorization': 'Bearer ' + token
  }
})
.then(response => response.json())
.then(data => {
  console.log('User analytics:', data);
});
```

### Real-time Metrics with Polling

```javascript
function fetchRealTimeMetrics() {
  fetch('/api/analytics/metrics/real-time?types=active_users,leaderboard', {
    headers: {
      'Authorization': 'Bearer ' + token
    }
  })
  .then(response => response.json())
  .then(data => {
    updateDashboard(data);
    setTimeout(fetchRealTimeMetrics, data.refresh_interval * 1000);
  });
}
```

### Export Data

```javascript
// Download CSV export
window.open('/api/analytics/export/csv?type=user_dashboard&timeframe=last_30_days');
```