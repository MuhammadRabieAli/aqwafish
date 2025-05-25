
// Fish Details Management
class FishDetailsManager {
    constructor() {
        this.fishData = window.fishDatabase || [];
        this.currentFish = null;
        this.currentImageIndex = 0;
        this.images = [];
        
        this.fishContent = document.getElementById('fishContent');
        this.loadingState = document.getElementById('loadingState');
        this.errorState = document.getElementById('errorState');
        this.relatedFish = document.getElementById('relatedFish');
        this.relatedGrid = document.getElementById('relatedGrid');
        
        this.init();
    }
    
    init() {
        this.loadFishData();
        this.getFishFromUrl();
        this.renderFishDetails();
    }
    
    loadFishData() {
        // If fishDatabase is not available, load from localStorage or use fallback data
        if (!this.fishData.length) {
            this.fishData = this.getFallbackData();
        }
    }
    
    getFallbackData() {
        return [
            {
                id: 1,
                name: "Atlantic Salmon",
                scientificName: "Salmo salar",
                family: "salmonidae",
                environment: "saltwater",
                size: "large",
                description: "A species of ray-finned fish in the family Salmonidae. Atlantic salmon are anadromous, which means they live in the sea but return to fresh water to reproduce. They are an important species both commercially and recreationally.",
                images: [
                    "https://images.unsplash.com/photo-1544943910-4c1dc44aab44?w=800&h=600&fit=crop",
                    "https://images.unsplash.com/photo-1559827260-dc66d52bef19?w=800&h=600&fit=crop",
                    "https://images.unsplash.com/photo-1583212292454-1fe6229603b7?w=800&h=600&fit=crop"
                ],
                habitat: "North Atlantic Ocean and rivers",
                diet: "Crustaceans, small fish, insects",
                lifespan: "4-8 years",
                maxLength: "150 cm",
                conservation: "Least Concern",
                characteristics: {
                    body: "Streamlined, silver body with dark spots",
                    fins: "Strong tail fin for swimming upstream",
                    behavior: "Anadromous - spawns in freshwater",
                    reproduction: "Returns to natal streams to spawn"
                }
            },
            {
                id: 2,
                name: "Clownfish",
                scientificName: "Amphiprioninae",
                family: "percidae", 
                environment: "saltwater",
                size: "small",
                description: "Colorful marine fish known for their symbiotic relationship with sea anemones. Popular in aquariums worldwide due to their vibrant colors and interesting behavior patterns.",
                images: [
                    "https://images.unsplash.com/photo-1520637836862-4d197d17c55a?w=800&h=600&fit=crop",
                    "https://images.unsplash.com/photo-1559827260-dc66d52bef19?w=800&h=600&fit=crop"
                ],
                habitat: "Coral reefs in warm waters",
                diet: "Algae, zooplankton, small crustaceans",
                lifespan: "6-10 years",
                maxLength: "11 cm",
                conservation: "Stable",
                characteristics: {
                    body: "Orange with white stripes and black borders",
                    fins: "Rounded fins adapted for maneuvering",
                    behavior: "Lives symbiotically with sea anemones",
                    reproduction: "Sequential hermaphrodites"
                }
            }
        ];
    }
    
    getFishFromUrl() {
        const urlParams = new URLSearchParams(window.location.search);
        const fishId = parseInt(urlParams.get('id'));
        
        if (fishId) {
            this.currentFish = this.fishData.find(fish => fish.id === fishId);
        }
    }
    
    renderFishDetails() {
        setTimeout(() => {
            this.loadingState.style.display = 'none';
            
            if (!this.currentFish) {
                this.errorState.style.display = 'block';
                return;
            }
            
            this.images = this.currentFish.images || [this.currentFish.image];
            this.renderFishContent();
            this.renderRelatedFish();
            this.bindEvents();
            
            this.fishContent.classList.add('loaded');
            this.relatedFish.style.display = 'block';
            
            // Update page title and breadcrumb
            document.title = `${this.currentFish.name} - AquaBase`;
            document.getElementById('fishName').textContent = this.currentFish.name;
        }, 800);
    }
    
