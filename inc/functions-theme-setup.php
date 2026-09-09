<?php

/**
 * Placeholder assets used until the client uploads their own
 * Login background is overridden by ACF field "login_background_image" (Theme Settings -> Login)
 * Login logo falls back to the theme custom logo before reaching the placeholder
 * The picsum seed keeps the same image on every load instead of reshuffling
 */
define( 'DRG_LOGIN_BACKGROUND_FALLBACK', 'https://picsum.photos/seed/drg-login/1920/1080' );
define( 'DRG_LOGIN_LOGO_FALLBACK', 'https://placehold.co/300x84?text=Company+Logo' );

/**
 * This function load login page style
 * We use custom login page style to make better layout
 */
function drg_login_css() {

    drg_print_css('drgadmincss', 'admin/login.css');

}
add_action( 'login_enqueue_scripts', 'drg_login_css' );

/**
 * Register new color scheme for admin in profile menu
 */
function drg_admin_color_scheme(){
	//Get the theme directory
	$theme_dir = get_template_directory_uri();

	//Dragon
	wp_admin_css_color( 'dragon', __( 'Dragon' ),
		$theme_dir . '/assets/css/admin/colorScheme-drg.css',
		array( '#242e3b', '#fff', '#d54e21' , '#10af13')
	);

	// Website COlor Scheme
	wp_admin_css_color( 'website_color_scheme', __( 'Website Color Scheme' ),
		$theme_dir . '/assets/css/admin/colorScheme-client.css',
		// array( '#242e3b', '#fff', '#d54e21' , '#10af13')
	);
}
add_action('admin_init', 'drg_admin_color_scheme');

/**
 * Automatically activate Dragon Color Scheme when Activating custom theme.
 */

function drg_set_default_admin_color_scheme() {
    $user_id = get_current_user_id(); // Get the ID of the currently logged-in user
    update_user_meta($user_id, 'admin_color', 'dragon'); // Set the color scheme for the current user

    // Optional: To set the color scheme for all users, loop through each user.
    /*
    $users = get_users();
    foreach ($users as $user) {
        update_user_meta($user->ID, 'admin_color', 'my_custom_scheme');
    }
    */
}
add_action('after_switch_theme', 'drg_set_default_admin_color_scheme');

/**
 * This function is use to get image background and logo for login page
 * The background image is set from admin page 
 */
function drg_custom_admin_login() {

    $hasAcf = class_exists('acf') && function_exists('get_field');

    // Add Background Image to Admin Login Page
    $backgroundField = $hasAcf ? get_field('login_background_image', 'login') : null;
    $adminImage      = $backgroundField['url'] ?? '';

    if ( $adminImage ) {
        $backgroundPosition = 'center';
    } else {
        $adminImage         = DRG_LOGIN_BACKGROUND_FALLBACK;
        $backgroundPosition = 'bottom';
    }

    echo '
    <style type="text/css">
        body.login:before {
            background-color: var(--web-identity);
            background-image: url("' . esc_url($adminImage) . '");
            background-position: ' . $backgroundPosition . ';
        }
    </style>
    ';

    // Add Custom Logo to Admin Login Page
    $custom_logo = get_theme_mod( 'custom_logo' );
    $logoField   = $hasAcf ? get_field('login_logo', 'login') : null;
    $logoSrc     = $custom_logo ? wp_get_attachment_image_src( $custom_logo, 'full' ) : false;

    $logo = $logoField['url']
        ?? ( is_array($logoSrc) ? ( $logoSrc[0] ?? null ) : null )
        ?? DRG_LOGIN_LOGO_FALLBACK;

    echo '<style type="text/css">
    #login h1 a {
        background-image: url(' . esc_url($logo) . ');
    }
    </style>';

}
add_action('login_enqueue_scripts', 'drg_custom_admin_login');

/**
 * Function to set the logo link in login page to our website home page
 * If we didn't change it, it will redirect to wordpress.org website
 */
function drg_logo_url_login() {
    return home_url();
}
add_filter('login_headerurl', 'drg_logo_url_login');

/**
 * Change logo alt text in login page to match the website name
 */
function drg_logo_url_title_login() {
    return wp_get_theme()->get('Theme Name');
}
add_filter('login_headertext', 'drg_logo_url_title_login');

/**
 * Add google recaptcha badge in login page
 */
function drg_recaptcha_badge_login() { 
    ?>
    <div class="message captcha-text">This site is protected by reCAPTCHA and the Google <a href="https://policies.google.com/privacy" target="_blank" rel="noreferrer noopener">Privacy Policy</a> and <a href="https://policies.google.com/terms" target="_blank" rel="noreferrer noopener">Terms of Service</a> apply.</div>
    
    <?php 
}
add_action('login_form','drg_recaptcha_badge_login');

