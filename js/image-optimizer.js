// ============================================================
// AUTOMATIC IMAGE RESIZE & OPTIMIZATION SYSTEM
// Hotel Senang Hati - JavaScript Image Management
// ============================================================

class ImageOptimizer {
    constructor() {
        this.init();
    }

    init() {
        // Initialize image optimization when DOM is loaded
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setupImageOptimization());
        } else {
            this.setupImageOptimization();
        }
    }

    setupImageOptimization() {
        this.setupLazyLoading();
        this.setupImageErrorHandling();
        this.setupImageResize();
        this.setupImagePreloader();
        this.setupResponsiveImages();
    }

    // ============================================================
    // LAZY LOADING IMPLEMENTATION
    // ============================================================
    setupLazyLoading() {
        // Check if Intersection Observer is supported
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        this.loadImage(img);
                        observer.unobserve(img);
                    }
                });
            }, {
                rootMargin: '50px 0px',
                threshold: 0.01
            });

            // Observe all images with loading="lazy"
            document.querySelectorAll('img[loading="lazy"]').forEach(img => {
                imageObserver.observe(img);
            });
        } else {
            // Fallback for browsers without Intersection Observer
            this.loadAllImages();
        }
    }

    loadImage(img) {
        const src = img.dataset.src || img.src;
        if (src) {
            img.src = src;
            img.classList.add('loaded');
        }
    }

    loadAllImages() {
        document.querySelectorAll('img[loading="lazy"]').forEach(img => {
            this.loadImage(img);
        });
    }

    // ============================================================
    // IMAGE ERROR HANDLING
    // ============================================================
    setupImageErrorHandling() {
        document.addEventListener('error', (e) => {
            if (e.target.tagName === 'IMG') {
                this.handleImageError(e.target);
            }
        }, true);
    }

    handleImageError(img) {
        // Add error class for styling
        img.classList.add('image-error');
        
        // Set fallback image based on context
        const fallbackSrc = this.getFallbackImage(img);
        if (fallbackSrc && img.src !== fallbackSrc) {
            img.src = fallbackSrc;
        } else {
            // Create placeholder element
            this.createImagePlaceholder(img);
        }
    }

    getFallbackImage(img) {
        // Determine fallback based on image context
        if (img.closest('.hotel-room-item') || img.closest('.fruite-item')) {
            return 'img/single-item.jpg'; // Room fallback
        } else if (img.closest('.amenity-item')) {
            return 'img/featur-1.jpg'; // Amenity fallback
        } else if (img.classList.contains('avatar-image')) {
            return 'img/avatar.jpg'; // Avatar fallback
        }
        return null;
    }

    createImagePlaceholder(img) {
        const placeholder = document.createElement('div');
        placeholder.className = 'image-placeholder';
        placeholder.style.width = img.offsetWidth + 'px';
        placeholder.style.height = img.offsetHeight + 'px';
        placeholder.innerHTML = '<i class="fas fa-image"></i>';
        img.parentNode.replaceChild(placeholder, img);
    }

    // ============================================================
    // AUTOMATIC IMAGE RESIZE
    // ============================================================
    setupImageResize() {
        window.addEventListener('resize', this.debounce(() => {
            this.resizeImages();
        }, 250));

        // Initial resize
        this.resizeImages();
    }

    resizeImages() {
        // Resize room images
        this.resizeRoomImages();
        
        // Resize amenity images
        this.resizeAmenityImages();
        
        // Resize hero images
        this.resizeHeroImages();
    }

    resizeRoomImages() {
        const roomImages = document.querySelectorAll('.hotel-room-item .room-img img, .fruite-item .fruite-img img');
        const targetHeight = this.getResponsiveHeight('room');
        
        roomImages.forEach(img => {
            img.style.height = targetHeight + 'px';
        });
    }

    resizeAmenityImages() {
        const amenityImages = document.querySelectorAll('.amenity-item .amenity-img img');
        const targetHeight = this.getResponsiveHeight('amenity');
        
        amenityImages.forEach(img => {
            img.style.height = targetHeight + 'px';
        });
    }

    resizeHeroImages() {
        const heroSection = document.querySelector('.hero-section');
        if (heroSection) {
            const targetHeight = window.innerHeight * 0.6; // 60vh
            heroSection.style.minHeight = targetHeight + 'px';
        }
    }

    getResponsiveHeight(type) {
        const screenWidth = window.innerWidth;
        
        if (type === 'room') {
            if (screenWidth < 768) return 200;      // Mobile
            if (screenWidth < 992) return 230;      // Tablet
            if (screenWidth < 1200) return 250;     // Desktop
            return 280;                             // Large Desktop
        }
        
        if (type === 'amenity') {
            if (screenWidth < 768) return 180;      // Mobile
            if (screenWidth < 992) return 200;      // Tablet
            if (screenWidth < 1200) return 220;     // Desktop
            return 250;                             // Large Desktop
        }
        
        return 200; // Default
    }

    // ============================================================
    // IMAGE PRELOADER
    // ============================================================
    setupImagePreloader() {
        // Preload critical images
        const criticalImages = [
            'img/hero-img.jpg',
            'img/best-product-1.jpg',
            'img/best-product-2.jpg',
            'img/best-product-3.jpg'
        ];

        criticalImages.forEach(src => this.preloadImage(src));
    }

    preloadImage(src) {
        const img = new Image();
        img.onload = () => {
            console.log(`Preloaded: ${src}`);
        };
        img.onerror = () => {
            console.warn(`Failed to preload: ${src}`);
        };
        img.src = src;
    }

    // ============================================================
    // RESPONSIVE IMAGES
    // ============================================================
    setupResponsiveImages() {
        // Setup responsive image switching
        window.addEventListener('resize', this.debounce(() => {
            this.updateResponsiveImages();
        }, 250));

        this.updateResponsiveImages();
    }

    updateResponsiveImages() {
        const images = document.querySelectorAll('img[data-src-mobile], img[data-src-tablet], img[data-src-desktop]');
        
        images.forEach(img => {
            const screenWidth = window.innerWidth;
            let targetSrc = img.src;

            if (screenWidth < 768 && img.dataset.srcMobile) {
                targetSrc = img.dataset.srcMobile;
            } else if (screenWidth < 1200 && img.dataset.srcTablet) {
                targetSrc = img.dataset.srcTablet;
            } else if (img.dataset.srcDesktop) {
                targetSrc = img.dataset.srcDesktop;
            }

            if (img.src !== targetSrc) {
                img.src = targetSrc;
            }
        });
    }

    // ============================================================
    // IMAGE COMPRESSION & OPTIMIZATION
    // ============================================================
    compressImage(file, maxWidth = 1200, quality = 0.8) {
        return new Promise((resolve) => {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            const img = new Image();

            img.onload = () => {
                // Calculate new dimensions
                const ratio = Math.min(maxWidth / img.width, maxWidth / img.height);
                canvas.width = img.width * ratio;
                canvas.height = img.height * ratio;

                // Draw and compress
                ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                canvas.toBlob(resolve, 'image/jpeg', quality);
            };

            img.src = URL.createObjectURL(file);
        });
    }

    // ============================================================
    // UTILITY FUNCTIONS
    // ============================================================
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

    // ============================================================
    // PUBLIC API
    // ============================================================
    
    // Method to manually trigger image optimization
    optimizeImages() {
        this.resizeImages();
        this.updateResponsiveImages();
    }

    // Method to add new images dynamically
    addImage(imgElement) {
        if (imgElement.hasAttribute('loading')) {
            this.setupLazyLoading();
        }
        this.resizeImages();
    }

    // Method to refresh all images
    refreshImages() {
        const images = document.querySelectorAll('img');
        images.forEach(img => {
            const src = img.src;
            img.src = '';
            img.src = src;
        });
    }
}

// ============================================================
// INITIALIZE IMAGE OPTIMIZER
// ============================================================

// Auto-initialize when script loads
const imageOptimizer = new ImageOptimizer();

// Make it globally available
window.ImageOptimizer = imageOptimizer;

// ============================================================
// ADDITIONAL HELPER FUNCTIONS
// ============================================================

// Function to convert images to WebP format (if supported)
function convertToWebP(imageSrc) {
    return new Promise((resolve) => {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        const img = new Image();

        img.onload = () => {
            canvas.width = img.width;
            canvas.height = img.height;
            ctx.drawImage(img, 0, 0);
            
            canvas.toBlob((blob) => {
                if (blob) {
                    resolve(URL.createObjectURL(blob));
                } else {
                    resolve(imageSrc); // Fallback to original
                }
            }, 'image/webp', 0.8);
        };

        img.onerror = () => resolve(imageSrc);
        img.src = imageSrc;
    });
}

// Function to check WebP support
function supportsWebP() {
    const canvas = document.createElement('canvas');
    canvas.width = 1;
    canvas.height = 1;
    return canvas.toDataURL('image/webp').indexOf('data:image/webp') === 0;
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ImageOptimizer;
}
