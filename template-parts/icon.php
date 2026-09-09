<?php
/**
 * Icon template part.
 *
 * get_template_part( 'template-parts/icon', null, array(
 *     'name'  => 'lucide-circle-arrow-right',
 *     'class' => 'drg-hero__icon',
 * ) );
 *
 * `name` is the filename in assets/images/icon/ without the extension. Add new
 * ones with `npm run make:icon <set>:<name>`.
 */

if ( ! function_exists( 'drg_get_icon' ) ) {
    return;
}

$icon_name  = isset( $args['name'] ) ? $args['name'] : '';
$icon_class = isset( $args['class'] ) ? $args['class'] : '';

// already escaped and validated in drg_get_icon(), and the file is ours
echo drg_get_icon( $icon_name, $icon_class );
