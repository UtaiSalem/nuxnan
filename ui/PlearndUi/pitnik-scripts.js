// Pitnik Template JavaScript

// Ripple Effect
document.addEventListener('DOMContentLoaded', function() {
    // Initialize ripple effect
    const rippleElements = document.querySelectorAll('[data-ripple]');
    rippleElements.forEach(element => {
        element.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            ripple.classList.add('ripple-effect');
            
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            
            this.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    });
    
    // Initialize tooltips
    const tooltipElements = document.querySelectorAll('[data-toggle="tooltip"]');
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', function() {
            const tooltipText = this.getAttribute('data-placement') || 'top';
            const title = this.getAttribute('title') || this.getAttribute('data-original-title');
            
            if (title) {
                const tooltip = document.createElement('div');
                tooltip.classList.add('custom-tooltip');
                tooltip.textContent = title;
                tooltip.classList.add(tooltipText);
                
                document.body.appendChild(tooltip);
                
                const rect = this.getBoundingClientRect();
                const tooltipRect = tooltip.getBoundingClientRect();
                
                let top, left;
                
                switch(tooltipText) {
                    case 'top':
                        top = rect.top - tooltipRect.height - 10;
                        left = rect.left + (rect.width - tooltipRect.width) / 2;
                        break;
                    case 'bottom':
                        top = rect.bottom + 10;
                        left = rect.left + (rect.width - tooltipRect.width) / 2;
                        break;
                    case 'left':
                        top = rect.top + (rect.height - tooltipRect.height) / 2;
                        left = rect.left - tooltipRect.width - 10;
                        break;
                    case 'right':
                        top = rect.top + (rect.height - tooltipRect.height) / 2;
                        left = rect.right + 10;
                        break;
                }
                
                tooltip.style.top = top + 'px';
                tooltip.style.left = left + 'px';
                
                this.setAttribute('data-original-title', title);
                this.removeAttribute('title');
            }
        });
        
        element.addEventListener('mouseleave', function() {
            const tooltip = document.querySelector('.custom-tooltip');
            if (tooltip) {
                tooltip.remove();
            }
            
            const originalTitle = this.getAttribute('data-original-title');
            if (originalTitle) {
                this.setAttribute('title', originalTitle);
                this.removeAttribute('data-original-title');
            }
        });
    });
    
    // Initialize popup
    const popup = document.querySelector('.popup-wraper');
    if (popup) {
        setTimeout(() => {
            popup.classList.add('show');
        }, 3000);
    }
    
    // Close popup
    const popupClose = document.querySelector('.popup-closed');
    if (popupClose) {
        popupClose.addEventListener('click', function(e) {
            e.preventDefault();
            const popup = document.querySelector('.popup-wraper');
            if (popup) {
                popup.classList.remove('show');
            }
        });
    }
    
    // Counter animation
    const counters = document.querySelectorAll('.counter');
    counters.forEach(counter => {
        const target = parseInt(counter.textContent);
        const duration = 2000;
        const increment = target / (duration / 16);
        let current = 0;
        
        const updateCounter = () => {
            current += increment;
            if (current < target) {
                counter.textContent = Math.floor(current);
                requestAnimationFrame(updateCounter);
            } else {
                counter.textContent = target;
            }
        };
        
        // Start animation when element is in viewport
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    updateCounter();
                    observer.unobserve(entry.target);
                }
            });
        });
        
        observer.observe(counter);
    });
    
    // Smooth scroll for anchor links
    const anchorLinks = document.querySelectorAll('a[href^="#"]');
    anchorLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);
            
            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Add animation classes on scroll
    const animateElements = document.querySelectorAll('.animate-fade-in');
    const animateObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animated');
                animateObserver.unobserve(entry.target);
            }
        });
    });
    
    animateElements.forEach(element => {
        animateObserver.observe(element);
    });
});

// Add CSS for ripple effect and tooltips
const style = document.createElement('style');
style.textContent = `
    .ripple-effect {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.5);
        transform: scale(0);
        animation: ripple-animation 0.6s ease-out;
        pointer-events: none;
    }
    
    @keyframes ripple-animation {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
    
    .custom-tooltip {
        position: absolute;
        background: #333;
        color: #fff;
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 12px;
        z-index: 1000;
        opacity: 0;
        transition: opacity 0.3s;
        pointer-events: none;
    }
    
    .custom-tooltip.top {
        margin-top: -5px;
    }
    
    .custom-tooltip.bottom {
        margin-top: 5px;
    }
    
    .custom-tooltip.left {
        margin-left: -5px;
    }
    
    .custom-tooltip.right {
        margin-left: 5px;
    }
    
    .animate-fade-in {
        opacity: 0;
        transform: translateY(20px);
        transition: opacity 0.6s ease, transform 0.6s ease;
    }
    
    .animate-fade-in.animated {
        opacity: 1;
        transform: translateY(0);
    }
    
    [data-ripple] {
        position: relative;
        overflow: hidden;
    }
`;

document.head.appendChild(style);