    renderFishContent() {
        this.fishContent.innerHTML = `
            <div class="fish-header">
                <div class="fish-gallery">
                    <img src="${this.images[0]}" alt="${this.currentFish.name}" class="main-image" id="mainImage">
                    ${this.images.length > 1 ? `
                        <div class="image-counter">
                            <span id="imageCounter">1</span> / ${this.images.length}
                        </div>
                        <button class="gallery-nav prev" id="prevImage">‹</button>
                        <button class="gallery-nav next" id="nextImage">›</button>
                    ` : ''}
                    ${this.images.length > 1 ? `
                        <div class="thumbnail-strip">
                            ${this.images.map((img, index) => `
                                <img src="${img}" alt="${this.currentFish.name} ${index + 1}" 
                                     class="thumbnail ${index === 0 ? 'active' : ''}" 
                                     data-index="${index}">
                            `).join('')}
                        </div>
                    ` : ''}
                </div>
                
                <div class="fish-info">
                    <h1 class="fish-title">${this.currentFish.name}</h1>
                    <p class="fish-scientific">${this.currentFish.scientificName}</p>
                    
                    <div class="fish-badges">
                        <span class="badge badge-info">${this.formatFamily(this.currentFish.family)}</span>
                        <span class="badge badge-${this.getEnvironmentColor(this.currentFish.environment)}">${this.formatEnvironment(this.currentFish.environment)}</span>
                        <span class="badge badge-secondary">${this.formatSize(this.currentFish.size)}</span>
                        ${this.currentFish.conservation ? `<span class="badge badge-success">${this.currentFish.conservation}</span>` : ''}
                    </div>
                    
                    <p class="fish-description">${this.currentFish.description}</p>
                    
                    <div class="quick-facts">
                        <h3>Quick Facts</h3>
                        <div class="facts-grid">
                            ${this.currentFish.habitat ? `
                                <div class="fact-item">
                                    <span class="fact-label">Habitat</span>
                                    <span class="fact-value">${this.currentFish.habitat}</span>
                                </div>
                            ` : ''}
                            ${this.currentFish.diet ? `
                                <div class="fact-item">
                                    <span class="fact-label">Diet</span>
                                    <span class="fact-value">${this.currentFish.diet}</span>
                                </div>
                            ` : ''}
                            ${this.currentFish.lifespan ? `
                                <div class="fact-item">
                                    <span class="fact-label">Lifespan</span>
                                    <span class="fact-value">${this.currentFish.lifespan}</span>
                                </div>
                            ` : ''}
                            ${this.currentFish.maxLength ? `
                                <div class="fact-item">
                                    <span class="fact-label">Max Length</span>
                                    <span class="fact-value">${this.currentFish.maxLength}</span>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            </div>
            
            ${this.currentFish.characteristics ? this.renderDetailedInfo() : ''}
        `;
    }
    
    renderDetailedInfo() {
        return `
            <div class="detailed-info">
                <div class="info-tabs">
                    <button class="tab-button active" data-tab="characteristics">Characteristics</button>
                    <button class="tab-button" data-tab="habitat">Habitat & Diet</button>
                    <button class="tab-button" data-tab="conservation">Conservation</button>
                </div>
                
                <div class="tab-content active" id="characteristics">
                    <div class="characteristics-list">
                        ${Object.entries(this.currentFish.characteristics).map(([key, value]) => `
                            <div class="characteristic-item">
                                <h5>${this.formatCharacteristic(key)}</h5>
                                <p>${value}</p>
                            </div>
                        `).join('')}
                    </div>
                </div>
                
                <div class="tab-content" id="habitat">
                    <h4>Habitat</h4>
                    <p>${this.currentFish.habitat || 'Information about habitat not available.'}</p>
                    <h4>Diet</h4>
                    <p>${this.currentFish.diet || 'Information about diet not available.'}</p>
                </div>
                
                <div class="tab-content" id="conservation">
                    <h4>Conservation Status</h4>
                    <p>${this.currentFish.conservation || 'Conservation status not specified.'}</p>
                    <p>Learn more about fish conservation efforts and how you can help protect marine ecosystems.</p>
                </div>
            </div>
        `;
    }
    
    renderRelatedFish() {
        const related = this.fishData
            .filter(fish => 
                fish.id !== this.currentFish.id && 
                (fish.family === this.currentFish.family || fish.environment === this.currentFish.environment)
            )
            .slice(0, 4);
        
        if (related.length === 0) {
            this.relatedFish.style.display = 'none';
            return;
        }
        
        this.relatedGrid.innerHTML = related.map(fish => `
            <a href="/fish.html?id=${fish.id}" class="related-card">
                <img src="${fish.images ? fish.images[0] : fish.image}" alt="${fish.name}" loading="lazy">
                <div class="related-card-content">
                    <h4>${fish.name}</h4>
                    <p>${fish.scientificName}</p>
                </div>
            </a>
        `).join('');
    }
    
    bindEvents() {
        // Image gallery navigation
        const prevBtn = document.getElementById('prevImage');
        const nextBtn = document.getElementById('nextImage');
        const thumbnails = document.querySelectorAll('.thumbnail');
        
        if (prevBtn && nextBtn) {
            prevBtn.addEventListener('click', () => this.changeImage(-1));
            nextBtn.addEventListener('click', () => this.changeImage(1));
        }
        
        thumbnails.forEach(thumb => {
            thumb.addEventListener('click', (e) => {
                const index = parseInt(e.target.dataset.index);
                this.setImage(index);
            });
        });
        
        // Tab navigation
        const tabButtons = document.querySelectorAll('.tab-button');
        tabButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                const tabId = e.target.dataset.tab;
                this.switchTab(tabId);
            });
        });
        
        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (this.images.length > 1) {
                if (e.key === 'ArrowLeft') this.changeImage(-1);
                if (e.key === 'ArrowRight') this.changeImage(1);
            }
        });
    }
    
    changeImage(direction) {
        this.currentImageIndex += direction;
        
        if (this.currentImageIndex < 0) {
            this.currentImageIndex = this.images.length - 1;
        } else if (this.currentImageIndex >= this.images.length) {
            this.currentImageIndex = 0;
        }
        
        this.setImage(this.currentImageIndex);
    }
    
    setImage(index) {
        this.currentImageIndex = index;
        
        const mainImage = document.getElementById('mainImage');
        const imageCounter = document.getElementById('imageCounter');
        const thumbnails = document.querySelectorAll('.thumbnail');
        
        if (mainImage) {
            mainImage.src = this.images[index];
            mainImage.style.opacity = '0';
            
            setTimeout(() => {
                mainImage.style.opacity = '1';
            }, 150);
        }
        
        if (imageCounter) {
            imageCounter.textContent = index + 1;
        }
        
        thumbnails.forEach((thumb, i) => {
            thumb.classList.toggle('active', i === index);
        });
    }
    
    switchTab(tabId) {
        // Update tab buttons
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.tab === tabId);
        });
        
        // Update tab content
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.toggle('active', content.id === tabId);
        });
    }
    
    formatFamily(family) {
        return family.charAt(0).toUpperCase() + family.slice(1);
    }
    
    formatEnvironment(environment) {
        return environment.charAt(0).toUpperCase() + environment.slice(1);
    }
    
    formatSize(size) {
        const sizeMap = {
            small: 'Small',
            medium: 'Medium',
            large: 'Large'
        };
        return sizeMap[size] || size;
    }
    
    formatCharacteristic(key) {
        return key.charAt(0).toUpperCase() + key.slice(1);
    }
    
    getEnvironmentColor(environment) {
        const colorMap = {
            freshwater: 'info',
            saltwater: 'success',
            brackish: 'warning'
        };
        return colorMap[environment] || 'secondary';
    }
}

// Initialize fish details manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new FishDetailsManager();
});
