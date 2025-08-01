import { Controller } from "@hotwired/stimulus"
import { connectStreamSource, disconnectStreamSource } from "@hotwired/turbo"

/**
 * Mercure Real-time Controller
 * Handles real-time updates via Mercure Server-Sent Events
 */
export default class extends Controller {
    static values = { 
        url: String,
        topics: Array,
        withCredentials: Boolean
    }

    static targets = ["status", "output"]

    connect() {
        console.log("Mercure controller connected")
        this.subscribeToTopics()
        this.updateConnectionStatus("connecting")
    }

    disconnect() {
        console.log("Mercure controller disconnected")
        this.disconnectFromTopics()
        this.updateConnectionStatus("disconnected")
    }

    subscribeToTopics() {
        if (!this.urlValue) {
            console.error("Mercure URL not provided")
            return
        }

        // Create EventSource URL with topics
        const url = new URL(this.urlValue)
        if (this.topicsValue && this.topicsValue.length > 0) {
            this.topicsValue.forEach(topic => {
                url.searchParams.append('topic', topic)
            })
        }

        // Create EventSource connection
        this.eventSource = new EventSource(url.toString(), {
            withCredentials: this.withCredentialsValue || false
        })

        // Set up event listeners
        this.eventSource.onopen = () => {
            console.log("Mercure connection opened")
            this.updateConnectionStatus("connected")
        }

        this.eventSource.onerror = (error) => {
            console.error("Mercure connection error:", error)
            this.updateConnectionStatus("error")
        }

        this.eventSource.onmessage = (event) => {
            this.handleMessage(event)
        }

        // Connect to Turbo Streams if available
        if (typeof connectStreamSource === 'function') {
            connectStreamSource(this.eventSource)
        }
    }

    disconnectFromTopics() {
        if (this.eventSource) {
            // Disconnect from Turbo Streams if available
            if (typeof disconnectStreamSource === 'function') {
                disconnectStreamSource(this.eventSource)
            }
            
            this.eventSource.close()
            this.eventSource = null
        }
    }

    handleMessage(event) {
        try {
            const data = JSON.parse(event.data)
            console.log("Received Mercure message:", data)
            
            // Dispatch custom events based on message type
            this.dispatchCustomEvent(data)
            
            // Update UI if output target exists
            if (this.hasOutputTarget) {
                this.updateOutput(data)
            }
        } catch (error) {
            console.error("Error parsing Mercure message:", error, event.data)
        }
    }

    dispatchCustomEvent(data) {
        const eventName = `mercure:${data.type || 'message'}`
        const customEvent = new CustomEvent(eventName, {
            detail: data,
            bubbles: true
        })
        
        this.element.dispatchEvent(customEvent)
        
        // Also dispatch on document for global listeners
        document.dispatchEvent(customEvent)
    }

    updateConnectionStatus(status) {
        if (this.hasStatusTarget) {
            this.statusTarget.textContent = status
            this.statusTarget.className = `mercure-status mercure-status--${status}`
        }
        
        this.element.setAttribute('data-mercure-status', status)
        
        // Dispatch status change event
        const statusEvent = new CustomEvent('mercure:status', {
            detail: { status },
            bubbles: true
        })
        this.element.dispatchEvent(statusEvent)
    }

    updateOutput(data) {
        const outputElement = document.createElement('div')
        outputElement.className = 'mercure-message'
        outputElement.innerHTML = `
            <div class="mercure-message__type">${data.type || 'message'}</div>
            <div class="mercure-message__timestamp">${new Date(data.timestamp * 1000).toLocaleTimeString()}</div>
            <div class="mercure-message__data">${JSON.stringify(data, null, 2)}</div>
        `
        
        this.outputTarget.appendChild(outputElement)
        
        // Keep only last 10 messages
        while (this.outputTarget.children.length > 10) {
            this.outputTarget.removeChild(this.outputTarget.firstChild)
        }
        
        // Scroll to bottom
        this.outputTarget.scrollTop = this.outputTarget.scrollHeight
    }

    // Action methods for manual control
    reconnect() {
        this.disconnectFromTopics()
        setTimeout(() => {
            this.subscribeToTopics()
        }, 1000)
    }

    toggleConnection() {
        if (this.eventSource && this.eventSource.readyState === EventSource.OPEN) {
            this.disconnectFromTopics()
        } else {
            this.subscribeToTopics()
        }
    }
}