# Change Log

## Version 0.4.0 - 09/09/2026

Ports the generic systems built in the James Brehm project back into the starter.
Project-specific code stays there: no nav walker, no decoration mixins, no palette
or $fontSizes changes, no Swiper, no reduced-motion override. All `jb` / `jb-` /
`JB_` naming is renamed to `drg` / `drg-` / `DRG_`.

### inc/functions-dynamic-content.php -- NEW

    - drg_render_page_sections(): renders every ACF flexible content row in author order, via locate_template so a child theme can override one module

    - drg_get_page_section_layouts(): layout names used by a page, following a global_module reference one level so the referenced post's assets still load

    - global_module recursion is capped at depth 1 in BOTH functions -- a module referencing itself renders nothing rather than looping

    - drg_resolve_link(): page-or-url field trio to one href, tolerant of a mistyped "Page:page" choice

    - drg_to_post_array(): post_object to always an array, so a template loops either way if ACF's "multiple" setting is flipped

    - drg_placeholder_image() / drg_placeholder_image_url() / drg_the_placeholder_image(): a missing image renders a sized placeholder, not a collapsed box

    - drg_the_acf_image() / drg_the_post_thumbnail(): image with placeholder fallback, opt out with the fourth argument

    - drg_the_multiline(): textarea with line breaks preserved

    - drg_youtube_id() / drg_youtube_embed_url(): any pasted YouTube URL to a nocookie embed, empty string on a bad paste

    - drg_get_menu_tree(): two-level menu array via wp_get_nav_menu_items(), so inherited item labels are not blank

    - drg_phone_href(): digits-only tel: / wa.me target, leading + preserved

    - every ACF-touching function is guarded by function_exists -- with ACF absent the theme renders without dynamic content instead of failing

### inc/functions-icon.php + template-parts/icon.php -- NEW

    - drg_get_icon(): inline SVG from assets/images/icon/, cached one read per icon per request

    - name validated against a lowercase-hyphen pattern and a missing file returns empty -- a bad icon name renders nothing rather than taking the page

    - injects aria-hidden and focusable="false", so an icon beside its own label adds nothing for a screen reader

### template-parts/button.php -- NEW

    - reads a page-or-url field trio through drg_resolve_link(), renders nothing without a resolvable target

    - variant picks the shape (pill or bare link), style picks the palette within it

    - optional icon with icon_position start or end (default end) and --start / --end modifier classes; no icon argument means no icon, since the starter ships an empty icon folder

    - opt-in scroll fade via the fade argument -- a caller cannot put the attribute on markup the part owns, and an element already inside a fade group would be armed twice

    - labels are plain strings, not gettext -- string translation goes through Polylang

### page.php -- NEW

    - default page template rendering drg_render_page_sections(); sits above index.php in the template hierarchy

    - page-template.php is left as-is with its Bootstrap demo

### tools/ -- NEW

    - make-module.js: npm run make:module scaffolds a module, reading the layout's sub fields out of acf-json/ and wiring each by name and type; falls back to a plain scaffold when the layout is not saved yet

    - make-icon.js: npm run make:icon fetches from Iconify, or imports a local/Figma export with --from

    - make-icon normalise(): strips literal fills, px dimensions and the white bounding rect, and de-duplicates clipPath ids -- those are document-global, so inlining one icon twice clipped the second against the wrong path

### source/js/layout.js

    - scroll-fade runtime on the existing window.DRG: data-drg-fade, data-drg-fade-group, data-drg-fade-item

    - start position is clamp(top 85%) -- an element near the page foot can otherwise compute a start beyond max scroll, never fire onEnter, and stay hidden for good

    - rise distance resolved when the tween fires, not when the trigger is built, so there is no matchMedia context to revert an already-played element back to invisible

    - prefers-reduced-motion keeps the opacity change and drops the translate, one code path

    - groups use ScrollTrigger.batch(), which groups by arrival time rather than DOM structure, so a responsive grid reveals row by row at every breakpoint

    - data-drg-fade-bound claim so a second init pass cannot double-trigger

    - published as window.DRG.fn.fadeInit so a module can re-arm after injecting markup

### header.php

    - synchronous inline head script adds .drg-js before first paint, so tagged elements never flash at full opacity; with JS disabled it never runs and content is visible from the first paint

    - 3s failsafe removes .drg-js, covering a bundle that 404s or throws; GSAP writes inline styles which beat the class, so it is a no-op once the bundle is alive

    - add body_class() to the body tag

