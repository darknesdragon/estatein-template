<?php

/**
 * Functions for get wordpress current language
 * You just need to call the function in templates to get the current language
 * it is usefull when you work with polylang plugin
 * Expexted return format "en"
 * Falls back to the site locale when polylang has no active language
 * @return string
 */
function drg_get_current_lang() {
    $fallback = explode( '_', get_locale() ?: 'en_US' )[0] ?: 'en';

    if ( function_exists('pll_current_language') ) {
        return pll_current_language() ?: $fallback;
    }

    return $fallback;
}

/**
 * Functions to get page data object with page title only
 * See page title in page option general settings -> page link
 * @param string default empty
 * @return WP_Post|null null when ACF is missing or no row matches the title
 */
function drg_get_page_data($page_title) {
    $pageData = function_exists('get_field') ? get_field( 'page_links', 'page-links' ) : null;

    if ( ! is_array($pageData) ) {
        return null;
    }

    foreach ( $pageData as $data ) {
        if ( ( $data['page_title'] ?? '' ) === $page_title ) {
            return $data['page_object'] ?? null;
        }
    }

    return null;
}

/**
 * Function to add separator to admin menu
 * This function receive 1 parameter which is menu position
 * Parameter can be either integer or string
 * You can either specify the position explicitly, or you can pass the slug or URL of an existing top-level menu and it will automatically figure out its position and add the separator right after that menu
 * @link https://w-shadow.com/blog/2012/10/16/add-separators-to-the-admin-menu/
 */
function drg_add_admin_menu_separator($position, $text = '') {
    global $menu;
    static $uid = 0;

    if ( ! is_array($menu) ) {
        return;
    }

    if ( !is_int($position) ) {
        //Find the position of the menu that matches
        //the specified file name or URL.
        $menuPosition = 0;
        foreach($menu as $menuPosition => $item) {
            if ( ( $item[2] ?? '' ) === $position ) {
                break;
            }
        }
        //We'll insert the separator just after the target menu.
        $position = $menuPosition + 1;
    }

    $menuFile = 'separator-custom-' . $uid++;

    if (!$text) {
        $menu[$position] = array(
            '',                  //Menu title (ignored)
            'read',              //Required capability
            $menuFile,           //URL or file (ignored, but must be unique)
            '',                  //Page title (ignored)
            'wp-menu-separator drg-separator', //CSS class. Identifies this item as a separator.
        );
    } else {
        $menu[$position] = array(
            '',                  //Menu title (ignored)
            'read',              //Required capability
            $menuFile,           //URL or file (ignored, but must be unique)
            '',                  //Page title (ignored)
            'wp-menu-separator drg-separator ' . $text, //CSS class. Identifies this item as a separator.
        );
    }
    ksort($menu);
}
/**
 * Function to show custom debugging helper
 * call the function anywhere in the template to show the debugging helper
 * write down the code that you want to debug on the template-parts/debug-console.php
 */

function drg_show_debug_helper() {
    get_template_part('template-parts/debug-console');
}
