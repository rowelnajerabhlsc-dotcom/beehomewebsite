/*!
 * Scroll-triggered reveal animations (shared)
 *
 * Usage in markup:
 *   <div data-animate="slide-left">...</div>
 *   <div data-animate="slide-right">...</div>
 *   <div data-animate="fade-in">...</div>
 *
 * Pairs with CSS/scroll-animate.css. Each element starts hidden
 * (opacity: 0) and gets `.in-view` added the first time it scrolls into
 * view, which triggers the matching @keyframes animation.
 *
 * Uses `.scroll-container` as the IntersectionObserver root when present
 * (home.php, transport.php), otherwise falls back to the window/viewport —
 * same scroll-model detection as JS/lenis.js.
 */
(function () {
    "use strict";

    var targets = document.querySelectorAll('[data-animate]');
    if (!targets.length) return;

    var root = document.querySelector('.scroll-container') || null;

    var observer = new IntersectionObserver(
        function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('in-view');
                    // Fires once per element. Remove this line (and switch
                    // to toggling the class on/off instead) if you want the
                    // animation to replay every time it re-enters view.
                    observer.unobserve(entry.target);
                }
            });
        },
        {
            root: root,
            threshold: 0.15,
            rootMargin: '0px 0px -10% 0px', // trigger slightly before it's fully in view
        }
    );

    targets.forEach(function (el) {
        observer.observe(el);
    });
})();
