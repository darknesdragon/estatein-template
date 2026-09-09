<?php
/**
 * Header banner.
 *
 * get_template_part( 'template-parts/header-banner/index' );
 *
 * Dismissible promo bar rendered at the top of <header>, above the nav. Content
 * comes from the ACF options page "Customize Settings" (post_id
 * customize_settings), so it is one set of values for the whole site rather than
 * per page.
 *
 * banner_button is a SEAMLESS clone of the Button Settings group with no name
 * prefix, so its sub fields resolve as plain top-level names -- button_label,
 * button_target, page_target, url_target -- and hand straight to
 * template-parts/button.php with no adapter.
 *
 * Styling lives in source/scss/components/_header-banner.scss and compiles into
 * layout.css. This is deliberately NOT a dynamic-content module: those are
 * enqueued per page from a flexible-content row, and this appears on every page.
 */

// ACF Pro absent -- render nothing rather than fatal, same guard as the
// dynamic-content helpers
if ( ! function_exists( 'get_field' ) ) {
    return;
}

$banner_option_id = 'customize_settings';

if ( ! get_field( 'enable_header_banner', $banner_option_id ) ) {
    return;
}

$banner_text = get_field( 'banner_text', $banner_option_id );
$banner_text = is_string( $banner_text ) ? trim( $banner_text ) : '';

// the bar exists to carry a message; without one there is nothing to show, and
// an empty strip would still occupy 63px
if ( '' === $banner_text ) {
    return;
}

/**
 * Background pattern, handed to CSS as a custom property rather than an <img>.
 *
 * The field returns an array, so ['url'] is already the full-size URL -- no
 * wp_get_attachment_image_url() round trip. Full size is the only size that
 * exists anyway: functions-theme-setup.php disables intermediate sizes, and no
 * add_image_size() is registered, so there is no srcset to give up by moving
 * this out of an <img>.
 *
 * An empty field leaves the property unset, and the CSS `none` fallback means
 * nothing paints -- which is the behaviour we want for a full-bleed pattern
 * behind light text, where a placeholder would read worse than no image.
 */
$banner_image     = get_field( 'background_image', $banner_option_id );
$banner_image_url = is_array( $banner_image ) ? ( $banner_image['url'] ?? '' ) : '';

/**
 * Dismissal key, derived from the copy itself.
 *
 * Storing a hash of the text rather than a fixed flag means rewording the banner
 * produces a new key, so a fresh message re-appears for everyone who dismissed
 * the previous one. Truncated because it only has to be unique against the
 * single value the visitor has stored.
 */
$banner_key = substr( md5( $banner_text ), 0, 8 );
?>

<?php
/**
 * The section stays full-bleed while .drg-container-fluid caps the CONTENT at
 * 1920 -- so past that width the dark strip still spans the viewport instead of
 * leaving page background down either side.
 *
 * That is why the two classes are on separate elements: .drg-container-fluid
 * carries max-width, and merging them would cap the strip too.
 */
?>
<section
    class="drg-header-banner"
    data-drg-banner-key="<?php echo esc_attr( $banner_key ); ?>"
    <?php if ( '' !== $banner_image_url ) : ?>
        style="--drg-banner-bg: url('<?php echo esc_url( $banner_image_url ); ?>');"
    <?php endif; ?>
>
    <div class="drg-header-banner__container drg-container-fluid">
        <div class="drg-header-banner__inner">
            <p class="drg-header-banner__text"><?php echo esc_html( $banner_text ); ?></p>

            <?php
            get_template_part(
                'template-parts/button',
                null,
                array(
                    'label'   => get_field( 'button_label', $banner_option_id ),
                    'type'    => get_field( 'button_target', $banner_option_id ),
                    'page'    => get_field( 'page_target', $banner_option_id ),
                    'url'     => get_field( 'url_target', $banner_option_id ),
                    'variant' => 'link',
                    'style'   => 'light',
                    'class'   => 'drg-header-banner__link',
                )
            );
            ?>

            <?php
            /**
             * aria-label is required, not decoration: drg_get_icon() injects
             * aria-hidden="true", and the icon is this button's only content --
             * without a label it is announced as an unnamed button.
             *
             * type="button" keeps it out of any form it might one day sit inside.
             */
            ?>
            <button
                type="button"
                class="drg-header-banner__close"
                aria-label="Dismiss banner"
                data-drg-banner-close
            >
                <?php
                get_template_part(
                    'template-parts/icon',
                    null,
                    array(
                        'name'  => 'heroicons-x-mark',
                        'class' => 'drg-header-banner__close-icon',
                    )
                );
                ?>
            </button>
        </div>
    </div>
</section>

<?php
/**
 * Pre-paint dismissal gate.
 *
 * MUST stay synchronous and immediately after the markup. layout.js is enqueued
 * in the footer, so hiding the banner from there would let it paint first and
 * flash on every page load for someone who already dismissed it -- the same
 * problem, and the same solution, as the .drg-js gate in header.php.
 *
 * Wrapped in try/catch because reading localStorage THROWS outright in some
 * privacy modes rather than returning null. An exception here would be thrown
 * during parsing and stop the rest of the inline script; failing open just means
 * the banner shows.
 */
?>
<script>
    (function () {
        var banner = document.currentScript && document.currentScript.previousElementSibling;

        if (!banner || !banner.classList || !banner.classList.contains('drg-header-banner')) {
            banner = document.querySelector('[data-drg-banner-key]');
        }

        if (!banner) return;

        try {
            if (window.localStorage.getItem('drgBannerDismissed') === banner.dataset.drgBannerKey) {
                banner.hidden = true;
            }
        } catch (error) {
            // storage unavailable -- leave the banner visible
        }
    })();
</script>