### source/scss/layout.scss

    - .drg-js fade start state using opacity + visibility, mirroring GSAP's autoAlpha so no element is half-owned by CSS and half by the runtime

    - :has() guard keeps the hidden set identical to what drgFadeInit() reveals -- a group with marked items hides those, not its children

### source/scss/config/_function.scss

    - vwSize() caps every value at its measurement at $maxViewport, so a screen wider than that renders pixel-identical instead of inflating the design

    - negatives clamp with max() rather than min(), which would pick the more negative value and keep growing outward

    - the cap lives in vwSize(), so it applies to everything routing through unit() -- vwUnit, vwDesktop, vwMobile, vwStacked, typo(), fontVar(), imageRatio()

### source/scss/config/_mixin.scss

    - add vwStacked(): vwMobile emits only the 767 and 480 anchors, so a layout stacking at Bootstrap's xl (1200) had the stacked structure from 1199 down but no values until 767, leaving 768-1199 with the property absent entirely

### source/scss/config/_variable.scss

    - add $maxViewport (1920) -- the width the design freezes at

    - add $fontPrimary, kept in sync with the google font enqueued in functions-style-script.php

### webpack.mix.js

    - auto-compile every folder under template-parts/dynamic-content/ containing _style.scss or component.js -- no entry per module

    - sassOptions.includePaths so a module can use the config partial without climbing three levels out

    - output paths written in full, since every module's source shares the same filename and would collide on _style.css

### inc/functions-style-script.php

    - enqueue only the modules a page actually uses, read during wp_enqueue_scripts

    - module JS depends on layoutJS so window.DRG exists before it runs

    - both files guarded by file_exists -- most modules ship CSS but no JS

### inc/functions-plugin.php

    - restore Advanced Custom Fields PRO to the required list using external_url + is_callable instead of a plugin-zip source

    - external_url links the plugin name to advancedcustomfields.com rather than a dead wordpress.org install link

    - is_callable set to acf_add_options_page (Pro only) satisfies the requirement for a manual install under any folder name

### package.json

    - add make:module and make:icon scripts

    - pin autoprefixer and postcss rather than relying on laravel-mix's bundled copies

