<!DOCTYPE html>
<html lang="<?php echo esc_attr( drg_get_current_lang() ); ?>">
<head>

    <?php
    /**
     * Reveal gate for the scroll-fade system.
     *
     * MUST stay synchronous and inside <head>. It runs before first paint, so
     * .drg-js lands before anything renders and tagged elements never flash at
     * full opacity. Deferring or async-ing it reintroduces the flash it exists
     * to remove.
     *
     * The class is the ONLY thing that arms the CSS start state, so with JS
     * disabled this never runs, the start state never applies, and content is
     * visible from the first paint. No <noscript> fallback needed.
     *
     * The timeout covers the remaining case -- JS enabled but the bundle 404s or
     * throws. GSAP writes INLINE styles and inline beats a class selector, so
     * once the bundle is alive this is a no-op; it can only ever reveal elements
     * GSAP never reached.
     */
    ?>
    <script>
        (function (root) {
            var REVEAL_FAILSAFE_MS = 3000;

            root.classList.add('drg-js');
            window.setTimeout(function () {
                root.classList.remove('drg-js');
            }, REVEAL_FAILSAFE_MS);
        })(document.documentElement);
    </script>

    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="format-detection" content="telephone=no">
    <meta name="theme-color" content="#703BF7">
    
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
    
    <?php wp_body_open(); ?>
    
    <header class="drg-header">
        <?php get_template_part( 'template-parts/header-banner/index' ); ?>

        <?php
        /**
         * expand-xl, not expand-lg: logo + four items + the right-area button do
         * not fit comfortably between 992 and 1200, and 1200 is already the
         * theme's stacking line everywhere else (see vwStacked in _mixin.scss).
         */
        ?>
        <nav class="navbar navbar-expand-xl drg-navbar">
            <div class="container">
                <?php
                    if(function_exists('the_custom_logo')) {
                        the_custom_logo();
                    }
                ?>
                <?php
                /**
                 * Our icon, not .navbar-toggler-icon.
                 *
                 * Bootstrap paints that span with a hardcoded SVG data URI whose
                 * stroke is baked in as rgba(0,0,0,.55) -- unreadable on the dark
                 * bar, and unreachable from CSS because it is a background image,
                 * not a glyph. An inlined heroicon inherits currentColor instead,
                 * so colour and hover are ordinary CSS.
                 *
                 * bars-3-bottom-right matches the comp: the bottom bar measures
                 * 12px against the other two at 22px, right-aligned.
                 */
                ?>
                <button class="navbar-toggler drg-navbar__toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar" aria-label="Toggle navigation">
                    <?php
                    get_template_part(
                        'template-parts/icon',
                        null,
                        array(
                            'name'  => 'heroicons-bars-3-bottom-right',
                            'class' => 'drg-navbar__toggler-icon',
                        )
                    );
                    ?>
                </button>

                <?php
                /**
                 * aria-label, not aria-labelledby.
                 *
                 * The panel used to point at an <h5> reading "Offcanvas". That
                 * heading is gone -- the logo is still visible in the bar behind
                 * the panel, so repeating it adds nothing -- and a dangling
                 * labelledby reference makes a screen reader announce an unnamed
                 * dialog. A literal label has nothing to dangle.
                 */
                ?>
                <div class="offcanvas offcanvas-end drg-navbar__offcanvas" tabindex="-1" id="offcanvasNavbar" aria-label="Main navigation">
                    <div class="offcanvas-header drg-navbar__offcanvas-header">
                        <?php
                        /**
                         * Same reasoning as the toggler above: Bootstrap's
                         * .btn-close is a background-image SVG with #000 baked
                         * in, so `color` cannot touch it and .btn-close-white
                         * only offers a filter-based invert. Our inlined icon
                         * takes currentColor directly.
                         *
                         * Reuses the x-mark already fetched for the header
                         * banner -- one icon file, two components.
                         */
                        ?>
                        <button type="button" class="drg-navbar__close" data-bs-dismiss="offcanvas" aria-label="Close menu">
                            <?php
                            get_template_part(
                                'template-parts/icon',
                                null,
                                array(
                                    'name'  => 'heroicons-x-mark',
                                    'class' => 'drg-navbar__close-icon',
                                )
                            );
                            ?>
                        </button>
                    </div>
                    <div class="offcanvas-body drg-navbar__offcanvas-body">
                        <ul class="navbar-nav drg-navbar__menu drg-navbar__menu--main">
                        <?php
                            // Header Main Navigation Menu
                            $args = array(
                                'theme_location' => 'main_menu',
                                'depth'          => 1,
                                'container'      => '', // remove div container
                                'items_wrap'      => '%3$s', // remove ul tag
                            );
                            wp_nav_menu( $args );
                        ?>
                        </ul>

                        <?php
                        /**
                         * Right-hand slot -- the Contact Us button in this design.
                         *
                         * Last in the DOM on purpose: above xl it lands at the far
                         * right of the flattened row, and below xl it falls to the
                         * bottom of the offcanvas, which is where it belongs on
                         * mobile. One order serves both.
                         *
                         * An unassigned location renders nothing, so a fresh site
                         * simply has no button rather than an empty <ul>.
                         */
                        ?>
                        <ul class="navbar-nav drg-navbar__menu drg-navbar__menu--right">
                        <?php
                            $right_args = array(
                                'theme_location' => 'main_menu_right',
                                'depth'          => 1,
                                'container'      => '',
                                'items_wrap'     => '%3$s',
                            );
                            wp_nav_menu( $right_args );
                        ?>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>
    </header>
    
    <main>