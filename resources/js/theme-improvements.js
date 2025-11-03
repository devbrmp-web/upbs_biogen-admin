/**
 * Theme Improvements - Prevent visual glitches during theme switching
 */
class ThemeManager {
    constructor() {
        this.html = document.documentElement;
        this.body = document.body;
        this.isTransitioning = false;
        this.init();
    }

    init() {
        this.syncThemeUI();
        this.bindThemeEvents();
        this.setupTransitions();
        
        this.initialized = true;
        window.dispatchEvent(new CustomEvent('themeManagerReady'));
    }

    syncThemeUI() {
        const currentTheme = this.html.getAttribute('data-bs-theme') || 'light';
        this.updateThemeIcon(currentTheme);
    }

    preventFOUC() {
        this.html.classList.add('theme-loading');
        
        setTimeout(() => {
            this.html.classList.remove('theme-loading');
        }, 100);
    }

    setupTransitions() {
        const style = document.createElement('style');
        style.textContent = `
            .theme-loading * {
                transition: none !important;
                animation: none !important;
            }
            
            .theme-transitioning {
                transition: background-color 0.1s cubic-bezier(0.25, 0.46, 0.45, 0.94), 
                           color 0.1s cubic-bezier(0.25, 0.46, 0.45, 0.94), 
                           border-color 0.1s cubic-bezier(0.25, 0.46, 0.45, 0.94),
                           box-shadow 0.1s cubic-bezier(0.25, 0.46, 0.45, 0.94) !important;
                transform: translateZ(0); /* Hardware acceleration */
                will-change: background-color, color, border-color, box-shadow;
                backface-visibility: hidden; /* Prevent flickering */
                contain: layout style paint; /* Optimize rendering */
            }
            
            .theme-transitioning *,
            .theme-transitioning *::before,
            .theme-transitioning *::after {
                transition: background-color 0.1s cubic-bezier(0.25, 0.46, 0.45, 0.94), 
                           color 0.1s cubic-bezier(0.25, 0.46, 0.45, 0.94), 
                           border-color 0.1s cubic-bezier(0.25, 0.46, 0.45, 0.94),
                           box-shadow 0.1s cubic-bezier(0.25, 0.46, 0.45, 0.94) !important;
                transform: translateZ(0); /* Hardware acceleration */
                backface-visibility: hidden; /* Prevent flickering */
                contain: layout style; /* Optimize rendering */
            }
            
            /* Performance optimization for frequently changing elements */
            .theme-transitioning .logo-box,
            .theme-transitioning .topbar,
            .theme-transitioning .main-nav,
            .theme-transitioning .btn,
            .theme-transitioning .topbar-button,
            .theme-transitioning .card,
            .theme-transitioning .modal-content,
            .theme-transitioning .form-control,
            .theme-transitioning .form-select,
            .theme-transitioning .table,
            .theme-transitioning .dropdown-menu {
                transform: translateZ(0) !important;
                will-change: background-color, color, border-color, box-shadow !important;
                backface-visibility: hidden !important;
                contain: layout style paint !important;
                isolation: isolate !important;
            }
            
            /* Prevent logo flicker during theme switch */
            .logo-box .logo-light,
            .logo-box .logo-dark {
                transition: opacity 0.1s cubic-bezier(0.25, 0.46, 0.45, 0.94) !important;
                transform: translateZ(0);
                backface-visibility: hidden;
                will-change: opacity;
            }
            
            /* Smooth topbar transitions */
            .topbar {
                transition: background-color 0.1s cubic-bezier(0.25, 0.46, 0.45, 0.94), 
                           border-color 0.1s cubic-bezier(0.25, 0.46, 0.45, 0.94) !important;
                transform: translateZ(0);
                will-change: background-color, border-color;
            }
            
            /* Smooth sidebar transitions */
            .main-nav {
                transition: background-color 0.1s cubic-bezier(0.25, 0.46, 0.45, 0.94), 
                           border-color 0.1s cubic-bezier(0.25, 0.46, 0.45, 0.94) !important;
                transform: translateZ(0);
                will-change: background-color, border-color;
            }
            
            /* Smooth button transitions */
            .btn, .topbar-button {
                transition: background-color 0.1s cubic-bezier(0.25, 0.46, 0.45, 0.94), 
                           color 0.1s cubic-bezier(0.25, 0.46, 0.45, 0.94), 
                           border-color 0.1s cubic-bezier(0.25, 0.46, 0.45, 0.94) !important;
                transform: translateZ(0);
                will-change: background-color, color, border-color;
            }
            
            /* Optimize cards and modals */
            .card, .modal-content {
                transition: background-color 0.1s cubic-bezier(0.25, 0.46, 0.45, 0.94), 
                           border-color 0.1s cubic-bezier(0.25, 0.46, 0.45, 0.94) !important;
                transform: translateZ(0);
                will-change: background-color, border-color;
            }
            
            /* Optimize form elements */
            .form-control, .form-select {
                transition: background-color 0.1s cubic-bezier(0.25, 0.46, 0.45, 0.94), 
                           border-color 0.1s cubic-bezier(0.25, 0.46, 0.45, 0.94),
                           color 0.1s cubic-bezier(0.25, 0.46, 0.45, 0.94) !important;
                transform: translateZ(0);
                will-change: background-color, border-color, color;
            }
            
            /* Performance optimization for low-end devices */
            @media (prefers-reduced-motion: reduce) {
                .theme-transitioning,
                .theme-transitioning * {
                    transition: none !important;
                    animation: none !important;
                }
            }
            
            /* Optimize for mobile devices */
            @media (max-width: 768px) {
                .theme-transitioning,
                .theme-transitioning * {
                    transition-duration: 0.05s !important;
                }
            }
            
            /* Optimize for very low-end devices */
            @media (max-width: 480px) and (max-height: 800px) {
                .theme-transitioning,
                .theme-transitioning * {
                    transition: background-color 0.05s ease,
                               color 0.05s ease !important;
                    will-change: auto !important;
                }
            }
            
            /* Optimize for high refresh rate displays */
            @media (min-resolution: 120dpi) {
                .theme-transitioning,
                .theme-transitioning * {
                    transition-timing-function: cubic-bezier(0.25, 0.46, 0.45, 0.94) !important;
                }
            }
        `;
        document.head.appendChild(style);
    }

