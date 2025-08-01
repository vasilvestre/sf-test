import { Controller } from "@hotwired/stimulus"

/**
 * Quiz Real-time Controller
 * Handles real-time quiz updates, progress tracking, and live feedback
 */
export default class extends Controller {
    static values = { 
        sessionId: String,
        userId: String,
        mercureUrl: String
    }

    static targets = [
        "progress", 
        "score", 
        "question", 
        "feedback", 
        "leaderboard",
        "analytics",
        "achievements"
    ]

    connect() {
        console.log("Quiz real-time controller connected")
        this.setupMercureSubscriptions()
        this.setupEventListeners()
    }

    disconnect() {
        console.log("Quiz real-time controller disconnected")
        this.cleanupSubscriptions()
    }

    setupMercureSubscriptions() {
        if (!this.sessionIdValue || !this.mercureUrlValue) {
            console.warn("Session ID or Mercure URL not provided")
            return
        }

        // Subscribe to session-specific topics
        const topics = [
            `quiz/session/${this.sessionIdValue}`,
            `quiz/session/${this.sessionIdValue}/questions`,
            `quiz/session/${this.sessionIdValue}/completed`,
            `analytics/user/${this.userIdValue}`,
            `achievements/user/${this.userIdValue}`,
            `leaderboard/global`,
            `statistics/real-time`
        ]

        // Create Mercure subscription URL
        const url = new URL(this.mercureUrlValue)
        topics.forEach(topic => {
            url.searchParams.append('topic', topic)
        })

        // Create EventSource connection
        this.eventSource = new EventSource(url.toString(), {
            withCredentials: true
        })

        this.eventSource.onmessage = (event) => {
            this.handleRealTimeUpdate(event)
        }

        this.eventSource.onerror = (error) => {
            console.error("Quiz real-time connection error:", error)
            this.showConnectionError()
        }

        this.eventSource.onopen = () => {
            console.log("Quiz real-time connection established")
            this.hideConnectionError()
        }
    }

    setupEventListeners() {
        // Listen for quiz-specific events
        document.addEventListener('quiz:answer:submitted', (event) => {
            this.handleAnswerSubmitted(event.detail)
        })

        document.addEventListener('quiz:question:changed', (event) => {
            this.handleQuestionChanged(event.detail)
        })
    }

    handleRealTimeUpdate(event) {
        try {
            const data = JSON.parse(event.data)
            console.log("Received quiz update:", data)

            switch (data.type) {
                case 'quiz_session_update':
                    this.handleSessionUpdate(data)
                    break
                case 'question_answered':
                    this.handleQuestionAnswered(data)
                    break
                case 'quiz_completed':
                    this.handleQuizCompleted(data)
                    break
                case 'analytics_update':
                    this.handleAnalyticsUpdate(data)
                    break
                case 'achievement_unlocked':
                    this.handleAchievementUnlocked(data)
                    break
                case 'leaderboard_update':
                    this.handleLeaderboardUpdate(data)
                    break
                case 'statistics_update':
                    this.handleStatisticsUpdate(data)
                    break
                default:
                    console.log("Unknown update type:", data.type)
            }
        } catch (error) {
            console.error("Error parsing quiz update:", error)
        }
    }

    handleSessionUpdate(data) {
        const sessionData = data.data

        // Update progress if available
        if (this.hasProgressTarget && sessionData.currentScore !== undefined) {
            this.updateProgress(sessionData)
        }

        // Update score display
        if (this.hasScoreTarget && sessionData.currentScore !== undefined) {
            this.scoreTarget.textContent = `${sessionData.currentScore.toFixed(1)}%`
        }

        // Show session status
        this.showNotification(`Quiz ${sessionData.status}`, 'info')
    }

    handleQuestionAnswered(data) {
        const answerData = data.answerData

        // Show immediate feedback
        if (this.hasFeedbackTarget) {
            this.showAnswerFeedback(answerData)
        }

        // Update question display if it's the current question
        if (this.hasQuestionTarget) {
            this.updateQuestionState(answerData)
        }

        // Animate score change
        this.animateScoreUpdate(answerData.score)
    }

    handleQuizCompleted(data) {
        const results = data.results

        // Show completion modal/notification
        this.showQuizCompletionModal(results)

        // Update final score and statistics
        this.updateFinalResults(results)

        // Redirect to results page after delay
        setTimeout(() => {
            window.location.href = `/quiz/results/${this.sessionIdValue}`
        }, 3000)
    }

    handleAnalyticsUpdate(data) {
        if (this.hasAnalyticsTarget) {
            this.updateAnalyticsDisplay(data.analytics)
        }
    }

    handleAchievementUnlocked(data) {
        if (this.hasAchievementsTarget) {
            this.showAchievementNotification(data.achievement)
        }
    }

    handleLeaderboardUpdate(data) {
        if (this.hasLeaderboardTarget) {
            this.updateLeaderboard(data.leaderboard)
        }
    }

    handleStatisticsUpdate(data) {
        // Update real-time statistics display
        this.updateGlobalStatistics(data.statistics)
    }

