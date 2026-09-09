/**
 * Page-specific JavaScript example (page-home.php).
 * Consume the shared runtime from window.DRG — never import gsap here, or you
 * get a second GSAP instance that can't coordinate with layout's.
 * Null-safe: page still loads if layout.js failed to publish window.DRG.
 */
const { gsap, ScrollTrigger, fn } = window.DRG || {};

if (gsap) {
    gsap.from('.hero', { opacity: 0, y: 30, duration: 0.6 });
}