/**
 * Change login logo title
 */
function drg_custom_title_login($origtitle) { 
    
    return get_bloginfo('name').' - Login';
    
}
add_filter('login_title', 'drg_custom_title_login', 99);

/**
 * Function to move language switcher on login page
 * Add javascript to move language switcher inside element with id login
 * Insert the language switcher to last element using appendChild
 */
function drg_move_login_language_dropdown() {
    ?>
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function () {
            const languageDropdown = document.querySelector('.language-switcher');
            const loginForm = document.querySelector('#login');
            if (languageDropdown && loginForm) {
                loginForm.appendChild(languageDropdown);
            }
        });
    </script>
    <?php
}
add_action('login_enqueue_scripts', 'drg_move_login_language_dropdown');

/**
 * Function to change the wordpress logo on admin dashboard to be website favicon
 * this function is generated using chatGPT
 */
function drg_custom_admin_logo() {
    // No admin bar means no element to restyle, emit nothing for logged out visitors
    if ( ! is_admin() && ! is_admin_bar_showing() ) {
        return;
    }

    // Replace this URL with your uploaded favicon URL
    $favicon_url = get_site_icon_url(); // Automatically fetch the site's favicon if set, otherwise replace with the URL of your image

    if (!$favicon_url) {
        return;
    }

    echo '<style type="text/css">
        #wpadminbar #wp-admin-bar-wp-logo > .ab-item .ab-icon:before {
            content: "";
            width: 100%;
            height: 100%;
            color: rgba(0, 0, 0, 0);
            background-image: url(' . esc_url($favicon_url) . ') !important;
            background-position: center;
            background-size: contain;
        }
    </style>';
}
add_action('admin_head', 'drg_custom_admin_logo');
add_action('wp_head', 'drg_custom_admin_logo'); // Optional: Add to frontend admin bar

/**
 * Function to add custom logo upload via menu appearance > customize
 * This function also add support for <title> tag in wp_head, so you dont need to add it manually
 */
function drg_theme_setup() {
    /**
     * Enable title tag for wordpress
     * @link https://codex.wordpress.org/Title_Tag
     */
    add_theme_support( 'title-tag' );

    // Enable custom logo upload
    $defaults = array(
        'flex-height' => true,
        'flex-width'  => true,
        'header-text' => array('site-title', 'site-description'),
    );
    add_theme_support('custom-logo', $defaults);
}
add_action('after_setup_theme','drg_theme_setup');

/**
 * Enable wordpress submenu in admin under menu appearance
 * Also register starter menu which is main navbar and footer navbar
 * register_nav_menu need 2 parameter
 * First parameter is location identifier, like a slug
 * Second parameter is location descriptive text
 * @link https://developer.wordpress.org/reference/functions/register_nav_menu/
 */
function drg_menu_setup() {
    add_theme_support('menus');
    register_nav_menu('main_menu', 'Main Navbar');
    register_nav_menu('footer', 'Footer Navbar');
    
}
add_action('init', 'drg_menu_setup');

/**
 * Replace custom logo class
 */
function drg_change_logo_class($html) {
    $html = str_replace('custom-logo-link', 'navbar-brand', $html);
    $html = str_replace('custom-logo', 'img-responsive', $html);
    return $html;
}
add_filter('get_custom_logo', 'drg_change_logo_class');

/**
 * Run the function add_example_separators() to add separator in admin menu
 * You can find the function in function-custom.php file
 * Find wordpress menu position in link below
 * @link https://developer.wordpress.org/reference/functions/add_menu_page/#menu-structure
 */
function drg_add_separators() {
    /**
     * Positions for Core Menu Items
        2 Dashboard
        4 Separator
        5 Posts
        10 Media
        15 Links
        20 Pages
        25 Comments
        59 Separator
        60 Appearance
        65 Plugins
        70 Users
        75 Tools
        80 Settings
        99 Separator

     * Note: use position 32 - 56 for custom post type position in admin, so it doesn't get overwrite by this separator
    */
    drg_add_admin_menu_separator(4, 'content');
    // drg_add_admin_menu_separator(31, 'custom-post-types');
    drg_add_admin_menu_separator(57, 'drg-settings');
    drg_add_admin_menu_separator(59, 'wordpress-settings');
    drg_add_admin_menu_separator(99, 'others');
}
add_action('admin_menu', 'drg_add_separators');

/**
 * Add <meta name="keywords" content="focus keywords">.
 */
add_filter( 'rank_math/frontend/show_keywords', '__return_true');

/**
 * Function to disable wordpress scaling down image
 * @link https://stackoverflow.com/questions/75044018/selectively-prevent-wordpress-from-generating-additional-image-sizes-on-upload
 */
add_filter('intermediate_image_sizes_advanced', '__return_empty_array');
