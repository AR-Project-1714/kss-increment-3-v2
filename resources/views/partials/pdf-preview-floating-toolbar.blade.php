<style>
    .pdf-preview-toolbar {
        --toolbar-bg-alpha: 1;
        --toolbar-border-alpha: 1;
        --toolbar-shadow-alpha: .06;
        --button-radius: 8px;
        --button-shadow-y: 0px;
        --button-shadow-blur: 0px;
        --button-shadow-alpha: 0;
        --button-tight-shadow-y: 0px;
        --button-tight-shadow-blur: 0px;
        --button-tight-shadow-alpha: 0;
        background-color: rgba(255, 255, 255, var(--toolbar-bg-alpha)) !important;
        background-image: none !important;
        border-bottom-color: rgba(209, 213, 219, var(--toolbar-border-alpha)) !important;
        box-shadow: 0 1px 6px rgba(0, 0, 0, var(--toolbar-shadow-alpha)) !important;
        will-change: background-color, border-color, box-shadow;
    }

    .pdf-preview-toolbar .btn {
        transform: translate3d(0, 0, 0);
        backface-visibility: hidden;
        will-change: transform, border-radius, box-shadow;
        border-radius: var(--button-radius);
        box-shadow:
            0 var(--button-shadow-y) var(--button-shadow-blur) rgba(15, 23, 42, var(--button-shadow-alpha)),
            0 var(--button-tight-shadow-y) var(--button-tight-shadow-blur) rgba(15, 23, 42, var(--button-tight-shadow-alpha));
        transition:
            transform 320ms cubic-bezier(.16, 1, .3, 1),
            background-color 260ms ease,
            border-color 260ms ease,
            filter 260ms ease;
    }

    .pdf-preview-toolbar.is-scrolled {
        pointer-events: none;
    }

    .pdf-preview-toolbar.is-peek {
        justify-content: flex-end;
    }

    .pdf-preview-toolbar.is-scrolled .btn {
        pointer-events: auto;
    }

    .pdf-preview-toolbar.is-scrolled .btn:hover {
        transform: translate3d(0, -2px, 0);
    }

    .pdf-preview-toolbar.is-scrolled .btn:active {
        transform: translate3d(0, 0, 0) scale(.98);
    }

    .pdf-preview-toolbar .btn:focus-visible {
        outline: 3px solid rgba(37, 99, 235, .28);
        outline-offset: 3px;
    }

    @media (prefers-reduced-motion: reduce) {
        .pdf-preview-toolbar,
        .pdf-preview-toolbar .btn {
            transition-duration: 1ms !important;
        }

        .pdf-preview-toolbar.is-scrolled .btn:hover,
        .pdf-preview-toolbar.is-scrolled .btn:active {
            transform: none;
        }
    }
</style>

<script>
    (function () {
        function initFloatingPdfToolbar() {
            var toolbars = document.querySelectorAll('.pdf-preview-toolbar');
            if (!toolbars.length) return;

            var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            var currentProgress = 0;
            var targetProgress = 0;
            var animationFrame = null;

            function getScrollProgress() {
                var scrollTop = window.scrollY
                    || document.documentElement.scrollTop
                    || document.body.scrollTop
                    || 0;

                return Math.min(1, Math.max(0, scrollTop / 96));
            }

            function smootherStep(progress) {
                return progress * progress * progress
                    * (progress * (progress * 6 - 15) + 10);
            }

            function renderToolbarState(progress) {
                var easedProgress = smootherStep(progress);
                var remaining = 1 - easedProgress;
                toolbars.forEach(function (toolbar) {
                    toolbar.style.setProperty('--toolbar-bg-alpha', remaining.toFixed(3));
                    toolbar.style.setProperty('--toolbar-border-alpha', remaining.toFixed(3));
                    toolbar.style.setProperty('--toolbar-shadow-alpha', (.06 * remaining).toFixed(3));
                    toolbar.style.setProperty('--button-radius', (8 + (18 * easedProgress)).toFixed(2) + 'px');
                    toolbar.style.setProperty('--button-shadow-y', (12 * easedProgress).toFixed(2) + 'px');
                    toolbar.style.setProperty('--button-shadow-blur', (28 * easedProgress).toFixed(2) + 'px');
                    toolbar.style.setProperty('--button-shadow-alpha', (.18 * easedProgress).toFixed(3));
                    toolbar.style.setProperty('--button-tight-shadow-y', (3 * easedProgress).toFixed(2) + 'px');
                    toolbar.style.setProperty('--button-tight-shadow-blur', (8 * easedProgress).toFixed(2) + 'px');
                    toolbar.style.setProperty('--button-tight-shadow-alpha', (.10 * easedProgress).toFixed(3));
                    toolbar.classList.toggle('is-scrolled', progress > .01);
                });
            }

            function animateToolbar() {
                var distance = targetProgress - currentProgress;

                if (Math.abs(distance) < .001) {
                    currentProgress = targetProgress;
                    renderToolbarState(currentProgress);
                    animationFrame = null;
                    return;
                }

                currentProgress += distance * .18;
                renderToolbarState(currentProgress);
                animationFrame = window.requestAnimationFrame(animateToolbar);
            }

            function updateToolbarTarget(instant) {
                targetProgress = getScrollProgress();

                if (instant || reducedMotion) {
                    currentProgress = targetProgress;
                    renderToolbarState(currentProgress);
                    if (animationFrame !== null) {
                        window.cancelAnimationFrame(animationFrame);
                        animationFrame = null;
                    }
                    return;
                }

                if (animationFrame === null) {
                    animationFrame = window.requestAnimationFrame(animateToolbar);
                }
            }

            updateToolbarTarget(true);
            window.addEventListener('scroll', function () {
                updateToolbarTarget(false);
            }, { passive: true });
            window.addEventListener('pageshow', function () {
                updateToolbarTarget(true);
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initFloatingPdfToolbar, { once: true });
        } else {
            initFloatingPdfToolbar();
        }
    })();
</script>
