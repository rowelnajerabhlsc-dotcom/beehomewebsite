/* ============================================================
   LAZY LOADING WITH SKELETON PLACEHOLDERS
   Automatically adds skeleton placeholders to images and
   loads them when they enter the viewport
   ============================================================ */

document.addEventListener('DOMContentLoaded', function() {
    // Check if IntersectionObserver is supported
    if (!('IntersectionObserver' in window)) {
        // Fallback for browsers that don't support IntersectionObserver
        lazyLoadFallback();
        return;
    }

    // Initialize lazy loading for images
    initImageLazyLoading();
    // Initialize lazy loading for background images (if any)
    initBackgroundLazyLoading();
});

/**
 * Initialize lazy loading for <img> elements
 */
function initImageLazyLoading() {
    const images = document.querySelectorAll('img[data-src], img.lazy-load');

    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                loadImage(img);
                observer.unobserve(img);
            }
        });
    }, {
        rootMargin: '50px 0px', // Start loading 50px before viewport
        threshold: 0.01
    });

    images.forEach(img => {
        // Skip if already processed
        if (img.getAttribute('data-processed') === 'true') return;

        // Add loading state
        addLoadingState(img);

        // Mark as processed
        img.setAttribute('data-processed', 'true');

        // Start observing
        imageObserver.observe(img);
    });
}

/**
 * Initialize lazy loading for elements with background images
 */
function initBackgroundLazyLoading() {
    const elements = document.querySelectorAll('[data-bg], .lazy-bg');

    const bgObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const element = entry.target;
                loadBackgroundImage(element);
                observer.unobserve(element);
            }
        });
    }, {
        rootMargin: '50px 0px',
        threshold: 0.01
    });

    elements.forEach(element => {
        // Skip if already processed
        if (element.getAttribute('data-processed') === 'true') return;

        // Add loading state
        addLoadingState(element);

        // Mark as processed
        element.setAttribute('data-processed', 'true');

        // Start observing
        bgObserver.observe(element);
    });
}

/**
 * Add skeleton loading state to an element
 */
function addLoadingState(element) {
    // For images, wrap in a container if not already wrapped
    if (element.tagName === 'IMG') {
        // Check if already wrapped
        if (element.parentElement.classList.contains('lazy-load-container')) {
            return;
        }

        // Create wrapper
        const wrapper = document.createElement('div');
        wrapper.className = 'lazy-load-container';

        // Determine skeleton type based on image attributes or parent
        let skeletonClass = 'skeleton-rectangle';

        // Check for specific classes or data attributes
        if (element.classList.contains('avatar') || element.dataset.type === 'avatar') {
            skeletonClass = 'skeleton-avatar';
            if (element.dataset.size === 'sm') skeletonClass = 'skeleton-avatar-sm';
            if (element.dataset.size === 'lg') skeletonClass = 'skeleton-avatar-lg';
        } else if (element.classList.contains('thumbnail') ||
                  (element.width && element.width > 200)) {
            skeletonClass = 'skeleton-thumbnail';
            if (element.dataset.size === 'sm') skeletonClass = 'skeleton-thumbnail-sm';
        } else if (element.closest('.orbit-icon') ||
                  element.closest('.service-box img') ||
                  element.closest('.affiliation-logos img') ||
                  element.closest('.proud-logos img')) {
            skeletonClass = 'skeleton-avatar';
        }

        // Create skeleton element
        const skeleton = document.createElement('div');
        skeleton.className = `skeleton-loader ${skeletonClass}`;

        // Set dimensions if available
        if (element.width && element.height) {
            skeleton.style.width = `${element.width}px`;
            skeleton.style.height = `${element.height}px`;
        } else if (element.dataset.width && element.dataset.height) {
            skeleton.style.width = `${element.dataset.width}px`;
            skeleton.style.height = `${element.dataset.height}px`;
        } else {
            // Default dimensions based on context
            if (skeletonClass.includes('avatar')) {
                skeleton.style.width = '40px';
                skeleton.style.height = '40px';
            } else if (skeletonClass.includes('thumbnail')) {
                skeleton.style.width = '100%';
                skeleton.style.height = '180px';
            } else {
                skeleton.style.width = '100%';
                skeleton.style.height = '200px';
            }
        }

        // Wrap the image
        element.parentNode.insertBefore(wrapper, element);
        wrapper.appendChild(skeleton);
        wrapper.appendChild(element);

        // Add loader class to wrapper
        wrapper.classList.add('lazy-load-wrapper');
    } else {
        // For non-img elements (background images, etc.)
        element.classList.add('skeleton-loader');

        // Determine appropriate skeleton class
        if (element.classList.contains('hero') ||
            element.classList.contains('services-intro') ||
            element.classList.contains('about')) {
            element.classList.add('skeleton-rectangle');
        }
    }
}

