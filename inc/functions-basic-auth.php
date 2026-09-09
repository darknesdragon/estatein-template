<?php

/**
 * Function to Adds basic authentication and IP whitelisting
 */
function drg_basic_auth_and_ip_whitelist() {
    // Whitelisted IPs
    $whitelisted_ips = array(
        '127.0.0.1', // local
        // 'x.x.x.x', // add office / client IP per project
    );

    // Basic Auth credentials
    $auth_user = 'dragon';
    $auth_pass = 'dragon#dragon'; // please change following regulation

    // check if Search engine visibility is checked, if true, apply basic auth
    // the settings is located in admin Settings -> Reading
    if (get_option('blog_public') == 0) {
        // Check if the IP is whitelisted
        $user_ip = $_SERVER['REMOTE_ADDR'] ?? '';
        if (in_array($user_ip, $whitelisted_ips)) {
            return;
        }

        // Basic authentication
        // hash_equals() keeps the comparison constant time, and the null coalesce
        // stops an undefined index warning when the browser sends no credentials
        $authUser = $_SERVER['PHP_AUTH_USER'] ?? '';
        $authPass = $_SERVER['PHP_AUTH_PW'] ?? '';

        if ( ! hash_equals($auth_user, $authUser) || ! hash_equals($auth_pass, $authPass) ) {

            header('WWW-Authenticate: Basic realm="Protected Area"');
            header('HTTP/1.0 401 Unauthorized');
            echo 'Unauthorized';
            exit;
        }
    }
}

// Hook into WordPress init
add_action('init', 'drg_basic_auth_and_ip_whitelist');
