# Architecture

WordPress **classic** starter theme. Build with laravel-mix (webpack 5).
Bootstrap 5 + GSAP. ACF Pro and other plugins installed via TGMPA.

**Companion docs**

| Doc | Covers |
|---|---|
| [DESIGN-SYSTEM.md](DESIGN-SYSTEM.md) | palette, type scale, gutter, sizing anchors, contrast pairs, `.drg-container-fluid`, sticky header, font loading |
| [ADMIN.md](ADMIN.md) | admin colour schemes, disabled-button contrast, third-party admin overrides |
| [components/button.md](components/button.md) | `template-parts/button.php` and the shared hover pair |
| [components/header-banner.md](components/header-banner.md) | the dismissible promo bar |
| [components/navbar.md](components/navbar.md) | header, menu locations, offcanvas, icon sizing |
| [components/main-banner.md](components/main-banner.md) | the `main_banner` module |
| [CHANGELOG.md](CHANGELOG.md) | one entry per version bump |

## Folder layout

```
fe-starter-wp/
├── inc/                         PHP, split by concern
│   ├── functions-theme-setup.php    theme support, admin login, color scheme, menus
│   ├── functions-style-script.php   enqueue helpers (drg_print_css / drg_print_js)
│   ├── functions-acf.php            ACF option pages + field groups (code-registered)
│   ├── functions-plugin.php         TGMPA required/recommended plugins
│   ├── functions-custom.php         helpers (lang, page-data, admin separators)
│   ├── functions-dynamic-content.php  ACF module renderer + content helpers
│   ├── functions-icon.php           inline SVG icon reader
│   └── functions-basic-auth.php     optional HTTP basic auth (staging)
├── source/                      EDIT HERE (build input)
│   ├── scss/
│   │   ├── layout.scss              global styles (~80% of project)
│   │   ├── config/                  variables, functions, mixins, extends
│   │   ├── admin/                   login + admin color schemes
│   │   └── plugin/                  preset library
│   └── js/
│       ├── layout.js                global JS + window.DRG shared runtime
│       └── pages/                   page-specific JS (one file per page)
├── template-parts/
│   ├── dynamic-content/<layout>/    one folder per ACF flexible content layout
│   │   ├── index.php                    markup                     REQUIRED
│   │   ├── _style.scss                  styling, auto-compiled     optional
│   │   └── component.js                 behaviour, auto-compiled   optional
│   ├── button.php                   page-or-url button + optional icon
│   ├── icon.php                     inline SVG by name
│   └── debug-console.php            debug helper output
├── tools/                       developer scripts (never shipped to the browser)
│   ├── make-module.js               npm run make:module
│   └── make-icon.js                 npm run make:icon
├── assets/                      BUILD OUTPUT — do not edit by hand
│   ├── css/  ├── js/             gitignored; run a build after cloning
│   └── images/icon/                 committed inline SVGs
├── docs/                        this folder
└── webpack.mix.js               build config
```

## Build pipeline

`source/` → laravel-mix → `assets/`. **Never edit `assets/` directly** — it is regenerated.

`assets/` build output and `mix-manifest.json` are **not committed** in this starter (see
`.gitignore`) — the template stays clean. Run `npm install && npm run prod` after cloning,
and make the build a required step in each project's deploy. A derived project that prefers
to commit its compiled assets can delete the build-output block from `.gitignore`.

```bash
npm run dev      # one-off dev build
npm run watch    # rebuild on change
npm run prod     # minified production build
```

Each compiled entry is declared explicitly in `webpack.mix.js`.

## CSS model (2 files per page)

- `layout.css` — global, loaded on every page (~80% of styling).
- `page/<name>.css` — page-specific, loaded only on that page.

Enqueue inside a page condition in `functions-style-script.php`:

```php
if (is_page_template('page-home.php')) {
    drg_print_css('homeCss', 'page/home.css');
}
```

