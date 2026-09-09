<?php

/**
 * Default template for pages.
 *
 * Pages are composed from the ACF flexible content field "page_section" -- each
 * row resolves to template-parts/dynamic-content/{layout}/index.php.
 * See inc/functions-dynamic-content.php
 *
 * Sits above index.php in the template hierarchy, so it applies to every page
 * that has not been assigned a specific template.
 */

get_header();

drg_render_page_sections();

get_footer();
