/*!
 * Lenis shared init (sitewide)
 *
 * - Auto-detects scroll model:
 *     - If a `.scroll-container` element exists on the page, Lenis is wired
 *       to that wrapper (with a `.lenis-content` direct child). Used by
 *       home.php and transport.php.
 *     - Otherwise, Lenis defaults to window scrolling. Used by every other
 *       page in the site.
 * - Uses `lerp: 0.12` for a responsive feel.
 * - Exposes the instance as `window.lenis` so any per-page module script
 *     (lenis/snap plugin, etc.) can reference it.
 * - Drives the rAF loop here so each page doesn't need to write its own.
 *
 * Loaded as a deferred <script> after the Lenis CDN UMD <script>, so
 * `window.Lenis` is already defined by the time this file runs.
 */
(function () {
    "use strict";

    if (typeof window.Lenis === "undefined") {
        // Lenis CDN script didn't load — bail rather than throw a hard error.
        console.warn("[lenis] Lenis UMD not found on window — skipping init");
        return;
    }

    // Check for reduced motion preference
    var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    var LENIS_OPTS = {
        // Adaptive lerp: base value increases with scroll velocity for more responsive feel
        lerp: function () {
            // Base lerp value
            var baseLerp = 0.1;

            // If reduced motion is preferred, use even smoother (lower) lerp
            if (prefersReducedMotion) {
                return 0.05;
            }

            // Increase responsiveness based on velocity (capped)
            // Note: We'll need to update this dynamically in the raf loop
            var velocityBoost = Math.min(Math.abs(this.velocity ? this.velocity.y : 0) * 0.003, 0.1);
            return Math.min(baseLerp + velocityBoost, 0.2);
        },
        smoothWheel: true,
        touchMultiplier: 1.5,
    };

    var lenis;
    var scrollContainer = document.querySelector(".scroll-container");

    if (scrollContainer) {
        // Custom scroll container (home, transport).
        // Lenis needs `wrapper` = the overflow:auto element and `content` =
        // a direct child wrapping all the scrollable content. We look for an
        // existing `.lenis-content` (added by home.php) and create one if it
        // doesn't exist (transport.php), moving the existing children into it.
        var content = scrollContainer.querySelector(".lenis-content");

        if (!content) {
            content = document.createElement("div");
            content.className = "lenis-content";
            // Move every existing direct child of the scroll container into
            // the new wrapper, in order.
            while (scrollContainer.firstChild) {
                content.appendChild(scrollContainer.firstChild);
            }
            scrollContainer.appendChild(content);
        }

        lenis = new window.Lenis(
            Object.assign({}, LENIS_OPTS, {
                wrapper: scrollContainer,
                content: content,
            })
        );
    } else {
        // Window-scrolling pages (the rest of the site).
        lenis = new window.Lenis(LENIS_OPTS);
    }

    // Lenis puts its state classes on the element passed as `wrapper` (or
    // on document.documentElement by default for window scrolling). Force
    // `scroll-behavior: auto` on the scrolling element so the browser doesn't
    // ALSO try to smooth-scroll on hash links, which would fight Lenis.
    if (scrollContainer) {
        scrollContainer.style.scrollBehavior = "auto";
    } else {
        document.documentElement.style.scrollBehavior = "auto";
    }

    // Expose for any module script that needs the instance.
    window.lenis = lenis;

    // Drive the rAF loop. One loop per page, lives until the page unloads.
    function raf(time) {
        // Update adaptive lerp based on current velocity
        if (lenis && typeof lenis.options.lerp === 'function') {
            // Create a temporary options object to calculate the current lerp value
            var tempOpts = Object.assign({}, LENIS_OPTS);
            tempOpts.lerp = lenis.options.lerp.call(lenis); // Call the lerp function with lenis context
            lenis.options.lerp = tempOpts.lerp;
        }

        lenis.raf(time);
        window.requestAnimationFrame(raf);
    }
    window.requestAnimationFrame(raf);

    // Helper for any page that previously called element.scrollIntoView() with
    // native smooth-scroll — those calls need to go through Lenis instead,
    // otherwise the browser's native smooth-scroll fights Lenis for control
    // of scrollTop. Pages can call `lenisScrollTo(elOrSelector)` from any
    // inline script without needing to import the Lenis module.
    window.lenisScrollTo = function (target, opts) {
        if (!lenis || !target) return;
        if (typeof target === "string") {
            target = document.querySelector(target);
            if (!target) return;
        }
        lenis.scrollTo(
            target,
            Object.assign({ offset: 0, immediate: false }, opts || {})
        );
    };
})();