## JS model — `window.DRG` shared runtime (2 files per page)

The constraint: separate webpack entries are **separate module graphs**. If two
bundles each `import` GSAP, you get **two GSAP instances** that cannot coordinate
(ScrollTrigger registered in one is invisible to the other).

Solution: **`layout.js` imports GSAP/Bootstrap once and publishes the single
instance on `window.DRG`. Page files consume it — they never import gsap.**

```javascript
// source/js/layout.js
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
gsap.registerPlugin(ScrollTrigger);
window.DRG = { gsap, ScrollTrigger, /* ...,*/ fn: {} };
```

```javascript
// source/js/pages/home.js  — null-safe, no gsap import
const { gsap, ScrollTrigger, fn } = window.DRG || {};
if (gsap) gsap.from('.hero', { opacity: 0, y: 30 });
```

Why 2 files, not 1: under HTTP/2, parallel requests are cheap, and splitting lets
`layout.js` be **cached once site-wide** while only the small page file changes.
Bundling layout into every page would duplicate it and kill that cache benefit.

### Adding a new page JS file — do BOTH

1. **Build** — add to `webpack.mix.js`:
   ```javascript
   mix.js('source/js/pages/about.js', 'assets/js/page/')
       .sourceMaps(true, 'source-map');
   ```
2. **Enqueue** — in `functions-style-script.php`, depend on `layoutJS` so
   `window.DRG` exists first:
   ```php
   drg_print_js('aboutJs', 'page/about.js', array('layoutJS'));
   ```

The `layoutJS` dependency is required — without it WP may print the page script
before `layout.js`, leaving `window.DRG` undefined.

## Scroll fade — `data-drg-fade` / `-group` / `-item`

`layout.js` publishes a scroll-triggered fade-up that every module opts into from
markup, so nothing is hardcoded per component.

```php
data-drg-fade         one element, its own trigger
data-drg-fade-group   a container whose DIRECT CHILDREN batch and stagger
data-drg-fade-item    inside a group, batch THESE instead of the children
```

Four pieces have to agree, and each exists for a reason that is not obvious:

**1. The inline gate in `header.php`.** A synchronous `<head>` script adds
`.drg-js` to `<html>` before first paint. That class is the *only* thing that arms
the CSS start state, so with JS disabled it never runs and content is visible from
the first paint — no `<noscript>` needed. It must stay synchronous and in `<head>`;
deferring it reintroduces the flash it exists to remove.

**2. The 3s failsafe.** The same script removes `.drg-js` after 3 seconds, covering
a bundle that 404s or throws. GSAP writes *inline* styles and inline beats a class
selector, so once the bundle is alive this is a no-op — it can only ever reveal
elements GSAP never reached.

**3. The CSS start state in `layout.scss`.** `opacity` + `visibility`, mirroring
GSAP's `autoAlpha`, so the start state and the runtime govern exactly the same two
properties. Neither affects layout, so it costs nothing in CLS. The `:has()` guard
keeps the hidden set identical to what `drgFadeInit()` reveals:

```scss
.drg-js {
    [data-drg-fade],
    [data-drg-fade-item],
    [data-drg-fade-group]:not(:has([data-drg-fade-item])) > * { … }
}
```

Getting that wrong is silent and total — hide something JS never reveals and it
stays invisible for good, only once JS has booted.

**4. `drgFadeInit()` in `layout.js`.** Published as `window.DRG.fn.fadeInit` so a
module can re-arm after injecting markup. Three details are load-bearing:

- `start: 'clamp(top 85%)'` — an element near the page foot can compute a start
  position *beyond max scroll*, so `onEnter` never fires and it stays hidden for
  good. `clamp()` pins it inside the scrollable range and costs nothing elsewhere.
- the rise distance is resolved **when the tween fires**, not when the trigger is
  built. A resize before the element arrives still gets the right distance, and
  there is no `matchMedia` context to revert — a reverting context would reset an
  already-played element to invisible while its `once: true` trigger was dead.
