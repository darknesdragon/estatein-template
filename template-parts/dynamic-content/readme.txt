Dynamic content modules
=======================

One folder per ACF flexible content layout. The folder name MUST match the
layout name exactly (the value ACF stores in acf_fc_layout).

    template-parts/dynamic-content/{layout}/
        index.php       markup                      REQUIRED
        _style.scss     styling, auto-compiled      optional
        component.js    behaviour, auto-compiled    optional

Requires ACF PRO -- flexible content and repeater fields are not in the free
plugin. With ACF absent the theme still renders, just with no dynamic content.

Adding a module
---------------
1. Add the layout to your flexible content field in ACF.
2. Scaffold it:  npm run make:module <layout_name> [--js]
3. Restart the build -- webpack.mix.js scans this directory when it STARTS, so
   `npm run watch` will not notice a brand new folder until restarted.

That is all. No registration anywhere in PHP.

The scaffolder reads the layout's sub fields straight out of acf-json/ and wires
each one by name and type. Save the field group in ACF first and it writes
working markup; run it before that and you get a plain scaffold instead.

How it works
------------
Render   inc/functions-dynamic-content.php -> drg_render_page_sections()
         maps get_row_layout() to this folder, via locate_template so a child
         theme can override a single module.

Compile  webpack.mix.js globs this directory and emits
         assets/css/module/{layout}.css and assets/js/module/{layout}.js

Enqueue  inc/functions-style-script.php reads the page's layouts during
         wp_enqueue_scripts and loads only those modules' assets.

Writing index.php
-----------------
The file runs inside the_row(), so get_sub_field() works directly.

Helpers in inc/functions-dynamic-content.php:
    drg_resolve_link( $target, $page_object, $url )   page-or-url field trio
    drg_to_post_array( $value )                       post_object -> array
    drg_the_acf_image( $image, $class, $size )        image array -> <img>
    drg_the_post_thumbnail( $post, $class, $size )    featured image -> <img>
    drg_the_placeholder_image( $class, $orientation ) placeholder -> <img>
    drg_the_multiline( $text )                        textarea with line breaks
    drg_youtube_embed_url( $url )                     pasted link -> nocookie embed
    drg_get_menu_tree( $location )                    2-level menu array
    drg_phone_href( $number )                         tel: / wa.me target

Shared parts:
    get_template_part( 'template-parts/button', null, array( ... ) )
    get_template_part( 'template-parts/icon',   null, array( ... ) )

A missing image renders a placeholder, not nothing, so a frame keeps its size
when an editor leaves a field empty. Pass false as the fourth argument to
drg_the_acf_image() to opt out -- do that only where a grey panel reads worse
than nothing, and say why in place.

Strings are plain, not gettext. Translation goes through Polylang -- see
pll_register_string() in inc/functions-polylang.php.

Writing _style.scss
-------------------
Start with:  @use 'config' as *;

That resolves through an includePath, so no ../../../ climbing. All the theme
tokens are available: color(), typo(), vwUnit(), vwDesktop(), vwMobile(),
vwStacked(), imageRatio().

Writing component.js
--------------------
Read the shared runtime from window.DRG. Never import gsap -- that creates a
second instance which cannot coordinate with layout.js.

    const { gsap, ScrollTrigger } = window.DRG || {};
    if (!gsap) return;

Module JS is enqueued with layoutJS as a dependency, so window.DRG always
exists by the time it runs.

Reusable content across pages
-----------------------------
A `global_module` layout renders another post's page_section field in place, so
content used on several pages is authored once. Point its field group at both
your page post type and the global module post type, and the module is built
from the same library of layouts as a page.

It emits no markup of its own -- it is a reference, not a section -- so it needs
no _style.scss and no fade attribute.

Nesting is deliberately unsupported: drg_render_page_sections() skips
global_module rows at depth 1, so a module referencing another module (or
itself) renders nothing rather than looping. drg_get_page_section_layouts()
caps at the same depth, so the two agree.

Dummy data -- a module rendering ahead of its post type
-------------------------------------------------------
A module that lists posts cannot be reviewed before the post type has content.
Put the stand-in rows in a dummy-data.php beside index.php, returning an array
the template loops the same way it loops real posts, and delete the file once
real content exists. Keeping it separate means the template never grows a
branch that has to be removed later.
