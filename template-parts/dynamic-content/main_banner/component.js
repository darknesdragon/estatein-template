// Module: main_banner -- see docs/components/main-banner.md

(function () {
    const DRG_COUNT_DURATION = 1.6;
    const DRG_COUNT_START = 'top 80%';
    const DRG_SPIN_DURATION = 20;

    const roots = document.querySelectorAll('.drg-main-banner');
    if (!roots.length) return;

    const { gsap, ScrollTrigger } = window.DRG || {};
    if (!gsap || !ScrollTrigger) return;

    // mirrors drg_format_compact_number() in inc/functions-dynamic-content.php
    const compact = (value) => {
        const trim = (n) => String(Number(n.toFixed(1)));

        if (value >= 1000000) return `${trim(value / 1000000)}M`;
        if (value >= 1000) return `${trim(value / 1000)}k`;

        return String(Math.round(value));
    };

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    roots.forEach((root) => {
        if (root.dataset.drgBannerBound) return;
        root.dataset.drgBannerBound = '1';

        /**
         * Counter-clockwise, because the textPath is set clockwise from 6
         * o'clock: later glyphs sit to the RIGHT at the top of the ring, so
         * turning left pulls the string through the reading position in order.
         *
         * Set up above the counter's early returns -- a banner with no stats
         * still has a ring to turn.
         */
        const badge = root.querySelector('.drg-main-banner__badge');
        const badgeText = root.querySelector('.drg-main-banner__badge-text');

        if (badgeText && !reduceMotion) {
            const spin = gsap.to(badgeText, {
                rotation: -360,
                duration: DRG_SPIN_DURATION,
                ease: 'none',
                repeat: -1,
                transformOrigin: '50% 50%',
            });

            if (badge) {
                const pauseSpin = () => spin.pause();
                const resumeSpin = () => spin.resume();

                badge.addEventListener('mouseenter', pauseSpin);
                badge.addEventListener('mouseleave', resumeSpin);

                // only ever fire on the linked variant; a div badge takes no focus
                badge.addEventListener('focus', pauseSpin);
                badge.addEventListener('blur', resumeSpin);
            }
        }

        const numbers = Array.from(root.querySelectorAll('[data-drg-count]'));
        if (!numbers.length) return;

        // the finished value is already rendered server-side, so leaving it
        // untouched is the correct reduced-motion outcome
        if (reduceMotion) return;

        numbers.forEach((el) => {
            const target = parseFloat(el.dataset.drgCount);
            if (!Number.isFinite(target)) return;

            const suffix = el.textContent.trim().endsWith('+') ? '+' : '';
            const counter = { value: 0 };

            const play = () =>
                gsap.fromTo(
                    counter,
                    { value: 0 },
                    {
                        value: target,
                        duration: DRG_COUNT_DURATION,
                        ease: 'power2.out',
                        onUpdate: () => {
                            el.textContent = compact(counter.value) + suffix;
                        },
                        onComplete: () => {
                            el.textContent = compact(target) + suffix;
                        },
                    }
                );

            /**
             * ScrollTrigger's onEnter fires on a scroll TRANSITION, so an
             * element already in view at load never triggers -- and this banner
             * is always above the fold. isInViewport() covers that case; the
             * trigger covers everything below it.
             *
             * The count zeroes inside play(), never up front, so a path that
             * never runs leaves the server-rendered value instead of a stuck 0.
             */
            if (ScrollTrigger.isInViewport(el)) {
                play();
                return;
            }

            ScrollTrigger.create({
                trigger: el,
                start: DRG_COUNT_START,
                once: true,
                onEnter: play,
            });
        });
    });
})();