    /**
     * Toggle theme with optimized performance for all devices
     */
    toggleTheme() {
        // Prevent multiple rapid clicks
        if (this.isTransitioning) {
            return false;
        }
        
        this.isTransitioning = true;
        const currentTheme = this.html.getAttribute('data-bs-theme') || 'light';
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        
        // Use requestAnimationFrame for smoother transitions with GPU optimization
        requestAnimationFrame(() => {
            // Add transition class for smooth animation
            this.html.classList.add('theme-transitioning');
            
            // Force a reflow to ensure the transition class is applied
            this.html.offsetHeight;
            
            // Change theme immediately for instant feedback
            this.html.setAttribute('data-bs-theme', newTheme);
            
            // Update icon immediately
            this.updateThemeIcon(newTheme);
            
            // Save to localStorage immediately
            this.saveTheme(newTheme);
            
            // Use double requestAnimationFrame for better performance on low-end devices
            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    // Remove transition class and reset flag after animation completes
                    // Using 100ms to match the 0.1s CSS transition duration
                    setTimeout(() => {
                         this.html.classList.remove('theme-transitioning');
                         this.isTransitioning = false;
                     }, 100);
                });
            });
        });
        
        return true;
    }

    bindThemeEvents() {
        // Make theme manager available globally for layout.js
        window.themeManager = this;
        
        // Only bind if not already bound by layout.js
        const themeToggle = document.getElementById('light-dark-mode');
        if (themeToggle && !themeToggle.hasAttribute('data-theme-bound')) {
            themeToggle.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation(); // Prevent other listeners
                this.toggleTheme();
            }, { once: false, passive: false });
            themeToggle.setAttribute('data-theme-bound', 'true');
        }

        // Listen for manual theme changes from layout.js or other sources
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes' && mutation.attributeName === 'data-bs-theme') {
                    const newTheme = this.html.getAttribute('data-bs-theme');
                    if (newTheme && !this.isTransitioning) {
                        this.updateThemeIcon(newTheme);
                        this.saveTheme(newTheme);
                    }
                }
            });
        });

        observer.observe(this.html, {
            attributes: true,
            attributeFilter: ['data-bs-theme', 'data-menu-color', 'data-topbar-color']
        });
    }

    updateThemeIcon(theme) {
        const themeToggle = document.getElementById('light-dark-mode');
        if (!themeToggle) return;
        
        const icon = themeToggle.querySelector('iconify-icon');
        if (icon) {
            if (theme === 'dark') {
                icon.setAttribute('icon', 'iconamoon:mode-light-duotone');
            } else {
                icon.setAttribute('icon', 'iconamoon:mode-dark-duotone');
            }
        }
    }

    saveTheme(theme) {
        // Get existing config or create new one
        let config = window.config || window.defaultConfig || {
            theme: "light",
            topbar: { color: "light" },
            menu: { size: "sm-hover-active", color: "light" }
        };
        
        // Update theme in config
        config.theme = theme;
        
        // Save to localStorage using the existing system
        localStorage.setItem("__REBACK_CONFIG__", JSON.stringify(config));
        
        // Update global config
        window.config = config;
    }

    // Method to be called by layout.js when theme changes
    onThemeChange(theme) {
        this.updateThemeIcon(theme);
        this.saveTheme(theme);
    }
}

// Initialize theme manager when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.themeManager = new ThemeManager();
    });
} else {
    window.themeManager = new ThemeManager();
}

// Export for use in layout.js
window.ThemeManager = ThemeManager;