<?php

/**
 * Dynamic content loader for the ACF flexible content field "page_section".
 *
 * A layout name maps 1:1 to a folder:
 *   template-parts/dynamic-content/{layout}/index.php    markup    (required)
 *   template-parts/dynamic-content/{layout}/_style.scss  styling   (auto-compiled)
 *   template-parts/dynamic-content/{layout}/component.js  behaviour (auto-compiled)
 *
 * Register the layout in ACF, create the folder, rebuild. Nothing else to wire.
 *
 * Flexible content and repeater fields are ACF PRO only, so every function here
 * that touches ACF is guarded -- with the free plugin, or no plugin at all, the
 * theme renders without dynamic content instead of failing.
 */

/**
 * Render every row of a flexible content field in author order.
 *
 * $depth guards the one layout that renders another post's sections. A Global
 * Module has the same field group as a page, so it can reference another module
 * -- or itself -- and recurse forever. Skipping global_module rows at depth 1
 * makes that structurally impossible rather than merely bounded, and matches the
 * decision that nesting reusable blocks is not wanted.
 */
function drg_render_page_sections( $field_name = 'page_sections', $post_id = null, $depth = 0 ) {
    if ( ! function_exists( 'have_rows' ) || ! function_exists( 'get_row_layout' ) ) {
        return;
    }

    if ( ! have_rows( $field_name, $post_id ) ) {
        return;
    }

    while ( have_rows( $field_name, $post_id ) ) {
        the_row();

        $layout = get_row_layout();
        if ( ! $layout ) {
            continue;
        }

        if ( 'global_module' === $layout && $depth > 0 ) {
            continue;
        }

        // locate_template lets a child theme override a single module
        $template = 'template-parts/dynamic-content/' . $layout . '/index';
        if ( locate_template( $template . '.php' ) ) {
            get_template_part( $template );
        }
    }
}

/**
 * Layout names used by a page, including any reached through a Global Module.
 *
 * Reads the field as a plain array rather than walking the_row(), so it is safe
 * to call during wp_enqueue_scripts -- long before the template runs.
 *
 * Following the reference is not optional. The enqueue is driven by layout name,
 * so a page whose only row is a global_module would report just that name and the
 * CSS the referenced post actually needs would never load -- the section would
 * render unstyled. global_module itself is never added, since it has no assets of
 * its own.
 */
function drg_get_page_section_layouts( $post_id = null, $field_name = 'page_sections', $depth = 0 ) {
    if ( ! function_exists( 'get_field' ) ) {
        return array();
    }

    $rows = get_field( $field_name, $post_id );
    if ( ! is_array( $rows ) ) {
        return array();
    }

    $layouts = array();

    foreach ( $rows as $row ) {
        if ( ! is_array( $row ) || empty( $row['acf_fc_layout'] ) ) {
            continue;
        }

        $layout = $row['acf_fc_layout'];

        if ( 'global_module' === $layout ) {
            // matches the renderer: one level only, so this cannot cycle
            if ( $depth > 0 ) {
                continue;
            }

            $referenced = isset( $row['selected_global_module'] ) ? $row['selected_global_module'] : null;

            if ( $referenced instanceof WP_Post && 'publish' === $referenced->post_status ) {
                $layouts = array_merge(
                    $layouts,
                    drg_get_page_section_layouts( $referenced->ID, $field_name, $depth + 1 )
                );
            }

            continue;
        }

        $layouts[] = $layout;
    }

    return array_unique( $layouts );
}

/**
 * Resolve a "page or url" field pair into a single href.
 *
 * The target is normalised (lowercased, and anything before a colon dropped) so
 * a mistyped ACF choice list such as "Page:page" still resolves correctly.
 */
function drg_resolve_link( $target, $page_object, $url ) {
    $target = is_string( $target ) ? strtolower( $target ) : '';

    if ( false !== strpos( $target, ':' ) ) {
        $target = substr( $target, strpos( $target, ':' ) + 1 );
    }

    if ( 'url' === $target ) {
        return is_string( $url ) ? $url : '';
    }

    if ( $page_object instanceof WP_Post ) {
        return get_permalink( $page_object );
    }

    return '';
}

/**
 * Normalise a post_object value to an array.
 *
 * ACF returns a single WP_Post when the field's "multiple" setting is off and an
 * array when it is on. Wrapping here means the template loops either way and
 * keeps working if that setting is flipped later.
 */
