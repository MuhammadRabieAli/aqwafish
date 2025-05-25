// Navigation Management
class NavigationManager {
    constructor() {
        this.hamburger = document.getElementById('navHamburger');
        this.navMenu = document.querySelector('.nav-menu');
        this.navLinks = document.querySelectorAll('.nav-link');
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.setActiveLink();
    }
    
    bindEvents() {
        if (this.hamburger) {
            this.hamburger.addEventListener('click', () => this.toggleMobileMenu());
        }
        
        // Close mobile menu when clicking nav links
        this.navLinks.forEach(link => {
            link.addEventListener('click', () => this.closeMobileMenu());
        });
        
        // Close mobile menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!this.hamburger.contains(e.target) && !this.navMenu.contains(e.target)) {
                this.closeMobileMenu();
            }
        });
    }
    
    toggleMobileMenu() {
        this.navMenu.classList.toggle('mobile-open');
        this.hamburger.classList.toggle('active');
    }
    
    closeMobileMenu() {
        this.navMenu.classList.remove('mobile-open');
        this.hamburger.classList.remove('active');
    }
    
    setActiveLink() {
        const currentPath = window.location.pathname;
        this.navLinks.forEach(link => {
            const linkPath = new URL(link.href).pathname;
            if (linkPath === currentPath) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });
    }
}

// Mobile Navigation Styles
const mobileNavStyles = `
    @media (max-width: 767px) {
        .nav-menu {
            position: fixed;
            top: 64px;
            left: 0;
            right: 0;
            background: var(--background);
            border-bottom: 1px solid var(--border);
            flex-direction: column;
            padding: var(--spacing-lg);
            transform: translateY(-100%);
            opacity: 0;
            visibility: hidden;
            transition: all var(--transition-normal);
            box-shadow: 0 4px 12px var(--shadow);
        }
        
        .nav-menu.mobile-open {
            transform: translateY(0);
            opacity: 1;
            visibility: visible;
        }
        
        .nav-hamburger.active span:nth-child(1) {
            transform: rotate(45deg) translate(5px, 5px);
        }
        
        .nav-hamburger.active span:nth-child(2) {
            opacity: 0;
        }
        
        .nav-hamburger.active span:nth-child(3) {
            transform: rotate(-45deg) translate(7px, -6px);
        }
    }
`;

// Inject mobile navigation styles
const styleSheet = document.createElement('style');
styleSheet.textContent = mobileNavStyles;
document.head.appendChild(styleSheet);

// Initialize navigation manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new NavigationManager();
});

// Mobile navigation menu functionality
document.addEventListener('DOMContentLoaded', function() {
    const navHamburger = document.getElementById('navHamburger');
    const navMenu = document.querySelector('.nav-menu');
    
    // Toggle mobile menu when hamburger is clicked
    navHamburger.addEventListener('click', function() {
        navHamburger.classList.toggle('active');
        navMenu.classList.toggle('active');
        
        // Add a class to body to prevent scrolling when menu is open
        document.body.classList.toggle('menu-open');
    });
    
    // Close menu when clicking outside
    document.addEventListener('click', function(event) {
        if (!navMenu.contains(event.target) && !navHamburger.contains(event.target) && navMenu.classList.contains('active')) {
            navHamburger.classList.remove('active');
            navMenu.classList.remove('active');
            document.body.classList.remove('menu-open');
        }
    });
    
    // Close menu when window is resized to desktop size
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 768 && navMenu.classList.contains('active')) {
            navHamburger.classList.remove('active');
            navMenu.classList.remove('active');
            document.body.classList.remove('menu-open');
        }
    });
});
