<?php
/**
 * Module: main_banner
 * Fields: main_image [image]
 * Fields: circled_text [text]
 * Fields: title [textarea]
 * Fields: description [textarea]
 * Fields: banner_buttons [group]
 * Fields: information_highlight [repeater]
 *
 * Scaffolded by tools/make-module.js -- edit freely.
 * Runs inside the_row(), so get_sub_field() works directly.
 */
?>

<section class="drg-main-banner">
    <div class="container">
        <?php if ( $main_image = get_sub_field( 'main_image' ) ) : ?>
            <div class="drg-main-banner__main-image">
                <?php drg_the_acf_image( $main_image, 'drg-main-banner__main-image-img' ); ?>
            </div>
        <?php endif; ?>

        <?php if ( $circled_text = get_sub_field( 'circled_text' ) ) : ?>
            <p class="drg-main-banner__circled-text"><?php echo esc_html( $circled_text ); ?></p>
        <?php endif; ?>

        <?php if ( $title = get_sub_field( 'title' ) ) : ?>
            <p class="drg-main-banner__title"><?php drg_the_multiline( $title ); ?></p>
        <?php endif; ?>

        <?php if ( $description = get_sub_field( 'description' ) ) : ?>
            <p class="drg-main-banner__description"><?php drg_the_multiline( $description ); ?></p>
        <?php endif; ?>

        <?php if ( $banner_buttons = get_sub_field( 'banner_buttons' ) ) : ?>
            <p class="drg-main-banner__banner-buttons"><?php echo esc_html( $banner_buttons ); ?></p>
        <?php endif; ?>

        <?php if ( have_rows( 'information_highlight' ) ) : ?>
            <ul class="drg-main-banner__information-highlight">
                <?php while ( have_rows( 'information_highlight' ) ) : the_row(); ?>
                    <li class="drg-main-banner__information-highlight-item">
                        <?php if ( $number = get_sub_field( 'number' ) ) : ?>
                            <p class="drg-main-banner__number"><?php echo esc_html( $number ); ?></p>
                        <?php endif; ?>

                        <?php if ( $title = get_sub_field( 'title' ) ) : ?>
                            <p class="drg-main-banner__title"><?php echo esc_html( $title ); ?></p>
                        <?php endif; ?>

                    </li>
                <?php endwhile; ?>
            </ul>
        <?php endif; ?>

    </div>
</section>
