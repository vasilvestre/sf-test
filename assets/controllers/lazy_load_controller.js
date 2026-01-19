import { Controller } from '@hotwired/stimulus';

/**
 * Lazy Load Controller
 * 
 * Uses IntersectionObserver for performance-optimized loading of elements.
 * Elements start hidden/skeleton and reveal with animation when scrolled into view.
 * 
 * Features:
 * - Intersection Observer for efficient scroll detection
 * - Staggered animations for multiple elements
 * - Skeleton loading states
 * - Configurable thresholds and root margins
 */
export default class extends Controller {
    static targets = ['item'];
    static values = {
        threshold: { type: Number, default: 0.1 },
        rootMargin: { type: String, default: '50px' },
        staggerDelay: { type: Number, default: 100 },
        animation: { type: String, default: 'fade-in-up' }
    };

    connect() {
        this.loadedCount = 0;
        this.setupObserver();
        this.observeItems();
    }

    disconnect() {
        if (this.observer) {
            this.observer.disconnect();
        }
    }

    setupObserver() {
        const options = {
            root: null,
            rootMargin: this.rootMarginValue,
            threshold: this.thresholdValue
        };

        this.observer = new IntersectionObserver((entries) => {
            entries.forEach((entry, index) => {
                if (entry.isIntersecting) {
                    this.loadItem(entry.target, index);
                    this.observer.unobserve(entry.target);
                }
            });
        }, options);
    }

    observeItems() {
        this.itemTargets.forEach((item, index) => {
            // Set initial state
            item.style.opacity = '0';
            item.style.transform = 'translateY(30px)';
            item.classList.add('lazy-item', 'lazy-pending');
            
            // Store original index for stagger calculation
            item.dataset.lazyIndex = index;
            
            this.observer.observe(item);
        });
    }

    loadItem(item, batchIndex) {
        const itemIndex = parseInt(item.dataset.lazyIndex, 10);
        const delay = (this.loadedCount % 5) * this.staggerDelayValue;
        
        setTimeout(() => {
            // Remove skeleton/pending state
            item.classList.remove('lazy-pending');
            item.classList.add('lazy-loaded');
            
            // Apply reveal animation
            item.style.transition = 'opacity 0.5s ease-out, transform 0.5s ease-out';
            item.style.opacity = '1';
            item.style.transform = 'translateY(0)';
            
            // Dispatch custom event for other controllers to hook into
            this.dispatch('loaded', { 
                detail: { 
                    item, 
                    index: itemIndex,
                    totalLoaded: this.loadedCount + 1
                }
            });
            
            this.loadedCount++;
        }, delay);
    }

    // Action to manually trigger load of all remaining items
    loadAll() {
        this.itemTargets.forEach((item) => {
            if (item.classList.contains('lazy-pending')) {
                this.loadItem(item, 0);
                this.observer.unobserve(item);
            }
        });
    }
}