function drg_to_post_array( $value ) {
    if ( $value instanceof WP_Post ) {
        return array( $value );
    }

    if ( ! is_array( $value ) ) {
        return array();
    }

    return array_filter(
        $value,
        function ( $item ) {
            return $item instanceof WP_Post;
        }
    );
}

/**
 * The placeholder file for a given orientation, with its intrinsic size.
 *
 * An argument rather than a second function: the file and its dimensions always
 * travel together, so a third ratio later is one more row here instead of one
 * more function at every call site.
 *
 * An unknown orientation falls back to landscape rather than erroring. A frame
 * with the wrong ratio still renders; a fatal in a template takes the page.
 */
function drg_placeholder_image( $orientation = 'landscape' ) {
    $placeholders = array(
        'landscape' => array( 'placeholder-image.png', 486, 324 ),
        'portrait'  => array( 'placeholder-vertical.png', 402, 536 ),
    );

    $placeholder = isset( $placeholders[ $orientation ] ) ? $placeholders[ $orientation ] : $placeholders['landscape'];

    return array(
        'url'    => get_theme_file_uri( 'assets/images/' . $placeholder[0] ),
        'width'  => $placeholder[1],
        'height' => $placeholder[2],
    );
}

/**
 * URL of the landscape placeholder.
 *
 * Kept as a thin wrapper because CLAUDE.md names it as the single place the path
 * lives. Reach for drg_placeholder_image() in new code -- it carries the
 * dimensions too, which a bare URL cannot.
 */
function drg_placeholder_image_url() {
    $placeholder = drg_placeholder_image();

    return $placeholder['url'];
}

/**
 * Echo the placeholder as a plain <img>.
 *
 * Not an attachment, so it cannot go through wp_get_attachment_image() and gets
 * no srcset. width/height are therefore explicit -- they let the browser reserve
 * the box before the file loads, the same guarantee imageRatio() exists for.
 * The mixin absolutely-positions .ratio-item at 100%/100%, so the attributes only
 * ever supply the intrinsic ratio, never the rendered size.
 *
 * alt is empty and PRESENT. The placeholder depicts nothing about the content it
 * stands in for, so describing it would mislead a screen reader -- and a missing
 * alt attribute would let one announce the filename instead.
 */
function drg_the_placeholder_image( $class = '', $orientation = 'landscape' ) {
    $placeholder = drg_placeholder_image( $orientation );

    printf(
        '<img class="%1$s" src="%2$s" width="%3$d" height="%4$d" alt="" loading="lazy" decoding="async">',
        esc_attr( $class ),
        esc_url( $placeholder['url'] ),
        (int) $placeholder['width'],
        (int) $placeholder['height']
    );
}

/**
 * Echo an ACF image array as an <img>, falling back to the placeholder.
 * Configure image fields to return an array -- this theme assumes that format.
 *
 * $fallback defaults to true so every caller gains the placeholder for free.
 * Pass false where a grey panel would read worse than nothing -- a full-bleed
 * background behind light text, or one grey box scrolling among real logos.
 *
 * $orientation only picks which placeholder stands in. A real image is emitted at
 * its own ratio either way, so passing it never changes what an editor uploaded.
 */
function drg_the_acf_image( $image, $class = '', $size = 'large', $fallback = true, $orientation = 'landscape' ) {
    if ( ! is_array( $image ) || empty( $image['ID'] ) ) {
        if ( $fallback ) {
            drg_the_placeholder_image( $class, $orientation );
        }

        return;
    }

    echo wp_get_attachment_image(
        $image['ID'],
        $size,
        false,
        array(
            'class' => $class,
            'alt'   => isset( $image['alt'] ) ? $image['alt'] : '',
        )
    );
}

/**
 * Echo a post's featured image, falling back to the placeholder.
 *
 * The WP_Post sibling of drg_the_acf_image(). Kept separate rather than folded
 * into one entry point because the two take different inputs and reach different
 * WordPress functions -- a single function would open by branching on its own
 * argument type, which is harder to read than two named ones.
 */
function drg_the_post_thumbnail( $post, $class = '', $size = 'large' ) {
    if ( $post instanceof WP_Post && has_post_thumbnail( $post ) ) {
        echo get_the_post_thumbnail( $post, $size, array( 'class' => $class ) );

        return;
    }

    drg_the_placeholder_image( $class );
}

/**
 * Echo a textarea value with line breaks preserved.
 * Configure textarea fields with no automatic formatting -- multi-line copy would
 * otherwise collapse into a single run of text.
 */
function drg_the_multiline( $text ) {
    if ( ! is_string( $text ) || '' === $text ) {
        return;
    }

    echo nl2br( esc_html( $text ) );
}

