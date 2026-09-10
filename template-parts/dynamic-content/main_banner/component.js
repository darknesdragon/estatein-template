// Module: main_banner -- see docs/components/main-banner.md

(function () {
    const DRG_COUNT_DURATION = 1.6;
    const DRG_COUNT_START = 'top 80%';
    const DRG_SPIN_DURATION = 20;

    const roots = document.querySelectorAll('.drg-main-banner');
    if (!roots.length) return;

    const { gsap, ScrollTrigger } = window.DRG || {};
    if (!gsap || !ScrollTrigger) return;

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

                badge.addEventListener('focus', pauseSpin);
                badge.addEventListener('blur', resumeSpin);
            }
        }

        const numbers = Array.from(root.querySelectorAll('[data-drg-count]'));
        if (!numbers.length) return;

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
