import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import Offcanvas from 'bootstrap/js/dist/offcanvas';

// example importing other Bootstrap Javascript Module
import Modal from 'bootstrap/js/dist/modal';

gsap.registerPlugin(ScrollTrigger);

/**
 * Shared runtime namespace.
 * layout.js is the ONLY file that imports GSAP/Bootstrap, then publishes the
 * single instance on window.DRG. Page bundles (source/js/pages/*.js) read from
 * window.DRG so layout + page animations share ONE GSAP instance.
 * Page files must NOT import gsap again (that creates a second instance).
 */
window.DRG = {
    gsap,
    ScrollTrigger,
    Modal,
    Offcanvas,
    fn: {}, // global helpers — attach reusable functions below
};

/**
 * Global layout logic (runs on every page).
 * Put navbar, offcanvas, global scroll animations, etc. here.
 */

/**
 * Scroll-triggered fade up.
 *
 * Two shapes, both opt-in from markup so no module is hardcoded here:
 *   data-drg-fade        one element, its own trigger
 *   data-drg-fade-group  a container whose DIRECT CHILDREN reveal in batches
 *   data-drg-fade-item   inside a group, reveal THESE instead of the children
 *
 * Never animate a .swiper-slide or a .swiper-wrapper. GSAP would write transform
 * onto elements Swiper is already translating, and the slide's own
 * `transition-property: transform` would smear it. Tagging something INSIDE a
 * slide is fine -- Swiper never touches it -- which is what the item attribute
 * is for.
 *
 * Above-the-fold sections should not be tagged at all -- there is nothing to
 * scroll to, and hiding an LCP element behind opacity: 0 until JS boots trades a
 * Core Web Vital for an animation nobody sees.
 */
const DRG_FADE_RISE_DESKTOP = 40;
const DRG_FADE_RISE_MOBILE = 24;
const DRG_FADE_DURATION = 0.8;
const DRG_FADE_STAGGER = 0.12;
const DRG_FADE_EASE = 'power2.out';

/**
 * Fires a little before the element is fully in view.
 *
 * clamp() is load-bearing, not decoration. An element near the foot of the page
 * can compute a start position BEYOND the maximum scroll, so its onEnter never
 * runs and it stays hidden for good. clamp() pins the computed start inside the
 * scrollable range, which costs nothing anywhere else.
 */
const DRG_FADE_START = 'clamp(top 85%)';

const drgMatches = (query) => window.matchMedia(query).matches;

/**
 * Resolved when the tween fires rather than when the trigger is built, so a
 * resize before the element arrives still gets the right distance -- and so
 * there is no matchMedia context to revert. A reverting context would reset an
 * already-played element to invisible while its once:true trigger was already
 * dead, leaving it stuck there.
 *
 * Reduced motion keeps the fade and drops the slide. Movement is what causes
 * vestibular symptoms; an opacity change barely registers. Returning 0 keeps one
 * code path, and the reveal still happens.
 */
const drgFadeRise = () => {
    if (drgMatches('(prefers-reduced-motion: reduce)')) return 0;

    return drgMatches('(max-width: 767.98px)') ? DRG_FADE_RISE_MOBILE : DRG_FADE_RISE_DESKTOP;
};

const drgFadePlay = (targets, stagger = 0) =>
    gsap.fromTo(
        targets,
        { autoAlpha: 0, y: drgFadeRise() },
        { autoAlpha: 1, y: 0, duration: DRG_FADE_DURATION, ease: DRG_FADE_EASE, stagger }
    );

// set inline so the header failsafe cannot reveal an element mid-wait and leave
// it to snap back to invisible when it finally enters
const drgFadeArm = (targets) => gsap.set(targets, { autoAlpha: 0 });

// a second init pass over the same root must not double-trigger
const drgFadeClaim = (el) => {
    if (el.dataset.drgFadeBound) return false;

    el.dataset.drgFadeBound = '1';

    return true;
};

const drgFadeInit = (root = document) => {
    gsap.utils.toArray('[data-drg-fade]', root).forEach((el) => {
        if (!drgFadeClaim(el)) return;

        drgFadeArm(el);

        ScrollTrigger.create({
            trigger: el,
            start: DRG_FADE_START,
            once: true,
            onEnter: () => drgFadePlay(el),
        });
    });

    /**
     * batch() groups by arrival time, not by DOM structure, so a responsive grid
     * reveals row by row at every breakpoint without anyone computing what a row
     * is. One card per row on mobile collapses the stagger on its own.
     */
    gsap.utils.toArray('[data-drg-fade-group]', root).forEach((group) => {
        if (!drgFadeClaim(group)) return;

        /**
         * The group's DIRECT CHILDREN, unless it contains elements marked
         * data-drg-fade-item -- then those are what reveal.
         *
         * That exists for the one case direct children cannot serve: a Swiper.
         * The slides and the wrapper belong to Swiper -- writing transform onto
         * either fights its translate3d, and `.swiper-slide` also carries
         * `transition-property: transform`, which would smear every frame GSAP
         * writes. A child of the slide is untouched by Swiper.
         *
         * A marker attribute rather than a selector on the group, because the
         * CSS start state has to hide exactly the same elements before JS boots
         * and cannot read an arbitrary selector out of an attribute. See the
         * fade block in source/scss/layout.scss.
         */
        const marked = Array.from(group.querySelectorAll('[data-drg-fade-item]'));
        const items = marked.length ? marked : Array.from(group.children);

        if (!items.length) return;

        drgFadeArm(items);

        ScrollTrigger.batch(items, {
            start: DRG_FADE_START,
            once: true,
            onEnter: (batch) => drgFadePlay(batch, DRG_FADE_STAGGER),
        });
    });
};

// published so a module's component.js can re-arm after injecting markup
window.DRG.fn.fadeInit = drgFadeInit;

/**
 * Header banner dismissal.
 *
 * Only the CLICK lives here. Hiding an already-dismissed banner happens in a
 * synchronous inline script beside the markup in
 * template-parts/header-banner/index.php, because this bundle is enqueued in the
 * footer -- hiding from here would let the bar paint first and flash on every
 * page load.
 *
 * The stored value is a hash of the banner copy, so rewording the message
 * produces a new key and the banner returns for everyone who dismissed the old
 * one.
 */
const DRG_BANNER_STORE = 'drgBannerDismissed';

const drgBannerInit = () => {
    // absent on any page where the banner is disabled or already dismissed
    const banner = document.querySelector('[data-drg-banner-key]');
    if (!banner) return;

    const close = banner.querySelector('[data-drg-banner-close]');
    if (!close) return;

    close.addEventListener('click', () => {
        banner.hidden = true;

        // writing THROWS in some privacy modes -- the banner still closes for
        // this page view, it just will not be remembered
        try {
            window.localStorage.setItem(DRG_BANNER_STORE, banner.dataset.drgBannerKey || '');
        } catch (error) {
            // storage unavailable, nothing to persist
        }
    });
};

// scripts are enqueued in the footer, so the DOM is already parsed -- the guard
// is for the day someone moves them to the head or adds defer
if (document.readyState === 'loading') {
    document.addEventListener(
        'DOMContentLoaded',
        () => {
            drgFadeInit();
            drgBannerInit();
        },
        { once: true }
    );
} else {
    drgFadeInit();
    drgBannerInit();
}

