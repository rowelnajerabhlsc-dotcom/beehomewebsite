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
 * - Offsets every snap point by the navbar's rendered height, since the
 *   sitewide navbar (`nav`, position: sticky; top: 0) sits on top of the
 *   scrollable content and would otherwise cover whatever a section snaps
 *   to right at its top edge.
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

    // The element Lenis is actually driving. `.scroll-container` for pages
    // like home/transport, otherwise Lenis scrolls the window/documentElement.
    var scrollWrapperEl = document.querySelector(".scroll-container") || document.documentElement;
    var navbarEl = document.querySelector("nav");

    // addElement()/addElements() snap to an element's exact top edge with no
    // way to offset it, so we compute each target's scroll position
    // ourselves and subtract the navbar's height, landing sections just
    // below the navbar instead of underneath it.
    var removeSnapPoints = [];

    function getOffsetWithinScroll(el) {
        if (scrollWrapperEl === document.documentElement) {
            // Window-scrolling page: getBoundingClientRect() is already
            // viewport-relative, so adding the current scroll position gives
            // the element's absolute offset from the top of the document.
            return el.getBoundingClientRect().top + window.scrollY;
        }
        // Custom scroll container: its own box doesn't move as its content
        // scrolls, so measure the target relative to the container's box,
        // then add the container's current scrollTop.
        var containerRect = scrollWrapperEl.getBoundingClientRect();
        var elRect = el.getBoundingClientRect();
        return (elRect.top - containerRect.top) + scrollWrapperEl.scrollTop;
    }

    function setSnapPoints() {
        // clear any previously registered points before recomputing
        removeSnapPoints.forEach(function (remove) { remove(); });
        removeSnapPoints = [];

        // nav is `position: sticky; top: 0`, so its rendered height is the
        // amount of extra clearance every snap target needs so the navbar
        // doesn't sit on top of it once it re-sticks to the viewport top.
        var navbarHeight = navbarEl ? navbarEl.offsetHeight : 0;

        Array.from(targets).forEach(function (el) {
            var snapValue = getOffsetWithinScroll(el) - navbarHeight;
            removeSnapPoints.push(snap.add(snapValue));
        });
    }

    setSnapPoints();
    window.addEventListener("resize", setSnapPoints);
})();