<?php

/**
 * TGMPA function
 * @link https://github.com/TGMPA/TGM-Plugin-Activation
 * @link https://github.com/webstylepress/WordPress-Snippets
 */
require_once get_template_directory() . '/inc/class-tgm-plugin-activation.php';
add_action( 'tgmpa_register', 'drg_theme_required_plugins' );
function drg_theme_required_plugins() {

    /*
     * Array of plugin arrays. Required keys are name and slug.
     * If the source is NOT from the .org repo, then local source is also required.
     */

    // Every plugin below resolves from the wordpress.org repository, except
    // ACF Pro -- see the note on its entry.

    $plugins = array(

        /**
         * Advanced Custom Fields PRO -- installed manually, listed anyway.
         *
         * Not on wordpress.org, and its official update server needs a licence
         * key, so there is no URL TGMPA could install from. external_url keeps
         * it in the required list and links the name to advancedcustomfields.com
         * instead of a dead install link.
         *
         * is_callable is what makes the row disappear once ACF Pro is active:
         * acf_add_options_page() exists only in Pro, and checking a function
         * rather than a plugin slug means a manual install under any folder
         * name still satisfies the requirement.
         *
         * The dynamic content system needs Pro specifically -- flexible content
         * and repeater fields are not in the free plugin.
         */
        array(
            'name'             => 'Advanced Custom Fields PRO',
            'slug'             => 'advanced-custom-fields-pro',
            'required'         => true,
            'external_url'     => 'https://www.advancedcustomfields.com/pro/',
            'is_callable'      => 'acf_add_options_page',
        ),

        // Advanced custom field image ratio crop
        array(
            'name'             => 'Advanced Custom Fields: Image Aspect Ratio Crop Field',
            'slug'             => 'acf-image-aspect-ratio-crop',
            'required'         => true,
        ),
        
        // Classic editor
        array(
            'name'             => 'Classic Editor',
            'slug'             => 'classic-editor',
            'required'         => true,
        ),
        
        // Duplicator
        array(
            'name'             => 'Duplicator – WordPress Migration & Backup Plugin',
            'slug'             => 'duplicator',
            'required'         => true,
        ),
        
        // Ninjafirewall
        array(
            'name'             => 'NinjaFirewall (WP Edition) – Advanced Security Plugin and Firewall',
            'slug'             => 'ninjafirewall',
            'required'         => false,
        ),
        
        // Wordfrence
        array(
            'name'             => 'Wordfence Security – Firewall, Malware Scan, and Login Security',
            'slug'             => 'wordfence',
            'required'         => false,
        ),
        
        // Polylang
        array(
            'name'             => 'Polylang',
            'slug'             => 'polylang',
            'required'         => false,
        ),
        
        // Polylang Slug
        array(
            'name'             => 'Polylang Slug',
            'slug'             => 'polylang-slug',
            'required'         => false,
        ),
        
        // CATPCHA4WP
        array(
            'name'             => 'CAPTCHA 4WP – Antispam CAPTCHA solution for WordPress',
            'slug'             => 'advanced-nocaptcha-recaptcha',
            'required'         => true,
        ),
        
        // Adminimize
        array(
            'name'             => 'Adminimize',
            'slug'             => 'adminimize',
            'required'         => true,
        ),
        
        // Capabilities
        array(
            'name'             => 'PublishPress Capabilities – User Role Editor, Access Permissions, Admin Menus',
            'slug'             => 'capability-manager-enhanced',
            'required'         => true,
        ),
        
        // SVG Support
        array(
            'name'             => 'SVG Support',
            'slug'             => 'svg-support',
            'required'         => true,
        ),

        // Publish Button on Toolbar
        array(
            'name'             => 'Toolbar Publish Button',
            'slug'             => 'toolbar-publish-button',
            'required'         => true,
        ),
        
        // White Label CMS
        // array(
        //     'name'             => 'White Label CMS',
        //     'slug'             => 'white-label-cms',
        //     'required'         => false,
        // ),
        
        // WPS Hide Login
        array(
            'name'             => 'WPS Hide Login',
            'slug'             => 'wps-hide-login',
            'required'         => true,
        ),
        
        // Contact Form 7
        array(
            'name'             => 'Contact Form 7',
            'slug'             => 'contact-form-7',
            'required'         => false,
        ),
        
        // Contact Form 7 Database Addon – CFDB7
        array(
            'name'             => 'Contact Form 7 Database Addon – CFDB7',
            'slug'             => 'contact-form-cfdb7',
            'required'         => false,
        ),
        
        // Flamingo
        array(
            'name'             => 'Flamingo',
            'slug'             => 'flamingo',
            'required'         => false,
        ),
        
        // Heartbeat Control
        array(
            'name'             => 'Heartbeat Control',
            'slug'             => 'heartbeat-control',
            'required'         => true,
        ),
        
    );
    
    $config = array(
        'id'           => 'tgmpa',                 // Unique ID for hashing notices for multiple instances of TGMPA.
        'default_path' => '',                      // Default absolute path to bundled plugins.
        'menu'         => 'tgmpa-install-plugins', // Menu slug.
        'parent_slug'  => 'themes.php',            // Parent menu slug.
        'capability'   => 'edit_theme_options',    // Capability needed to view plugin install page, should be a capability associated with the parent menu used.
        'has_notices'  => true,                    // Show admin notices or not.
        'dismissable'  => true,                    // If false, a user cannot dismiss the nag message.
        'dismiss_msg'  => '',                      // If 'dismissable' is false, this message will be output at top of nag.
        'is_automatic' => true,                    // Automatically activate plugins after installation or not.
        'message'      => '',                      // Message to output right before the plugins table.
        /*
        'strings'      => array(
            'page_title'                      => __( 'Install Required Plugins', 'theme-slug' ),
            'menu_title'                      => __( 'Install Plugins', 'theme-slug' ),
            // <snip>...</snip>
            'nag_type'                        => 'updated', // Determines admin notice type - can only be 'updated', 'update-nag' or 'error'.
            )
            */
    );
    tgmpa( $plugins, $config );
}