- groups use `ScrollTrigger.batch()`, which groups by **arrival time** rather than
  DOM structure, so a responsive grid reveals row by row at every breakpoint
  without anyone computing what a row is.

Under `prefers-reduced-motion: reduce` the fade keeps its opacity change and drops
the translate — movement causes vestibular symptoms, an opacity change barely
registers. One code path, and the reveal still happens.

**Never animate a `.swiper-slide` or `.swiper-wrapper`.** Use `data-drg-fade-item`
on something inside the slide instead. **Above-the-fold elements should not be
tagged** — hiding an LCP element until JS boots trades a Core Web Vital for an
animation nobody sees.

## Dynamic content modules

Requires **ACF Pro** — flexible content and repeater are not in the free plugin.
With ACF absent the theme renders without dynamic content rather than failing.

A layout name maps 1:1 to a folder:

```
template-parts/dynamic-content/{layout}/
    index.php       markup                      REQUIRED
    _style.scss     styling, auto-compiled      optional
    component.js    behaviour, auto-compiled    optional
```

Three systems meet, and none needs a registration step:

| Stage | Where | How |
|---|---|---|
| Render | `drg_render_page_sections()` | maps `get_row_layout()` to the folder, through `locate_template` so a child theme can override one module |
| Compile | `webpack.mix.js` | globs the directory at startup, emits `assets/css/module/{layout}.css` and `assets/js/module/{layout}.js` |
| Enqueue | `drg_get_page_section_layouts()` | read during `wp_enqueue_scripts`, loads only the modules the page uses |

> **`drg_render_page_sections()` and `drg_get_page_section_layouts()` are a
> connected pair.** One draws the markup, the other enqueues that module's CSS
> and JS, and both default to the same field name. Changing the default on one
> without the other gives you a page whose modules render unstyled — or styles
> loaded for modules that never appear.

> **A module's JS must be named exactly `component.js`.** `webpack.mix.js` globs
> that filename; anything else compiles to nothing, silently.

```bash
npm run make:module <layout_name> [--js]
```

The scaffolder reads the layout's sub fields straight out of `acf-json/` and wires
each by name and type. Save the field group in ACF first and it writes working
markup; run it before that and you get a plain scaffold.

**Restart the build after adding a folder** — webpack scans the directory when it
starts, so a running `npm run watch` will not see a new module.

`sassOptions.includePaths` points at `source/scss`, so a module writes
`@use 'config' as *` rather than climbing three levels out. Output paths are
written in full because every module's source shares the same filename and would
otherwise collide on `_style.css`.

### Global Modules — content reused across pages

A `global_module` layout renders another post's `page_section` field in place, so
content used on several pages is authored once. Point the field group at both the
page post type and a global-module post type and the module is built from the same
library of layouts as a page. It emits no markup of its own — a reference, not a
section — so it needs no `_style.scss` and no fade attribute.

**Nesting is deliberately unsupported.** A Global Module has the same field group
as a page, so it could reference another module — or itself — and recurse forever.
`drg_render_page_sections()` skips `global_module` rows at depth 1, and
`drg_get_page_section_layouts()` caps at the same depth. The two must agree:
following the reference in the collector is not optional, because a page whose only
row is a `global_module` would otherwise report just that name and the referenced
post's CSS would never load — the section would render unstyled.

## Images and the placeholder

Always attach an image through `imageRatio()`, whatever its size — the mixin
reserves the box before the image loads.

A missing image renders a placeholder, not nothing:

```php
drg_the_acf_image( $image, $class, $size, $fallback = true, $orientation = 'landscape' );
drg_the_post_thumbnail( $post, $class, $size );
drg_the_placeholder_image( $class, $orientation );
```

