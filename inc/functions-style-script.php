<?php 

/**
 * This function is used to make the enqueue script shorter
 * You just need to pass the file name and file path as parameters
 * File path is relative to theme folder
 * File versioning will automatically follow version in style.css
 */
function drg_print_css($name, $filePath) {
    $themeVersion = wp_get_theme()->get('Version');
    $cssPath = get_template_directory_uri() . '/assets/css/';
    return wp_enqueue_style($name,  $cssPath . $filePath, array(), $themeVersion, 'all');
}

function drg_print_js($name, $filePath, $deps = array()) {
    $themeVersion = wp_get_theme()->get('Version');
    $jsPath = get_template_directory_uri() . '/assets/js/';
    wp_enqueue_script($name, $jsPath . $filePath, $deps, $themeVersion, true);
}

/**
 * Function for load google font
 * No need to change anything, just change the google_font_url variable to your theme font
 */
function drg_add_google_fonts() {
    $google_font_url = 'https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700&display=swap';
    
    $onloadVal = "this.media='all'";
    
    echo '
    <link rel="preconnect" href = "https://fonts.gstatic.com" crossorigin />
    <link rel="preload" as = "style" href="' . $google_font_url .  '" />
    <link rel="stylesheet" href="' . $google_font_url . '" media="print" onload="' . $onloadVal . '" />
    <noscript>
        <link rel="stylesheet" href="' . $google_font_url . '" />
    </noscript>
    ';
}
add_action('wp_enqueue_scripts', 'drg_add_google_fonts');

/**
 * Function for enqueue css and js from our template
 * Please reminder to enqueue spesific css or js to spesific page
 * @link https://developer.wordpress.org/reference/functions/
 */
function drg_script_enqueue() {
    
    // Global CSS
    drg_print_css('layoutCss', 'layout.css');
    
    // Global JS
    drg_print_js('layoutJS', 'layout.js');

    /**
     * Dynamic content modules.
     * Only the modules this page actually uses are loaded. Both files are
     * optional -- most modules ship CSS but no JS -- so each is guarded by
     * file_exists rather than assumed.
     */
    if (function_exists('drg_get_page_section_layouts')) {
        foreach (drg_get_page_section_layouts(get_queried_object_id()) as $layout) {

            if (file_exists(get_theme_file_path('assets/css/module/' . $layout . '.css'))) {
                drg_print_css('module-' . $layout . 'Css', 'module/' . $layout . '.css');
            }

            // depends on layoutJS so window.DRG exists before the module runs
            if (file_exists(get_theme_file_path('assets/js/module/' . $layout . '.js'))) {
                drg_print_js('module-' . $layout . 'Js', 'module/' . $layout . '.js', array('layoutJS'));
            }
        }
    }

    // example code to add CSS and JS to Page Template
    // example is for page-home.php page template
    /*
    if (is_page_template('page-home.php')) {
        drg_print_css('homeCss', 'page/home.css');
        drg_print_js('homeJs', 'page/home.js', array('layoutJS')); // depend on layoutJS so window.DRG exists first
    }
    */
    
    // example code to add CSS and JS to Singular Page
    /*
    if (is_singular('post_type')) {
        drg_print_css('nameCss', 'page/filePath.css');
        drg_print_js('nameJs', 'page/filePath.js');
    }
    */
    
    // example code to add CSS and JS to Archive Page
    /*
    if (is_archive('post_type')) {
        drg_print_css('nameCss', 'page/filePath.css');
        drg_print_js('nameJs', 'page/filePath.js');
    }
    */
    
    // example code to add CSS and JS to 404 Page
    /*
    if (is_404()) {
        drg_print_css('404Css', 'page/404.css');
        drg_print_js('404Js', 'page/404.js');
    }
    */
    
}
add_action('wp_enqueue_scripts', 'drg_script_enqueue');