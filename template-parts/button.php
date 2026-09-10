<?php
/**
 * Section button.
 *
 * get_template_part( 'template-parts/button', null, array(
 *     'label'         => get_sub_field( 'button_label' ),
 *     'type'          => get_sub_field( 'button_target_type' ),
 *     'page'          => get_sub_field( 'page_target' ),
 *     'url'           => get_sub_field( 'url_target' ),
 *     'class'         => 'drg-about__button',
 *     'variant'       => 'link',                        // optional, omit for the default pill
 *     'style'         => 'secondary',                   // optional, omit for primary
 *     'icon'          => 'lucide-circle-arrow-right',   // optional, omit for no icon
 *     'icon_position' => 'start',                       // optional, defaults to end
 *     'fade'          => true,                          // optional, see below
 * ) );
 *
 * Reads a "page or url" field trio -- a *_target select, a post_object and a url
 * field as siblings. Clone the same ACF group wherever a section needs a button
 * and every caller reads identically.
 *
 * Renders nothing without a resolvable target. drg_resolve_link() already
 * enforces that the stored value matches the selected type, which matters
 * because ACF keeps the value of a field its conditional logic has hidden.
 */

if ( ! function_exists( 'drg_resolve_link' ) ) {
    return;
}

$button_href = drg_resolve_link(
    isset( $args['type'] ) ? $args['type'] : '',
    isset( $args['page'] ) ? $args['page'] : null,
    isset( $args['url'] ) ? $args['url'] : ''
);

if ( '' === $button_href ) {
    return;
}

/**
 * Labels are plain strings, not gettext calls.
 *
 * String translation in this stack goes through Polylang, not .po files -- see
 * pll_register_string() in inc/functions-polylang.php. Mixing the two would mean
 * two places to look for the same string.
 */
$button_label = isset( $args['label'] ) ? trim( (string) $args['label'] ) : '';

if ( '' === $button_label ) {
    $button_label = 'View More';
}

$button_class = isset( $args['class'] ) ? $args['class'] : '';

/**
 * 'link' drops the pill entirely -- a bare label, so it takes neither Bootstrap's
 * .btn nor our .btn overrides, which would only have to be unset again.
 */
$is_link_variant = isset( $args['variant'] ) && 'link' === $args['variant'];

/**
 * Colour, kept separate from `variant`.
 *
 * `variant` picks the SHAPE -- a filled pill or a bare text link. `style` picks
 * the palette within that shape. They never combine, because a link variant has
 * no fill to colour, which is why this only affects the pill branch.
 */
$button_style = ( isset( $args['style'] ) && 'secondary' === $args['style'] ) ? 'btn-secondary' : 'btn-primary';

$button_modifier = ( isset( $args['style'] ) && 'light' === $args['style'] ) ? ' drg-button--light' : '';

$button_base = $is_link_variant
    ? 'drg-button drg-button--link' . $button_modifier
    : 'btn ' . $button_style . ' drg-button' . $button_modifier;

/**
 * Optional icon, placed before or after the label.
 *
 * No 'icon' means no icon at all -- which is what the starter ships with, since
 * assets/images/icon/ is empty until a project runs `npm run make:icon`. The
 * position is ignored without one.
 *
 * Both sides get a --start / --end modifier so SCSS can order and space them
 * without reading the markup.
 */
$button_icon     = isset( $args['icon'] ) ? trim( (string) $args['icon'] ) : '';
$button_icon_end = ! isset( $args['icon_position'] ) || 'start' !== $args['icon_position'];

$button_icon_args = array(
    'name'  => $button_icon,
    'class' => 'drg-button__icon drg-button__icon--' . ( $button_icon_end ? 'end' : 'start' ),
);

/**
 * Opt-in scroll fade.
 *
 * A caller cannot put data-drg-fade on the anchor itself, since this part owns the
 * markup. Opt-in rather than always-on: a button inside a container that already
 * carries data-drg-fade-group would otherwise be armed twice.
 */
$button_fade = ( isset( $args['fade'] ) && $args['fade'] ) ? ' data-drg-fade' : '';
?>

<a class="<?php echo esc_attr( trim( $button_base . ' ' . $button_class ) ); ?>" href="<?php echo esc_url( $button_href ); ?>"<?php echo $button_fade; ?>>
    <?php if ( '' !== $button_icon && ! $button_icon_end ) : ?>
        <?php get_template_part( 'template-parts/icon', null, $button_icon_args ); ?>
    <?php endif; ?>

    <span><?php echo esc_html( $button_label ); ?></span>

    <?php if ( '' !== $button_icon && $button_icon_end ) : ?>
        <?php get_template_part( 'template-parts/icon', null, $button_icon_args ); ?>
    <?php endif; ?>
</a>
