/**
 * Theme Toggle System for Smart Classroom
 * Supports light/dark mode with localStorage persistence
 */

class SmartClassroomTheme {
    constructor() {
        this.html = document.documentElement;
        this.storageKey = 'smart-classroom-theme';
        this.defaultTheme = 'light';
        
        // Color scheme
        this.colors = {
            primary: '#1561AD',
            secondary: '#4D774E', 
            accent: '#FBA92C',
            lightBg: '#F8FAFC',
            darkBg: '#0F172A'
        };
        
        this.init();
    }

    init() {
        // Load saved theme or detect system preference
        const savedTheme = this.getSavedTheme();
        this.setTheme(savedTheme);
        
        // Listen for system theme changes
        this.watchSystemTheme();
        
        // Create toggle button if it doesn't exist
        this.ensureToggleButton();
    }

    getSavedTheme() {
        const saved = localStorage.getItem(this.storageKey);
        if (saved) return saved;
        
        // Detect system preference
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            return 'dark';
        }
        
        return this.defaultTheme;
    }

    setTheme(theme) {
        // Validate theme
        if (!['light', 'dark'].includes(theme)) {
            theme = this.defaultTheme;
        }
        
        // Update HTML class
        this.html.classList.toggle('dark', theme === 'dark');
        
        // Save to localStorage
        localStorage.setItem(this.storageKey, theme);
        
        // Update toggle button if exists
        this.updateToggleButton(theme);
        
        // Dispatch event
        this.dispatchThemeEvent(theme);
        
        // Update meta theme-color for mobile browsers
        this.updateMetaThemeColor(theme);
    }

    toggle() {
        const currentTheme = this.getCurrentTheme();
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        this.setTheme(newTheme);
        return newTheme;
    }

    getCurrentTheme() {
        return this.html.classList.contains('dark') ? 'dark' : 'light';
    }

    updateToggleButton(theme) {
        const button = document.getElementById('themeToggle');
        const sunIcon = document.getElementById('sunIcon');
        const moonIcon = document.getElementById('moonIcon');
        
        if (!button || !sunIcon || !moonIcon) return;
        
        // Update icons
        if (theme === 'dark') {
            sunIcon.classList.add('hidden');
            moonIcon.classList.remove('hidden');
        } else {
            moonIcon.classList.add('hidden');
            sunIcon.classList.remove('hidden');
        }
        
        // Add animation
        button.classList.add('animate-pulse');
        setTimeout(() => button.classList.remove('animate-pulse'), 300);
    }

    ensureToggleButton() {
        let button = document.getElementById('themeToggle');
        
        if (!button) {
            button = this.createToggleButton();
            // Try to append to header or body
            const header = document.querySelector('header, nav, .header');
            if (header) {
                header.appendChild(button);
            } else {
                document.body.appendChild(button);
            }
        }
        
        // Add event listeners
        button.addEventListener('click', () => this.toggle());
        button.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.toggle();
            }
        });
    }

    createToggleButton() {
        const button = document.createElement('button');
        button.id = 'themeToggle';
        button.className = 'p-2 rounded-full bg-white/10 dark:bg-gray-700 hover:bg-accent/20 dark:hover:bg-accent/20 transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-accent';
        button.setAttribute('aria-label', 'Toggle theme');
        
        button.innerHTML = `
            <!-- Sun Icon (Light Mode) -->
            <svg id="sunIcon" class="w-5 h-5 text-primary dark:hidden transition-all duration-300" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd" />
            </svg>
            
            <!-- Moon Icon (Dark Mode) -->
            <svg id="moonIcon" class="w-5 h-5 text-accent hidden dark:block transition-all duration-300" fill="currentColor" viewBox="0 0 20 20">
                <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z" />
            </svg>
        `;
        
        return button;
    }

    watchSystemTheme() {
        if (!window.matchMedia) return;
        
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        mediaQuery.addEventListener('change', (e) => {
            // Only auto-switch if user hasn't manually set a preference
            if (!localStorage.getItem(this.storageKey)) {
                this.setTheme(e.matches ? 'dark' : 'light');
            }
        });
    }

    dispatchThemeEvent(theme) {
        window.dispatchEvent(new CustomEvent('themeChanged', { 
            detail: { theme } 
        }));
    }

    updateMetaThemeColor(theme) {
        let metaThemeColor = document.querySelector('meta[name="theme-color"]');
        
        if (!metaThemeColor) {
            metaThemeColor = document.createElement('meta');
            metaThemeColor.name = 'theme-color';
            document.head.appendChild(metaThemeColor);
        }
        
        metaThemeColor.content = theme === 'dark' ? this.colors.darkBg : this.colors.lightBg;
    }
}

// Initialize theme system when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.smartClassroomTheme = new SmartClassroomTheme();
    
    // Optional: Listen for theme changes
    window.addEventListener('themeChanged', (e) => {
        console.log(`Theme changed to: ${e.detail.theme}`);
    });
    
    // Simple theme toggle for pages without the class
    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');
    
    if (themeToggle && !window.smartClassroomTheme) {
        // Load saved theme
        const savedTheme = localStorage.getItem('smart-classroom-theme') || 'light';
        document.documentElement.classList.toggle('dark', savedTheme === 'dark');
        if (themeIcon) {
            themeIcon.className = savedTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        }
        
        themeToggle.addEventListener('click', () => {
            const isDark = document.documentElement.classList.toggle('dark');
            const newTheme = isDark ? 'dark' : 'light';
            localStorage.setItem('smart-classroom-theme', newTheme);
            if (themeIcon) {
                themeIcon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
            }
        });
    }
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SmartClassroomTheme;
}
