(function () {
    if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
        document.body.classList.remove("loading");
        var loader = document.getElementById("page-loader");
        if (loader) {
            loader.remove();
        }
        return;
    }

    var loader = document.getElementById("page-loader");
    var wordmark = document.querySelector(".loader-wordmark");

    if (!loader || !wordmark || typeof anime === "undefined") {
        document.body.classList.remove("loading");
        if (loader) {
            loader.remove();
        }
        return;
    }

    function buildFragments() {
        var columns = window.innerWidth < 700 ? 32 : 40;
        var rows = Math.ceil(window.innerHeight / (window.innerWidth / columns)) + 2;
        var layer = document.createElement("div");
        var fragmentCount = columns * rows;

        layer.className = "loader-fragments";
        layer.style.setProperty("--fragment-columns", columns);
        layer.style.setProperty("--fragment-rows", rows);
        layer.setAttribute("aria-hidden", "true");

        for (var i = 0; i < fragmentCount; i++) {
            var fragment = document.createElement("span");
            fragment.className = "loader-fragment";
            layer.appendChild(fragment);
        }

        loader.insertBefore(layer, loader.firstChild);

        return {
            columns: columns,
            rows: rows,
            layer: layer,
            pieces: layer.querySelectorAll(".loader-fragment")
        };
    }

    var fragments = buildFragments();
    var GAP = 16;
    var EMBLEM_LIFT = 14;
    var keys = ["c", "emblem", "o", "p"];
    var selectors = [".loader-c", ".loader-emblem", ".loader-o", ".loader-p"];
    var elements = selectors.map(function (selector) {
        return document.querySelector(selector);
    });

    wordmark.classList.add("loader-wordmark--measure");

    var widths = elements.map(function (el) {
        return el.offsetWidth;
    });
    var heights = elements.map(function (el) {
        return el.offsetHeight;
    });
    var maxHeight = Math.max.apply(null, heights);
    var totalWidth = widths.reduce(function (sum, width) {
        return sum + width;
    }, 0) + GAP * (elements.length - 1);

    wordmark.classList.remove("loader-wordmark--measure");
    wordmark.classList.add("loader-wordmark--ready");
    wordmark.style.width = totalWidth + "px";
    wordmark.style.height = maxHeight + "px";

    var finalLeft = {};
    var x = 0;

    keys.forEach(function (key, index) {
        finalLeft[key] = x + widths[index] / 2;
        x += widths[index] + (index < keys.length - 1 ? GAP : 0);
    });

    var originX = totalWidth / 2;
    var baselineY = maxHeight;

    elements.forEach(function (el, index) {
        var key = keys[index];
        var height = heights[index];
        var top = baselineY - height / 2;

        el.style.left = originX + "px";
        el.style.top = top + "px";

        if (key === "emblem") {
            el.style.transform = "translate(-50%, calc(-50% - " + EMBLEM_LIFT + "px))";
        } else {
            el.style.transform = "translate(-50%, -50%)";
        }
    });

    document.querySelector(".loader-emblem").style.opacity = "0";

    var timeline = anime.timeline({
        autoplay: true,
        complete: function () {
            var backdrop = document.getElementById("loader-backdrop");

            var exitTimeline = anime.timeline({
                begin: function () {
                    // Fragments are still fully opaque/covering everything at
                    // this exact instant, so swapping the backdrop out here
                    // is invisible — but from this point on, the fragments'
                    // own movement (not a static white layer) is what's
                    // hiding the page, so their flight actually reveals it.
                    if (backdrop) {
                        backdrop.style.display = "none";
                    }
                },
                complete: function () {
                    loader.remove();
                    document.body.classList.remove("loading");
                }
            });

            exitTimeline
                .add({
                    targets: wordmark,
                    opacity: [1, 0],
                    translateY: [0, -54],
                    scale: [1, 0.94],
                    duration: 700,
                    easing: "easeInCubic"
                })
                .add({
                    targets: fragments.pieces,
                    scale: [
                        { value: 0.88, duration: 150, easing: "easeOutQuad" },
                        { value: 0.66, duration: 650, easing: "easeInOutCubic" }
                    ],
                    translateY: function () {
                        return -(window.innerHeight + window.innerHeight * 0.25 + anime.random(80, 210));
                    },
                    translateX: function () {
                        return anime.random(-18, 18);
                    },
                    rotate: function () {
                        return anime.random(-16, 16);
                    },
                    delay: anime.stagger(10, {
                        grid: [fragments.columns, fragments.rows],
                        from: "center"
                    }),
                    duration: 1030,
                    easing: "easeInOutCubic"
                }, 0)
                .add({
                    targets: loader,
                    opacity: [1, 0],
                    duration: 120,
                    easing: "linear",
                    begin: function () {
                        loader.style.pointerEvents = "none";
                    }
                }, "-=120");
        }
    });

    timeline
        .add({
            targets: ".loader-emblem",
            opacity: [0, 1],
            scale: [0.88, 1],
            duration: 850,
            easing: "easeOutQuad"
        })
        .add({
            targets: ".loader-emblem",
            left: [originX, finalLeft.emblem],
            duration: 650,
            easing: "easeInOutCubic"
        }, "-=250")
        .add({
            targets: ".loader-c",
            opacity: [0, 1],
            left: [originX, finalLeft.c],
            duration: 750,
            easing: "easeOutCubic"
        }, "-=350")
        .add({
            targets: ".loader-o",
            opacity: [0, 1],
            left: [originX, finalLeft.o],
            duration: 700,
            easing: "easeOutCubic"
        }, "-=500")
        .add({
            targets: ".loader-p",
            opacity: [0, 1],
            left: [originX, finalLeft.p],
            duration: 700,
            easing: "easeOutCubic"
        }, "-=560")
        .add({
            duration: 1600
        });
})();
