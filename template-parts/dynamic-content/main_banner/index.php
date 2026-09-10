<?php
// Module: main_banner -- see docs/components/main-banner.md

$heading_tag   = ( function_exists( 'get_row_index' ) && 1 === get_row_index() ) ? 'h1' : 'h2';
$badge_path_id = 'drg-main-banner-circle-' . ( function_exists( 'get_row_index' ) ? get_row_index() : 1 );

$main_image  = get_sub_field( 'main_image' );
$circled     = trim( (string) get_sub_field( 'circled_text' ) );
$title       = get_sub_field( 'title' );
$description = get_sub_field( 'description' );

$badge_href = '';

if ( get_sub_field( 'circle_text_clickable' ) && function_exists( 'drg_resolve_link' ) ) {
    $badge_href = drg_resolve_link(
        get_sub_field( 'circle_text_target' ),
        get_sub_field( 'page_target' ),
        get_sub_field( 'url_target' )
    );
}

$badge_tag = '' !== $badge_href ? 'a' : 'div';

$buttons = get_sub_field( 'banner_buttons' );
$buttons = is_array( $buttons ) ? $buttons : array();
?>

<section class="drg-main-banner">

    <div class="drg-main-banner__image-container">
        <?php
        if ( function_exists( 'drg_the_acf_image' ) ) {
            drg_the_acf_image( $main_image, 'drg-main-banner__image', 'full' );
        }
        ?>

        <?php if ( '' !== $circled ) : ?>
            <<?php echo esc_attr( $badge_tag ); ?>
                class="drg-main-banner__badge"
                <?php if ( 'a' === $badge_tag ) : ?>
                    href="<?php echo esc_url( $badge_href ); ?>"
                    aria-label="<?php echo esc_attr( $circled ); ?>"
                <?php endif; ?>
            >
                <svg class="drg-main-banner__badge-text" viewBox="0 0 100 100" aria-hidden="true" focusable="false">
                    <defs>
                        <?php // starts at 6 o'clock and sweeps clockwise, so the string opens at the bottom ?>
                        <path
                            id="<?php echo esc_attr( $badge_path_id ); ?>"
                            fill="none"
                            d="M 50,88 a 38,38 0 1,1 0,-76 a 38,38 0 1,1 0,76"
                        />
                    </defs>
                    <text>
                        <textPath href="#<?php echo esc_attr( $badge_path_id ); ?>"><?php echo esc_html( $circled ); ?></textPath>
                    </text>
                </svg>

                <span class="drg-main-banner__badge-icon">
                    <?php
                    get_template_part(
                        'template-parts/icon',
                        null,
                        array( 'name' => 'heroicons-arrow-up-right' )
                    );
                    ?>
                </span>
            </<?php echo esc_attr( $badge_tag ); ?>>
        <?php endif; ?>
    </div>

    <div class="drg-main-banner__inner container">
        <div class="drg-main-banner__content">

            <?php if ( $title ) : ?>
                <<?php echo esc_attr( $heading_tag ); ?> class="drg-main-banner__title">
                    <?php drg_the_multiline( $title ); ?>
                </<?php echo esc_attr( $heading_tag ); ?>>
            <?php endif; ?>

            <?php if ( $description ) : ?>
                <p class="drg-main-banner__description"><?php drg_the_multiline( $description ); ?></p>
            <?php endif; ?>

            <div class="drg-main-banner__buttons">
                <?php
                get_template_part(
                    'template-parts/button',
                    null,
                    array(
                        'label' => $buttons['secondary_button_button_label'] ?? '',
                        'type'  => $buttons['secondary_button_button_target'] ?? '',
                        'page'  => $buttons['secondary_button_page_target'] ?? null,
                        'url'   => $buttons['secondary_button_url_target'] ?? '',
                        'style' => 'secondary',
                        'class' => 'drg-main-banner__button',
                    )
                );

                get_template_part(
                    'template-parts/button',
                    null,
                    array(
                        'label' => $buttons['primary_button_button_label'] ?? '',
                        'type'  => $buttons['primary_button_button_target'] ?? '',
                        'page'  => $buttons['primary_button_page_target'] ?? null,
                        'url'   => $buttons['primary_button_url_target'] ?? '',
                        'class' => 'drg-main-banner__button',
                    )
                );
                ?>
            </div>

            <?php if ( have_rows( 'information_highlight' ) ) : ?>
                <ul class="drg-main-banner__stats">
                    <?php while ( have_rows( 'information_highlight' ) ) : the_row(); ?>
                        <li class="drg-main-banner__stat">
                            <?php if ( $stat_number = get_sub_field( 'number' ) ) : ?>
                                <?php
                                $stat_raw = (float) preg_replace( '/[^0-9.]/', '', (string) $stat_number );
                                ?>
                                <p class="drg-main-banner__stat-number" data-drg-count="<?php echo esc_attr( $stat_raw ); ?>">
                                    <?php echo esc_html( drg_format_compact_number( $stat_number ) ); ?>+
                                </p>
                            <?php endif; ?>

                            <?php if ( $stat_title = get_sub_field( 'title' ) ) : ?>
                                <p class="drg-main-banner__stat-label"><?php echo esc_html( $stat_title ); ?></p>
                            <?php endif; ?>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php endif; ?>

        </div>
    </div>

</section>
