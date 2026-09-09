<?php

/**
 * Functions for register ACF option page
 * This function only for registering new page option
 * Note that we already have option page called "Theme Settings"
 * @link https://www.advancedcustomfields.com/resources/acf_add_options_page/
 * 
 */
if (function_exists('acf_add_options_page')) {
    
    // Create Option Page Example
    /*
    acf_add_options_page(array(
        'post_id'     => 'options',
        'page_title'  => 'Options',
        'menu_title'  => 'Options',
        'menu_slug'   => 'options',
        'capability'  => 'edit_posts',
        'redirect'    => true,
        'icon_url'    => 'dashicons-admin-home',
        'position'    => '79' // https://developer.wordpress.org/reference/functions/add_menu_page/#menu-structure
        
    ));
    */
    
    // Create Option Subpage Example
    /*
    acf_add_options_sub_page(array(
        'post_id'         => 'banner_image',
        'page_title'      => 'Banner Image',
        'menu_title'      => 'Banner Image',
        'parent_slug'     => 'home',
        'menu_slug'       => 'banner-image',
        'updated_message' => __("Banner Image Updated", 'acf')
    ));
    */
    
}

/**
 * Functions for register Option page "Theme Settings"
 * Note this is our starter template that should be on every project
 */
function drg_register_option_page() {
    acf_add_options_page( array(
        'page_title' => 'Theme Settings',
        'menu_slug' => 'theme-settings',
        'icon_url' => 'dashicons-admin-settings',
        'menu_title' => 'Theme Settings',
        'position' => 58,
        'redirect' => true,
    ) );

	acf_add_options_sub_page( array(
        'page_title' => 'Login',
        'menu_slug' => 'login',
        'post_id' => 'login',
        'parent_slug' => 'theme-settings',
        'menu_title' => 'Login',
        'redirect' => false,
    ) );

	acf_add_options_sub_page( array(
        'page_title' => 'Page Links',
        'menu_slug' => 'page-links',
        'post_id' => 'page-links',
        'parent_slug' => 'theme-settings',
        'menu_title' => 'Page Link',
        'redirect' => false,
    ) );

    // Place for other custom subpage
}
add_action( 'acf/init', 'drg_register_option_page' );

/**
 * Functions for register ACF field inside option page "Theme Settings"
 * it automatically created via php code so you don't have to add id in admin page
 * Note this function fires after ACF is fully initialized.
 */
function drg_settings_acf_option_page() {
    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }
    
    // Register Field Background Image to Option Page Login
    acf_add_local_field_group( array(
        'key' => 'group_64db4a5f8cc18',
        'title' => 'Login Customize',
        'fields' => array(
            array(
                'key' => 'field_64db4a60bfbff',
                'label' => 'Login Background Image',
                'name' => 'login_background_image',
                'aria-label' => '',
                'type' => 'image',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'return_format' => 'array',
                'library' => 'all',
                'min_width' => '',
                'min_height' => '',
                'min_size' => '',
                'max_width' => '',
                'max_height' => '',
                'max_size' => '',
                'mime_types' => '',
                'preview_size' => 'medium',
            ),
            array(
                'key' => 'field_64db4a60bfbfg',
                'label' => 'Login Logo',
                'name' => 'login_logo',
                'aria-label' => '',
                'type' => 'image',
                'instructions' => '<i>default image: site logo</i>',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'return_format' => 'array',
                'library' => 'all',
                'min_width' => '',
                'min_height' => '',
                'min_size' => '',
                'max_width' => '',
                'max_height' => '',
                'max_size' => '',
                'mime_types' => '',
                'preview_size' => 'medium',
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'options_page',
                    'operator' => '==',
                    'value' => 'login',
                ),
            ),
        ),
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'left',
        'instruction_placement' => 'label',
        'hide_on_screen' => '',
        'active' => true,
        'description' => '',
        'show_in_rest' => 0,
    ) );

    // Register Page Link Repeater to Option Page Page Link
    acf_add_local_field_group( array(
        'key' => 'group_64dee3674689b',
        'title' => 'Page Links',
        'fields' => array(
            array(
                'key' => 'field_64dee36767ae0',
                'label' => 'Page Links',
                'name' => 'page_links',
                'aria-label' => '',
                'type' => 'repeater',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'layout' => 'row',
                'pagination' => 0,
                'min' => 0,
                'max' => 0,
                'collapsed' => '',
                'button_label' => 'Add Row',
                'rows_per_page' => 20,
                'sub_fields' => array(
                    array(
                        'key' => 'field_64dee37467ae1',
                        'label' => 'Page Title',
                        'name' => 'page_title',
                        'aria-label' => '',
                        'type' => 'text',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '',
                        'maxlength' => '',
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'parent_repeater' => 'field_64dee36767ae0',
                    ),
                    array(
                        'key' => 'field_64dee38067ae2',
                        'label' => 'Page Object',
                        'name' => 'page_object',
                        'aria-label' => '',
                        'type' => 'post_object',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'post_type' => array(
                            0 => 'page',
                        ),
                        'post_status' => '',
                        'taxonomy' => '',
                        'return_format' => 'object',
                        'multiple' => 0,
                        'allow_null' => 0,
                        'bidirectional' => 0,
                        'ui' => 1,
                        'bidirectional_target' => array(
                        ),
                        'parent_repeater' => 'field_64dee36767ae0',
                    ),
                ),
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'options_page',
                    'operator' => '==',
                    'value' => 'page-links',
                ),
            ),
        ),
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'left',
        'instruction_placement' => 'label',
        'hide_on_screen' => '',
        'active' => true,
        'description' => '',
        'show_in_rest' => 0,
    ) );
}
add_action( 'acf/include_fields', 'drg_settings_acf_option_page');