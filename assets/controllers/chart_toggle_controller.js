import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['chart', 'categorySelect'];

    connect() {
        if (this.hasCategorySelectTarget) {
            this.categorySelectTarget.addEventListener('change', this.filterByCategory.bind(this));

            // Initialize with current selection when the controller connects
            const currentCategory = this.categorySelectTarget.value;
            const url = `/quiz/chart-data/${currentCategory === 'all' ? '' : currentCategory}`;

            // Longer delay to ensure chart is fully initialized
            setTimeout(() => {
                this.updateChart(url);
            }, 1000); // Increased from 500ms to 1000ms
        }
    }

    filterByCategory(event) {
        const categoryId = event.target.value;
        const url = `/quiz/chart-data/${categoryId === 'all' ? '' : categoryId}`;
        this.updateChart(url);
    }

    updateChart(url) {
        // Wait for Chart.js to be loaded
        import('chart.js').then(({ Chart }) => {
            // Try multiple methods to get the chart instance
            let chart = this.findChartInstance(Chart);

            // If chart is not found, try again after a longer delay
            if (!chart) {
                console.log('Chart not found on first attempt, retrying...');
                setTimeout(() => {
                    chart = this.findChartInstance(Chart);

                    if (!chart) {
                        console.error('Chart not found after retry');
                        // Try one more time with an even longer delay
                        setTimeout(() => {
                            chart = this.findChartInstance(Chart);
                            if (chart) {
                                console.log('Chart found on final attempt');
                                this.fetchAndUpdateChart(chart, url);
                            } else {
                                console.error('Chart not found after final attempt');
                            }
                        }, 1000);
                        return;
                    }

                    this.fetchAndUpdateChart(chart, url);
                }, 500); // Increased from 300ms to 500ms
                return;
            }

            this.fetchAndUpdateChart(chart, url);
        }).catch(error => {
            console.error('Error loading Chart.js:', error);
        });
    }

    findChartInstance(Chart) {
        // Method 1: Try to get the chart by ID
        let chart = Chart.getChart('performance-chart');
        if (chart) return chart;

        // Method 2: Try to find canvas element and get chart by element
        const canvasElement = document.getElementById('performance-chart');
        if (canvasElement) {
            chart = Chart.getChart(canvasElement);
            if (chart) return chart;
        }

        // Method 3: Try to get all registered charts using registry
        try {
            if (Chart.registry && Chart.registry.charts) {
                const charts = Chart.registry.charts;
                if (charts.length > 0) {
                    console.log('Using first available chart from registry');
                    return charts[0];
                }
            }
        } catch (e) {
            console.error('Error accessing chart registry:', e);
        }

        // Method 4: Try to find any canvas element with a chart attached
        try {
            const canvasElements = document.querySelectorAll('canvas');
            for (let i = 0; i < canvasElements.length; i++) {
                const potentialChart = Chart.getChart(canvasElements[i]);
                if (potentialChart) {
                    console.log('Found chart on canvas element:', canvasElements[i].id || 'unnamed');
                    return potentialChart;
                }
            }
        } catch (e) {
            console.error('Error finding chart on canvas elements:', e);
        }

        // No chart found
        return null;
    }

    fetchAndUpdateChart(chart, url) {
        fetch(url)
            .then(response => response.json())
            .then(data => {
                chart.data = data.data;
                chart.options = data.chart;
                chart.update();
            })
            .catch(error => console.error('Error fetching chart data:', error));
    }
}
