// Homepage Management
document.addEventListener('DOMContentLoaded', function() {
    // Initialize variables
    const searchInput = document.getElementById('searchInput');
    const searchBtn = document.getElementById('searchBtn');
    const gridView = document.getElementById('gridView');
    const listView = document.getElementById('listView');
    const fishGrid = document.getElementById('fishGrid');
    const loading = document.getElementById('loading');
    const noResults = document.getElementById('noResults');
    
    // Bind events
    if (searchBtn) {
        searchBtn.addEventListener('click', function(e) {
            if (searchInput.value.trim() === '') {
                e.preventDefault();
            }
        });
    }

    // View toggle functionality
    if (gridView && listView && fishGrid) {
        gridView.addEventListener('click', function() {
            setView('grid');
        });
        
        listView.addEventListener('click', function() {
            setView('list');
        });
    }
    
    function setView(view) {
        // Update button states
        if (gridView && listView) {
            gridView.classList.toggle('active', view === 'grid');
            listView.classList.toggle('active', view === 'list');
        }
        
        // Update grid class
        if (fishGrid) {
            fishGrid.classList.toggle('list-view', view === 'list');
            
            // Save preference to localStorage
            localStorage.setItem('fish_view', view);
            
            // Animate cards
            animateCards();
        }
    }
    
    function animateCards() {
        if (!fishGrid) return;
        
        const cards = fishGrid.querySelectorAll('.fish-card');
        cards.forEach((card, index) => {
            card.style.animationDelay = `${index * 50}ms`;
            card.classList.remove('fade-in-up');
            
            // Force reflow
            card.offsetHeight;
            
            card.classList.add('fade-in-up');
        });
    }
    
    // Load saved view preference
    const savedView = localStorage.getItem('fish_view');
    if (savedView) {
        setView(savedView);
    }
    
    // Initialize animations
    animateCards();
    
    // Add intersection observer for scroll animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in');
            }
        });
    }, observerOptions);
    
    // Observe elements when they come into view
    const animatedElements = document.querySelectorAll('.fish-card, .hero-content');
    animatedElements.forEach(el => observer.observe(el));
});

// Add smooth scrolling for anchor links
document.addEventListener('click', (e) => {
    if (e.target.matches('a[href^="#"]')) {
        e.preventDefault();
        const target = document.querySelector(e.target.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    }
});