`drg_placeholder_image( $orientation )` returns the file **and** its intrinsic
size, because the two always travel together — a third ratio later is one more row
rather than one more function at every call site. An unknown orientation falls back
to landscape: a frame with the wrong ratio still renders, a fatal in a template
takes the page.

The placeholder is not an attachment, so it gets no srcset and its `width`/`height`
are explicit — that is what lets the browser reserve the box. `alt` is empty and
**present**: the placeholder depicts nothing about the content it stands in for, and
a missing `alt` would let a screen reader announce the filename instead.

Pass `$fallback = false` only where a grey panel reads worse than nothing, and
document the reason in place.

## Icons

```php
get_template_part( 'template-parts/icon', null, array(
    'name'  => 'lucide-circle-arrow-right',
    'class' => 'drg-hero__icon',
) );
```

Files live in `assets/images/icon/`, committed to the repo and **inlined** rather
than linked, so they inherit `currentColor` and cost no extra request.
`drg_get_icon()` caches one file read per icon per request, validates the name, and
returns an empty string for anything missing — a bad icon name renders nothing
rather than taking the page. It injects `aria-hidden="true" focusable="false"`,
since an icon beside its own label adds nothing for a screen reader.

```bash
npm run make:icon lucide:circle-arrow-right          # from Iconify
npm run make:icon -- --from ./export.svg drg-logo    # from a local/Figma export
```

Both paths run through `normalise()`, which strips literal fills, px dimensions and
the white bounding rect, and **de-duplicates `clipPath` ids** — those are
document-global, so inlining the same icon twice would clip the second against the
wrong path. The order of operations inside it is load-bearing and commented as such.

The fetch happens at build time on a developer machine only. The site never depends
on Iconify at runtime and icons work with JavaScript disabled.

> **`tools/make-icon.js` — the `fromIndex === -1` guard is load-bearing.**
> Without `--from`, `fromIndex` is `-1`, so `i !== fromIndex + 1` reads as
> `i !== 0` — which drops the icon id itself and leaves `target` undefined. The
> Iconify path could then never receive its argument.

## Dummy data — a module rendering ahead of its post type

A module that lists posts cannot be reviewed before the post type has content. Put
the stand-in rows in a `dummy-data.php` beside `index.php`, returning an array the
template loops the same way it loops real posts, and delete the file once real
content exists. Keeping it separate means the template never grows a branch that
has to be removed later.

## Enqueue helpers

`inc/functions-style-script.php`:

- `drg_print_css($name, $filePath, $deps = array())`
- `drg_print_js($name, $filePath, $deps = array())`

Both version assets by the theme version from `style.css` (cache-busting).

`drg_script_enqueue()` also runs the dynamic-content module loop — see above. It
is guarded by `function_exists()` and `file_exists()`, so a project that deletes
the module system, or a module that ships CSS but no JS, both work unchanged.

## Plugins (TGMPA)

`inc/functions-plugin.php` declares required/recommended plugins. Everything
resolves from the wordpress.org repository except **ACF Pro**, which is not on
wordpress.org and whose official update server needs a licence key — so there is
no URL TGMPA could install from.

It is listed anyway, with two keys instead of a `source`:

- `external_url` links the plugin **name** to advancedcustomfields.com rather than
  a dead wordpress.org install link.
- `is_callable => 'acf_add_options_page'` — a function that exists only in Pro —
  satisfies the requirement once Pro is active, regardless of the folder name a
  manual install used.

TGMPA still offers an Install action on that row, which would 404. It is only
reachable while Pro is absent, which is exactly when the `external_url` link
beside it is the right thing to click.

## Conventions

- PHP function prefix: `drg_`. CSS/attribute prefix: `drg-` / `data-drg-`.
- Admin color scheme slug: `dragon`.
- Strings are plain, not gettext — translation goes through Polylang.
- Edit `source/`, never `assets/`.
- All JS/PHP access null-safe (`a?.b || fallback`, `window.DRG || {}`).
