<?php
// Header banner -- see docs/components/header-banner.md

if ( ! function_exists( 'get_field' ) ) {
    return;
}

$banner_option_id = 'customize_settings';

if ( ! get_field( 'enable_header_banner', $banner_option_id ) ) {
    return;
}

$banner_text = get_field( 'banner_text', $banner_option_id );
$banner_text = is_string( $banner_text ) ? trim( $banner_text ) : '';

if ( '' === $banner_text ) {
    return;
}

$banner_image     = get_field( 'background_image', $banner_option_id );
$banner_image_url = is_array( $banner_image ) ? ( $banner_image['url'] ?? '' ) : '';

$banner_key = substr( md5( $banner_text ), 0, 8 );
?>

<?php
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
        }
    })();
</script>