### .gitignore

    - ignore assets/css/module/, assets/js/module/ and assets/js/*.LICENSE.txt

### assets/images

    - add placeholder-image.png (486x324) and placeholder-vertical.png (402x536)

    - add icon/ with a readme covering both make:icon paths

### docs

    - ARCHITECTURE.md: add Scroll fade, Dynamic content modules, Images and the placeholder, Icons, and Dummy data sections

    - CLAUDE.md: add the image, motion, sizing, icon and module rules

    - template-parts/dynamic-content/readme.txt: the module contract

---

## Version 0.3.1 - 09/09/2026

### repo hygiene

    - delete build output from assets/ (layout.css, admin/*.css, layout.js + all .map) and mix-manifest.json

    - remove stale assets/css/admin/colorScheme-tmdr.css left over from the drg rebrand

    - .gitignore build output so the starter stays clean -- run `npm install && npm run prod` after clone

    - remove unreferenced assets/images/wp-admin-default*.png (3 files, 2.0 MB)

    - screenshot.png -> screenshot.jpg, re-encoded at quality 85 (1.2 MB -> 177 KB, same 1200x900)

### drg_ prefix -- rename remaining un-prefixed functions

    - getCurrentLang() -> drg_get_current_lang()

    - getPageData() -> drg_get_page_data()

    - add_admin_menu_separator() -> drg_add_admin_menu_separator()

    - show_debug_helper() -> drg_show_debug_helper()

    - mytheme_set_default_admin_color_scheme() -> drg_set_default_admin_color_scheme()

    - custom_admin_logo() -> drg_custom_admin_logo()

    - move_login_language_dropdown() -> drg_move_login_language_dropdown()

    - basic_auth_and_ip_whitelist() -> drg_basic_auth_and_ip_whitelist()

### inc/functions-custom.php

    - drg_get_current_lang(): fall back to get_locale() when pll_current_language() returns false (was emitting <html lang="">)

    - drg_get_page_data(): guard non-array get_field() result before foreach, guard missing sub-keys, return null instead of a diagnostic string on miss

    - drg_add_admin_menu_separator(): guard non-array $menu and missing $item[2]

    - drg_show_debug_helper(): drop echo -- get_template_part() prints directly and returns void

### inc/functions-theme-setup.php

    - add DRG_LOGIN_BACKGROUND_FALLBACK and DRG_LOGIN_LOGO_FALLBACK constants

    - login background placeholder moved off the timedoor server to picsum.photos (seeded so it does not reshuffle)

    - drg_custom_admin_login(): null-safe ACF ['url'] and wp_get_attachment_image_src()[0] access, escape both URLs with esc_url()

    - drg_custom_admin_logo(): skip output when no admin bar is showing on the frontend

    - drg_custom_admin_logo(): fix broken CSS -- missing semicolon after content: "" killed every declaration after it, plus a doubled semicolon on height

### inc/functions-basic-auth.php

    - null-safe $_SERVER['REMOTE_ADDR'] / PHP_AUTH_USER / PHP_AUTH_PW access

    - compare credentials with hash_equals()

    - change default credentials to dragon / dragon#dragon

    - drop the hardcoded office IP from the whitelist, leave localhost + a placeholder comment

### inc/functions-plugin.php

    - remove the $source plugin server -- every remaining plugin resolves from wordpress.org

    - remove the ACF Pro entry (not on wordpress.org, installed manually per project)

    - fix Polylang Slug source, which pointed at a doubled /plugins/plugins/ path

### header.php

    - update drg_get_current_lang() / drg_show_debug_helper() call sites

    - escape the lang attribute with esc_attr()

### template-parts/debug-console.php

    - initialise $origin and guard backtrace keys

    - match the renamed drg_show_debug_helper in the backtrace lookup

    - escape $origin with esc_html()

### docs

    - note in ARCHITECTURE.md and CLAUDE.md that assets/ is gitignored and must be built after clone

---

## Version 0.3.0 - 28/06/2026

### package.json

    - switch gsap from office-hosted .tgz to public npm package (^3.13.0)

### rebrand: tmdr -> drg, timedoor -> dragon

    - rename all PHP function prefixes tmdr_ -> drg_ (inc/*.php)

    - rename admin color scheme slug 'timedoor' -> 'dragon' (+ label, css path colorScheme-drg.css)

    - rename admin separator css class .tmdr-* -> .drg-* (functions-custom.php + scss)

    - rename source/scss/admin/colorScheme-tmdr.scss -> colorScheme-drg.scss

    - rename source/scss/plugin/_tmdrPreset.scss -> _drgPreset.scss

    - rename scss vars $timedoor-green -> $drg-green, $timedoorGreen(Hover) -> $drgGreen(Hover)

    - style.css author -> Drago

### source/js — shared runtime (window.DRG) pattern

    - layout.js now imports GSAP/Bootstrap once and publishes window.DRG (single shared instance)

    - add source/js/pages/home.js example consuming window.DRG (null-safe)

    - webpack.mix.js: add page-JS build entry (source/js/pages -> assets/js/page)

### inc/functions-style-script.php

    - drg_print_js() now accepts $deps so page scripts can depend on layoutJS

### docs

    - move change-log.md -> docs/CHANGELOG.md

    - add docs/ARCHITECTURE.md and root CLAUDE.md

---

## Version 0.2.1 - 07/11/2024

### inc/funtions-plugin.php

    - remove White Label CMS plugin from recommendation

    - add Toolbar Publish Button plugin as recommendation

### inc/functions-theme-setup.scss

    - add function to change wordpress logo to be website favicon if available

### source/scss/admin/colorSheme-client.scss

    - adjust button disabled color

---

## Version 0.2.0 - 07/11/2024

### inc/functions-theme-setup.php

    - reorder function

    - change default login banner image to use weblink instead of file

### source/scss/admin folder

    - add folder for admin specific styling

### source/scss/tmdr-admin.scss

    - renamed to be login.scss

    - move to `source/scss/admin`

### source/scss/tmdr-color-scheme.scss

    - renamed to be colorScheme-tmdr.scss

    - move to `source/scss/admin`

### source/scss/tmdr-web-specific-color-scheme.scss

    - renamed to be colorScheme-client.scss

    - move to `source/scss/admin`

### source/scss/config/_variable.scss

    - remove admin specific variable so it will not conaint major difference with main style presets

### screenshot.png

    - added screenshot.png

---

## Version 0.1.23 - 05/11/2024

### inc/functions-style-script.php

    - move admin styling to inc/function-theme-setup.php

### inc/function-theme-setup.php

    - login default banner code adjustment

    - company logo code adjustment

    - add Web Specific Admin Color Scheme

    - add function to activate `timedoor` color scheme when activating Timedoor Theme Starter

### source/scss/config/_variable.scss

    - change color example following Prime Codex Color

    - add admin variant color for Web Specific Color Scheme

### source/scss/tmdr-admin-color-scheme.scss

    - organise color to use variable instead of direct

### source/scss/tmdr-admin-web-specific-color-scheme.scss

    - added file

---

## Version 0.1.22 - 01/11/2024

### functions-theme-setup.php

    - add default banner image for login page

### tmdr-admin.scss

    - adjust button color

    - adjust link color

---

## Version 0.1.21 - 31/10/2024

### package.json

    - remove gsap file

    - change installation path to use Timedoor server

---

## Version 0.1.20 - 31/10/2024

### class-tgm-plugin-activation.php

    - move to inc folder instead on root

### functions-plugin.php

    - change local file to source website

### functions-theme-setup.php

    - add placeholder image using placehold.co

### functions.php

    - add tgmpa file to function.php

---

## Version 0.1.19 - 27/09/2024

### acf-json

    - added acf-json folder to record ACF field update to theme

    - added index.php so it won't be accessible

### inc/function-acf.php

    - added login logo field

### inc/function-theme-setup.php

    - adjust admin login logo with the new ACF field

### source/scss/tmdr-admin.scss

    - body height adjustment

### functions.php

    - adjust file path so it will alwayd use file on theme folder

---

## Version 0.1.18 - 09/08/2024

### source/scss/

    - update SCSS starter

---

## Version 0.1.17 - 09/08/2024

### source/scss

    - update SCSS starter

### inc/functions-theme-setup.php

    - add function to show RankMath meta keyword

---

## Version 0.1.16 - 04/07/2024

### inc/functions-plugin.php

    - update ACF version to 6.3.3

---

## Version 0.1.15 - 04/07/2024

### source/scss/config/_extend.scss
### source/scss/config/_mixin.scss
### source/scss/_config_.scss

    - update styling presets

---

## Version 0.1.14 - 04/07/2024

### inc/functions-basic-auth.php

    - add basic auth function

---

## Version 0.1.12 - 17/05/2024

### inc/functions-theme-setup.php

    - fix login_headertitle to login_headertext for newer wordpress

---

## Version 0.1.11 - 16/05/2024

### inc/functions-custom.php

    - fix getCurrentLang() function on get_locale() part

### header.php

    - fix format-detection meta tag to use ""

---

## Version 0.1.8 - 22/02/2024

### inc/functions-theme-setup.php

    - update admin background hook

---

## Version 0.1.7 - 05/02/2024

### inc/functions-custom.php

    - update admin menu separator code

### inc/functions-theme-setup.php

    - update admin menu separator data and code

### source/scss/tmdr-admin-color-scheme.scss

    - add admin menu separator styling

---

## Version 0.1.6 - 14/10/2023

### changelog.md

    - standarise writing

### layout.scss

    - remove bootstrap component
    - change `@import` to `@use` for _init-bootstrap.scss

### _init_bootstrap.scss

    - add all bootstrap component

---

## Version 0.1.5 - 17/10/2023

### functions-custom.php

    - added `show_debug_helper()` function

### functions-plugin.php

    - added Wordfence as a recommended plugin
    
### functions-theme-setup.php

    - add new admin color scheme

### header.php

    - add `show_debug_console()` example

### tmdr-admin-color-scheme.scss

    - new file added

### template-parts/debug-console.php

    - new file added

---

## Version 0.1.4 - 03/10/2023

### funcstions.php

    - fix file path

---

## Version 0.1.3 - 15/09/2023

### function-style-script.php

    - fix google font integration

### functions-theme-setup.php

    - add javascript to change language switcher palcement on login page

---

## Version 0.1.2 - 07/09/2023

### change-log.md

    - add `change-log.md` file

---

## Version 0.1.1 - 07/09/2023

### style.css

    - add `Starter Version`

### functions-acf.php

    - update comment
    - change `TMDR Theme Setting` option page -> `Theme Setting`
    - change `TMDR Theme Setting` menu slug from `tmdr-theme-setting` -> `theme-setting`
    - change `General Setting` option sub-page -> `Login `
    - change `Login Image` input field name -> `Login Background Image`

### functions-custom.php

    - add `add_admin_menu_separator` function

### functions-theme-setup.php

    - update ACF integration for background image

### _init-bootstrap.scss

    - add Bootstrap Nav scss component

### _mixin.scss

    - update elypsis mixin

### tmdr-admins.scss

    - change `%loginLogoSetting` height -> max-height
    - change `%loginLogoSetting` background-size cover -> contain
    - add `%loginLogoSetting` background-position center

### header.php

    - add class `navbar-nav` to menu `ul`
    - change `item_wrap` -> `items_wrap`