/**
 * Load the actual image
 */
function loadImage(img) {
    // Get the actual src from data-src or src attribute
    const src = img.dataset.src || img.src;

    if (!src) {
        // If no src, remove loading state to prevent perpetual skeleton
        removeLoadingState(img);
        return;
    }

    // Create image object to preload
    const imgObj = new Image();
    imgObj.onload = () => {
        // Replace the src attribute
        img.src = src;

        // Remove loading state after a brief delay to let transition work
        setTimeout(() => {
            const wrapper = img.closest('.lazy-load-container');
            if (wrapper) {
                wrapper.classList.add('loaded');

                // Remove skeleton after transition ends
                setTimeout(() => {
                    const skeleton = wrapper.querySelector('.skeleton-loader');
                    if (skeleton) {
                        skeleton.remove();
                    }
                }, 300);
            }

            // Add loaded class to image
            img.classList.add('loaded');
        }, 50);
    };
    imgObj.onerror = () => {
        // Handle error - remove loading state to prevent perpetual skeleton
        console.warn(`Failed to load image: ${src}`);
        removeLoadingState(img);
    };
    imgObj.src = src;
}

/**
 * Remove loading state from an image element
 * Used when there's an error or we need to clean up
 */
function removeLoadingState(img) {
    const wrapper = img.closest('.lazy-load-container');
    if (wrapper) {
        // Remove skeleton immediately
        const skeleton = wrapper.querySelector('.skeleton-loader');
        if (skeleton) {
            skeleton.remove();
        }
        // Remove loader class from wrapper
        wrapper.classList.remove('lazy-load-wrapper');
    }
    // Remove loaded class from image
    img.classList.remove('loaded');
}

/**
 * Load background image for an element
 */
function loadBackgroundImage(element) {
    const bgUrl = element.dataset.bg ||
                  window.getComputedStyle(element).getPropertyValue('background-image');

    if (!bgUrl || bgUrl === 'none') {
        // If no background URL, remove loading state to prevent perpetual skeleton
        removeBackgroundLoadingState(element);
        return;
    }

    // Extract URL from url(...) format if needed
    const cleanUrl = bgUrl.replace(/^url\(['"]?/, '').replace(/['"]?\)$/, '');

    if (!cleanUrl || cleanUrl === 'none') {
        // If no valid URL after cleaning, remove loading state
        removeBackgroundLoadingState(element);
        return;
    }

    // Create image object to preload
    const imgObj = new Image();
    imgObj.onload = () => {
        // Apply the background image
        element.style.backgroundImage = `url('${cleanUrl}')`;

        // Remove loading state
        setTimeout(() => {
            removeBackgroundLoadingState(element);
        }, 300);
    };
    imgObj.onerror = () => {
        console.warn(`Failed to load background image: ${cleanUrl}`);
        removeBackgroundLoadingState(element);
    };
    imgObj.src = cleanUrl;
}

/**
 * Remove loading state from a background element
 * Used when there's an error or we need to clean up
 */
function removeBackgroundLoadingState(element) {
    element.classList.remove('skeleton-loader');
    // Remove any skeleton classes
    element.classList.remove('skeleton-rectangle', 'skeleton-circle', 'skeleton-text');
}

/**
 * Fallback for browsers without IntersectionObserver support
 * Uses scroll and resize events with throttling
 */
function lazyLoadFallback() {
    const lazyElements = document.querySelectorAll('img[data-src], img.lazy-load, [data-bg], .lazy-bg');

    const loadElement = (element) => {
        if (element.tagName === 'IMG') {
            loadImage(element);
        } else {
            loadBackgroundImage(element);
        }
    };

    const checkPosition = () => {
        const windowHeight = window.innerHeight;

        lazyElements.forEach(element => {
            // Skip if already loaded
            if (element.dataset.loaded === 'true') return;

            const rect = element.getBoundingClientRect();

            // Check if element is in viewport (with some buffer)
            if (
                rect.top <= windowHeight * 1.2 &&
                rect.bottom >= -windowHeight * 0.2
            ) {
                loadElement(element);
                element.dataset.loaded = 'true';
            }
        });
    };

    // Initial check
    checkPosition();

    // Check on scroll and resize with throttling
    let ticking = false;
    const onScroll = () => {
        if (!ticking) {
            window.requestAnimationFrame(() => {
                checkPosition();
                ticking = false;
            });
            ticking = true;
        }
    };

    window.addEventListener('scroll', onScroll);
    window.addEventListener('resize', onScroll);

    // Also check on load
    window.addEventListener('load', () => setTimeout(checkPosition, 200));
}

// Export functions for potential manual usage
window.lazyLoad = {
    init: initImageLazyLoading,
    loadImage: loadImage,
    loadBackgroundImage: loadBackgroundImage
};