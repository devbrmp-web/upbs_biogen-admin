/**
 * Responsive Enhancements for Admin Interface
 * Handles mobile-specific behaviors and interactions
 */

class ResponsiveEnhancements {
    constructor() {
        this.isMobile = window.innerWidth < 768;
        this.isTablet = window.innerWidth >= 768 && window.innerWidth < 992;
        this.isDesktop = window.innerWidth >= 992;
        
        this.init();
        this.bindEvents();
    }

    init() {
        this.handleInitialLoad();
        this.setupMobileBackdrop();
        this.enhanceTableResponsiveness();
        this.setupTouchGestures();
    }

    bindEvents() {
        // Window resize handler
        window.addEventListener('resize', this.debounce(() => {
            this.updateBreakpoints();
            this.handleResize();
        }, 250));

        // Mobile backdrop click handler
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('mobile-backdrop')) {
                this.closeMobileSidebar();
            }
        });

        // Enhanced menu toggle for mobile
        const menuToggle = document.querySelector('.button-toggle-menu');
        if (menuToggle) {
            menuToggle.addEventListener('click', (e) => {
                if (this.isMobile) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.toggleMobileSidebar();
                }
            });
        }

        // Escape key to close mobile sidebar
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isMobile) {
                this.closeMobileSidebar();
            }
        });

        // Handle form submission on mobile
        this.enhanceMobileForms();
        
        // Handle dropdown behavior on mobile
        this.enhanceMobileDropdowns();
    }

    updateBreakpoints() {
        this.isMobile = window.innerWidth < 768;
        this.isTablet = window.innerWidth >= 768 && window.innerWidth < 992;
        this.isDesktop = window.innerWidth >= 992;
    }

    handleInitialLoad() {
        if (this.isMobile) {
            // Hide sidebar by default on mobile
            document.body.classList.remove('sidebar-enable');
            this.hideMobileSearch();
        }
    }

    handleResize() {
        if (this.isMobile) {
            this.closeMobileSidebar();
            this.hideMobileSearch();
        } else {
            this.removeMobileBackdrop();
            this.showDesktopSearch();
        }

        // Refresh table responsiveness
        this.enhanceTableResponsiveness();
    }

    toggleMobileSidebar() {
        const body = document.body;
        const isOpen = body.classList.contains('sidebar-enable');
        
        if (isOpen) {
            this.closeMobileSidebar();
        } else {
            this.openMobileSidebar();
        }
    }

    openMobileSidebar() {
        document.body.classList.add('sidebar-enable');
        this.showMobileBackdrop();
        
        // Prevent body scroll when sidebar is open
        document.body.style.overflow = 'hidden';
        
        // Focus management for accessibility
        const firstNavLink = document.querySelector('.main-nav .nav-link');
        if (firstNavLink) {
            firstNavLink.focus();
        }
    }

    closeMobileSidebar() {
        document.body.classList.remove('sidebar-enable');
        this.removeMobileBackdrop();
        
        // Restore body scroll
        document.body.style.overflow = '';
        
        // Return focus to menu button
        const menuToggle = document.querySelector('.button-toggle-menu');
        if (menuToggle) {
            menuToggle.focus();
        }
    }

    showMobileBackdrop() {
        // Check if a theme backdrop already exists to avoid duplicates
        if (document.querySelector('.offcanvas-backdrop')) return;

        let backdrop = document.querySelector('.mobile-backdrop');
        if (!backdrop) {
            backdrop = document.createElement('div');
            backdrop.className = 'mobile-backdrop';
            backdrop.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.4);
                z-index: 1040;
                opacity: 0;
                transition: opacity 0.3s ease;
                backdrop-filter: blur(2px);
            `;
            document.body.appendChild(backdrop);
        }
        
        // Trigger animation
        setTimeout(() => {
            backdrop.style.opacity = '1';
        }, 10);
    }

    removeMobileBackdrop() {
        const backdrop = document.querySelector('.mobile-backdrop');
        if (backdrop) {
            backdrop.style.opacity = '0';
            setTimeout(() => {
                if (backdrop.parentNode) backdrop.remove();
            }, 300);
        }
        
        // Also cleanup body styles just in case
        if (!document.querySelector('.modal.show') && !document.querySelector('.offcanvas.show')) {
            document.body.style.overflow = '';
            document.body.classList.remove('sidebar-enable');
        }
    }

    setupMobileBackdrop() {
        // Initialize mobile backdrop functionality
        // This method sets up the backdrop for mobile sidebar overlay
        if (this.isMobile) {
            // Ensure no existing backdrop
            this.removeMobileBackdrop();
        }
    }

    hideMobileSearch() {
        const searchForm = document.querySelector('.app-search');
        if (searchForm) {
            searchForm.style.display = 'none';
        }
    }

    showDesktopSearch() {
        const searchForm = document.querySelector('.app-search');
        if (searchForm) {
            searchForm.style.display = '';
        }
    }

    enhanceTableResponsiveness() {
        const tables = document.querySelectorAll('table:not(.table-responsive table)');
        
        tables.forEach(table => {
            if (!table.closest('.table-responsive')) {
                const wrapper = document.createElement('div');
                wrapper.className = 'table-responsive';
                table.parentNode.insertBefore(wrapper, table);
                wrapper.appendChild(table);
            }

            // Add mobile-friendly table headers
            if (this.isMobile) {
                this.addMobileTableHeaders(table);
            }
        });
    }

    addMobileTableHeaders(table) {
        const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.textContent.trim());
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            cells.forEach((cell, index) => {
                if (headers[index] && !cell.getAttribute('data-label')) {
                    cell.setAttribute('data-label', headers[index]);
                }
            });
        });
    }

    setupTouchGestures() {
        if (!('ontouchstart' in window)) return;

        let startX = 0;
        let startY = 0;
        let isScrolling = false;

        document.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
            isScrolling = false;
        }, { passive: true });

        document.addEventListener('touchmove', (e) => {
            if (!startX || !startY) return;

            const diffX = e.touches[0].clientX - startX;
            const diffY = e.touches[0].clientY - startY;

            if (Math.abs(diffY) > Math.abs(diffX)) {
                isScrolling = true;
            }
        }, { passive: true });

        document.addEventListener('touchend', (e) => {
            if (!startX || !startY || isScrolling) return;

            const diffX = e.changedTouches[0].clientX - startX;
            const threshold = 100;

            // Swipe right to open sidebar (from left edge)
            if (diffX > threshold && startX < 50 && this.isMobile) {
                this.openMobileSidebar();
            }
            
            // Swipe left to close sidebar
            if (diffX < -threshold && document.body.classList.contains('sidebar-enable') && this.isMobile) {
                this.closeMobileSidebar();
            }

            startX = 0;
            startY = 0;
        }, { passive: true });
    }

    enhanceMobileForms() {
        const forms = document.querySelectorAll('form');
        
        forms.forEach(form => {
            // Add mobile-friendly input behaviors
            const inputs = form.querySelectorAll('input, select, textarea');
            
            inputs.forEach(input => {
                // Auto-scroll to input when focused on mobile
                if (this.isMobile) {
                    input.addEventListener('focus', () => {
                        setTimeout(() => {
                            input.scrollIntoView({ 
                                behavior: 'smooth', 
                                block: 'center' 
                            });
                        }, 300);
                    });
                }

                // Add loading state for form submissions
                form.addEventListener('submit', () => {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.classList.add('loading');
                        submitBtn.disabled = true;
                    }
                });
            });
        });
    }

    enhanceMobileDropdowns() {
        const dropdowns = document.querySelectorAll('.dropdown');
        
        dropdowns.forEach(dropdown => {
            const toggle = dropdown.querySelector('.dropdown-toggle');
            const menu = dropdown.querySelector('.dropdown-menu');
            
            if (toggle && menu && this.isMobile) {
                toggle.addEventListener('click', (e) => {
                    e.preventDefault();
                    
                    // Close other open dropdowns
                    document.querySelectorAll('.dropdown-menu.show').forEach(otherMenu => {
                        if (otherMenu !== menu) {
                            otherMenu.classList.remove('show');
                        }
                    });
                    
                    menu.classList.toggle('show');
                });
            }
        });

        // Close dropdowns when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.dropdown') && this.isMobile) {
                document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                    menu.classList.remove('show');
                });
            }
        });
    }

    // Utility function for debouncing
    debounce(func, wait) {
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

    // Public method to refresh responsive features
    refresh() {
        this.updateBreakpoints();
        this.handleResize();
        this.enhanceTableResponsiveness();
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.responsiveEnhancements = new ResponsiveEnhancements();
});

// Export for potential external use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ResponsiveEnhancements;
}