    updateProgress(sessionData) {
        const progressBar = this.progressTarget.querySelector('.progress-bar')
        const progressText = this.progressTarget.querySelector('.progress-text')

        if (progressBar && sessionData.progress !== undefined) {
            progressBar.style.width = `${sessionData.progress}%`
            progressBar.setAttribute('aria-valuenow', sessionData.progress)
        }

        if (progressText) {
            progressText.textContent = `${sessionData.questionsAnswered || 0} / ${sessionData.totalQuestions || 0}`
        }
    }

    showAnswerFeedback(answerData) {
        const feedbackElement = this.feedbackTarget
        const isCorrect = answerData.isCorrect

        feedbackElement.className = `feedback ${isCorrect ? 'feedback--correct' : 'feedback--incorrect'}`
        feedbackElement.innerHTML = `
            <div class="feedback__icon">
                ${isCorrect ? '✅' : '❌'}
            </div>
            <div class="feedback__text">
                ${isCorrect ? 'Correct!' : 'Incorrect'}
                <div class="feedback__score">+${answerData.score.toFixed(1)} points</div>
            </div>
        `

        feedbackElement.style.display = 'block'

        // Auto-hide after 3 seconds
        setTimeout(() => {
            feedbackElement.style.display = 'none'
        }, 3000)
    }

    updateQuestionState(answerData) {
        const questionElement = this.questionTarget
        questionElement.classList.add('question--answered')
        
        if (answerData.isCorrect) {
            questionElement.classList.add('question--correct')
        } else {
            questionElement.classList.add('question--incorrect')
        }
    }

    animateScoreUpdate(scoreChange) {
        if (!this.hasScoreTarget) return

        const scoreElement = this.scoreTarget
        const changeElement = document.createElement('div')
        changeElement.className = 'score-change'
        changeElement.textContent = `+${scoreChange.toFixed(1)}`

        scoreElement.appendChild(changeElement)

        // Animate the change
        changeElement.style.animation = 'scoreChangeAnimation 2s ease-out'

        setTimeout(() => {
            changeElement.remove()
        }, 2000)
    }

    showQuizCompletionModal(results) {
        const modal = document.createElement('div')
        modal.className = 'quiz-completion-modal'
        modal.innerHTML = `
            <div class="modal-content">
                <h2>Quiz Completed!</h2>
                <div class="results">
                    <div class="final-score">
                        Final Score: ${results.finalScore.toFixed(1)}%
                    </div>
                    <div class="correct-answers">
                        ${results.correctAnswers} / ${results.totalQuestions} correct
                    </div>
                    <div class="time-spent">
                        Time: ${this.formatTime(results.timeSpent)}
                    </div>
                </div>
                <button class="btn btn-primary" onclick="this.parentElement.parentElement.remove()">
                    Close
                </button>
            </div>
        `

        document.body.appendChild(modal)
    }

    updateLeaderboard(leaderboard) {
        const container = this.leaderboardTarget
        container.innerHTML = `
            <h3>Leaderboard</h3>
            <div class="leaderboard-list">
                ${leaderboard.map((entry, index) => `
                    <div class="leaderboard-entry ${entry.userId === this.userIdValue ? 'leaderboard-entry--current' : ''}">
                        <span class="rank">#${entry.rank}</span>
                        <span class="score">${entry.score.toFixed(1)}%</span>
                    </div>
                `).join('')}
            </div>
        `
    }

    showAchievementNotification(achievement) {
        const notification = document.createElement('div')
        notification.className = 'achievement-notification'
        notification.innerHTML = `
            <div class="achievement-icon">🏆</div>
            <div class="achievement-content">
                <div class="achievement-title">${achievement.title}</div>
                <div class="achievement-description">${achievement.description}</div>
            </div>
        `

        document.body.appendChild(notification)

        // Auto-remove after 5 seconds
        setTimeout(() => {
            notification.remove()
        }, 5000)
    }

    showNotification(message, type = 'info') {
        // Create and show a simple notification
        const notification = document.createElement('div')
        notification.className = `notification notification--${type}`
        notification.textContent = message

        document.body.appendChild(notification)

        setTimeout(() => {
            notification.remove()
        }, 3000)
    }

    showConnectionError() {
        this.showNotification('Real-time connection lost', 'error')
    }

    hideConnectionError() {
        // Remove any existing error notifications
        document.querySelectorAll('.notification--error').forEach(el => el.remove())
    }

    formatTime(seconds) {
        const minutes = Math.floor(seconds / 60)
        const remainingSeconds = Math.floor(seconds % 60)
        return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`
    }

    cleanupSubscriptions() {
        if (this.eventSource) {
            this.eventSource.close()
            this.eventSource = null
        }
    }

    // Action methods for manual triggering
    refreshLeaderboard() {
        // Manually request leaderboard refresh
        fetch(`/api/quiz/v2/leaderboard/refresh`, { method: 'POST' })
            .then(response => response.json())
            .then(data => {
                if (data.leaderboard) {
                    this.updateLeaderboard(data.leaderboard)
                }
            })
            .catch(error => console.error('Error refreshing leaderboard:', error))
    }

    reconnect() {
        this.cleanupSubscriptions()
        setTimeout(() => {
            this.setupMercureSubscriptions()
        }, 1000)
    }
}