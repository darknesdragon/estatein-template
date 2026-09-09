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
    <meta name="theme-color" content="#10AF13">
    
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
    
    <?php wp_body_open(); ?>
    
    <header>
        <nav class="navbar navbar-expand-lg">
            <div class="container">
                <?php
                    if(function_exists('the_custom_logo')) {
                        the_custom_logo();
                    }
                ?>
                <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
                    <div class="offcanvas-header">
                        <h5 class="offcanvas-title" id="offcanvasNavbarLabel">Offcanvas</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                    </div>
                    <div class="offcanvas-body">
                        <ul class="navbar-nav">
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
                    </div>
                </div>
            </div>
        </nav>
    </header>
    
    <main>

    <?php drg_show_debug_helper(); ?>