/**
 * Extract the 11-character video id from any YouTube URL an editor might paste.
 *
 * Editors paste the address bar, not embed markup -- finding the embed code means
 * digging through YouTube's share dialog, so the field takes a plain URL and the
 * id is derived here.
 *
 * Handles watch?v=, youtu.be/, /embed/, /shorts/ and /live/, with or without
 * extra query parameters. Returns '' for anything unreadable, so a bad paste
 * renders nothing rather than a broken frame.
 */
function drg_youtube_id( $url ) {
    if ( ! is_string( $url ) || '' === trim( $url ) ) {
        return '';
    }

    $parts = wp_parse_url( $url );

    if ( empty( $parts['host'] ) ) {
        return '';
    }

    $candidate = '';

    if ( ! empty( $parts['query'] ) ) {
        $query = array();
        wp_parse_str( $parts['query'], $query );

        if ( ! empty( $query['v'] ) ) {
            $candidate = $query['v'];
        }
    }

    // youtu.be/ID, /embed/ID, /shorts/ID and /live/ID all carry it as the last segment
    if ( '' === $candidate && ! empty( $parts['path'] ) ) {
        $segments = array_values( array_filter( explode( '/', $parts['path'] ) ) );

        if ( ! empty( $segments ) ) {
            $candidate = end( $segments );
        }
    }

    return preg_match( '/^[A-Za-z0-9_-]{11}$/', (string) $candidate ) ? (string) $candidate : '';
}

/**
 * Privacy-enhanced embed URL for a pasted YouTube link, or '' when unreadable.
 *
 * youtube-nocookie.com sets no tracking cookie until the visitor presses play.
 */
function drg_youtube_embed_url( $url ) {
    $video_id = drg_youtube_id( $url );

    if ( '' === $video_id ) {
        return '';
    }

    return 'https://www.youtube-nocookie.com/embed/' . $video_id . '?rel=0';
}

/**
 * A two-level menu as [ parent_id => [ 'item' => WP_Post, 'children' => [...] ] ].
 *
 * Goes through wp_get_nav_menu_items() rather than querying nav_menu_item posts,
 * because most menu items store an EMPTY post_title and inherit their label from
 * the page they link to. That fallback happens in wp_setup_nav_menu_item(), not
 * in the database, so a raw query returns blank labels.
 *
 * Returns an empty array for an unregistered location, an unassigned one, or an
 * empty menu -- so a caller can foreach it without guarding first.
 *
 * Only two levels. Grandchildren are dropped rather than silently flattened into
 * the wrong column; a footer or mega panel has nowhere to put a third.
 */
function drg_get_menu_tree( $location ) {
    $locations = get_nav_menu_locations();

    if ( empty( $locations[ $location ] ) ) {
        return array();
    }

    $items = wp_get_nav_menu_items( $locations[ $location ] );

    if ( ! is_array( $items ) ) {
        return array();
    }

    $tree = array();

    foreach ( $items as $item ) {
        if ( 0 === (int) $item->menu_item_parent ) {
            $tree[ $item->ID ] = array(
                'item'     => $item,
                'children' => array(),
            );
        }
    }

    // second pass, so a child listed before its parent still lands correctly
    foreach ( $items as $item ) {
        $parent = (int) $item->menu_item_parent;

        if ( $parent && isset( $tree[ $parent ] ) ) {
            $tree[ $parent ]['children'][] = $item;
        }
    }

    return $tree;
}

/**
 * Digits-only form of a phone number, for a tel: or wa.me href.
 *
 * The visible label keeps whatever formatting the client entered; only the href
 * is normalised. A leading + is preserved because it carries the country code.
 */
function drg_phone_href( $number ) {
    if ( ! is_string( $number ) || '' === trim( $number ) ) {
        return '';
    }

    $plus   = 0 === strpos( trim( $number ), '+' ) ? '+' : '';
    $digits = preg_replace( '/\D/', '', $number );

    return '' === $digits ? '' : $plus . $digits;
}

function drg_format_compact_number( $value ) {
    $number = (float) preg_replace( '/[^0-9.]/', '', (string) $value );

    $trim = function ( $n ) {
        return rtrim( rtrim( number_format( $n, 1, '.', '' ), '0' ), '.' );
    };

    if ( $number >= 1000000 ) {
        return $trim( $number / 1000000 ) . 'M';
    }

    if ( $number >= 1000 ) {
        return $trim( $number / 1000 ) . 'k';
    }

    return $trim( $number );
}
