/*!
 * Lenis Snap plugin (shared)
 *
 * Loaded as `<script type="module" src="JS/lenis-snap.js"></script>` from
 * any page that uses section snapping (home, transport).
 *
 * - Imports `Snap` from esm.sh (no UMD build exists).
 * - Wires the plugin to the shared `window.lenis` instance created by
 *   `JS/lenis.js`.
 * - Snaps to any element that has a `data-lenis-snap` attribute, with
 *   `align: 'start'` to match the project's original `scroll-snap-align: start`.
 * - Type is `proximity` to match the project's original
 *   `scroll-snap-type: y proximity`.
 *
 * ESM scope note: this is a module script, so it does not share scope with
 * the classic <script> tag that loads `JS/lenis.js`. The classic script
 * exposes the Lenis instance as `window.lenis`, and we read it from there.
 */

import Snap from "https://esm.sh/lenis/snap";

(function initSnap() {
    if (!window.lenis) {
        // JS/lenis.js didn't run or Lenis didn't load. Bail without throwing.
        console.warn("[lenis-snap] window.lenis not found — skipping snap init");
        return;
    }

    var targets = document.querySelectorAll("[data-lenis-snap]");

    if (!targets.length) {
        var scrollContainer = document.querySelector(".lenis-content");
        if (scrollContainer) {
            targets = scrollContainer.querySelectorAll(":scope > section, :scope > .footer");
        } else {
            scrollContainer = document.querySelector(".scroll-container");
            if (scrollContainer) {
                targets = scrollContainer.querySelectorAll(":scope > *");
            }
        }
    }

    if (!targets.length) {
        // Page doesn't use snap — that's fine, this module is loaded
        // globally but no-ops when the page has no snap targets.
        return;
    }

    var snap = new Snap(window.lenis, {
        type: "proximity",
        duration: 0.6,
    });

    snap.addElements(Array.from(targets), { align: "start" });
})();
