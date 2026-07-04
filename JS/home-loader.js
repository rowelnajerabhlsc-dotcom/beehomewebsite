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
            anime({
                targets: loader,
                translateY: ["0%", "-100%"],
                duration: 900,
                easing: "easeInOutCubic",
                complete: function () {
                    loader.remove();
                    document.body.classList.remove("loading");
                }
            });
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
