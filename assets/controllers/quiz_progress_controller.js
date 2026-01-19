import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['progressBar', 'progressText'];
    static values = { total: Number };

    connect() {
        console.log('Quiz progress controller connected!');
        this.updateProgress();
        // Use event delegation for checkbox changes
        this.boundUpdate = this.updateProgress.bind(this);
        this.element.addEventListener('change', this.boundUpdate);
    }

    disconnect() {
        this.element.removeEventListener('change', this.boundUpdate);
    }

    updateProgress() {
        const checkboxes = this.element.querySelectorAll('input[type="checkbox"]');
        const questionCards = this.element.querySelectorAll('.question-card');
        let answeredCount = 0;

        questionCards.forEach(card => {
            const cardCheckboxes = card.querySelectorAll('input[type="checkbox"]');
            const hasAnswer = Array.from(cardCheckboxes).some(cb => cb.checked);
            if (hasAnswer) {
                answeredCount++;
            }
        });

        const total = this.totalValue || questionCards.length;
        const percentage = total > 0 ? Math.round((answeredCount / total) * 100) : 0;

        console.log(`Progress: ${answeredCount}/${total} = ${percentage}%`);

        if (this.hasProgressBarTarget) {
            this.progressBarTarget.style.width = `${percentage}%`;
            this.progressBarTarget.setAttribute('aria-valuenow', percentage);
        }

        if (this.hasProgressTextTarget) {
            this.progressTextTarget.textContent = `${answeredCount} / ${total}`;
        }
    }
}
