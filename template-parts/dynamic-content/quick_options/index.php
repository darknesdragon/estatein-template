<?php
/**
 * Module: quick_options
 * Fields: options [repeater]
 *
 * Scaffolded by tools/make-module.js -- edit freely.
 * Runs inside the_row(), so get_sub_field() works directly.
 */
?>

<section class="drg-quick-options">
    <div class="container">
        <?php if ( have_rows( 'options' ) ) : ?>
            <ul class="drg-quick-options__options">
                <?php while ( have_rows( 'options' ) ) : the_row(); ?>
                    <li class="drg-quick-options__options-item">
                        <?php if ( $icon = get_sub_field( 'icon' ) ) : ?>
                            <div class="drg-quick-options__icon">
                                <?php drg_the_acf_image( $icon, 'drg-quick-options__icon-img' ); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ( $text = get_sub_field( 'text' ) ) : ?>
                            <p class="drg-quick-options__text"><?php echo esc_html( $text ); ?></p>
                        <?php endif; ?>

                        <?php
                        $link_href = drg_resolve_link(
                            get_sub_field( 'option_target' ),
                            get_sub_field( 'page_target' ),
                            get_sub_field( 'url_target' )
                        );
                        ?>
                        <?php if ( $link_href ) : ?>
                            <a class="btn btn-primary drg-quick-options__link" href="<?php echo esc_url( $link_href ); ?>">
                                <?php echo esc_html( 'Learn more' ); ?>
                            </a>
                        <?php endif; ?>

                    </li>
                <?php endwhile; ?>
            </ul>
        <?php endif; ?>

    </div>
</section>
