// Loading State Handler
function showLoading(buttonId) {
    const button = document.getElementById(buttonId);
    if (!button) return;
    
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = `
        <div class="inline-block mr-2">
            <div class="spinner-border animate-spin inline-block w-4 h-4 border-2 rounded-full" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
        Loading...
    `;
    return originalText;
}

function hideLoading(buttonId, originalText) {
    const button = document.getElementById(buttonId);
    if (!button) return;
    
    button.disabled = false;
    button.innerHTML = originalText;
}

// Progress Bar Handler
function updateProgress(elementId, progress) {
    const progressBar = document.getElementById(elementId);
    if (!progressBar) return;
    
    progressBar.style.width = progress + '%';
}

// Alert Handler
function showAlert(message, type = 'success', duration = 3000) {
    const alertContainer = document.getElementById('alert-container') || createAlertContainer();
    const alert = document.createElement('div');
    
    alert.className = `alert animate-fade-in border-l-4 p-4 mb-4 ${
        type === 'success' ? 'bg-green-100 border-green-400 text-green-700' :
        type === 'error' ? 'bg-red-100 border-red-400 text-red-700' :
        type === 'warning' ? 'bg-yellow-100 border-yellow-400 text-yellow-700' :
        'bg-blue-100 border-blue-400 text-blue-700'
    }`;
    
    alert.innerHTML = `<p class="font-medium">${message}</p>`;
    alertContainer.appendChild(alert);
    
    if (duration > 0) {
        setTimeout(() => {
            alert.classList.add('animate-fade-out');
            setTimeout(() => alert.remove(), 300);
        }, duration);
    }
}

function createAlertContainer() {
    const container = document.createElement('div');
    container.id = 'alert-container';
    container.className = 'fixed top-4 right-4 z-50';
    document.body.appendChild(container);
    return container;
}

// Form Validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('border-red-500');
            isValid = false;
        } else {
            input.classList.remove('border-red-500');
        }
    });
    
    return isValid;
}

// Smooth Scroll
function smoothScroll(elementId) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    element.scrollIntoView({
        behavior: 'smooth',
        block: 'start'
    });
}

// Debounce Function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Throttle Function
function throttle(func, limit) {
    let inThrottle;
    return function executedFunction(...args) {
        if (!inThrottle) {
            func(...args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// Initialize UI Components
document.addEventListener('DOMContentLoaded', function() {
    // Add smooth transitions
    document.body.classList.add('transition-colors', 'duration-300');
    
    // Initialize tooltips
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(tooltip => {
        tooltip.addEventListener('mouseenter', showTooltip);
        tooltip.addEventListener('mouseleave', hideTooltip);
    });
});

// Tooltip Functions
function showTooltip(event) {
    const tooltip = document.createElement('div');
    tooltip.className = 'absolute z-50 px-2 py-1 text-sm text-white bg-gray-900 rounded-md shadow-lg';
    tooltip.textContent = this.dataset.tooltip;
    
    const rect = this.getBoundingClientRect();
    tooltip.style.top = `${rect.bottom + 5}px`;
    tooltip.style.left = `${rect.left + (rect.width / 2)}px`;
    tooltip.style.transform = 'translateX(-50%)';
    
    this.appendChild(tooltip);
}

function hideTooltip() {
    const tooltip = this.querySelector('.absolute');
    if (tooltip) {
        tooltip.remove();
    }
} 