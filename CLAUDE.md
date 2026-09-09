# CLAUDE.md

Guidance for AI coding assistants working in this repo. This is a **starter
template** — rename/retune per project (theme name, author, `drg_` prefix,
plugin list). See `docs/ARCHITECTURE.md` for the full model.

## What this is

WordPress **classic** theme starter. No block editor / FSE. Stack:

- Build: **laravel-mix 6** (webpack 5)
- CSS: **SCSS** + **Bootstrap 5**
- JS: vanilla ES modules + **GSAP** (npm)
- Fields/options: **ACF Pro**, installed manually per project (listed in TGMPA
  via `external_url`, since it is not on wordpress.org). Flexible content and
  repeater are Pro-only, so the dynamic content system needs Pro specifically.

## Build commands

```bash
npm install          # install deps (gsap pulls from public npm)
npm run dev          # dev build
npm run watch        # rebuild on change
npm run prod         # production (minified) build
```

`source/` is the input; `assets/` is generated output — and it is **gitignored** here.
A fresh clone has no CSS/JS until you run a build.

## Hard rules

- **Edit `source/`, never `assets/`.** `assets/` is build output and is overwritten.
- **PHP functions are prefixed `drg_`.** Keep the `add_action`/`add_filter` hook
  string in sync with the function name when renaming.
- **Null-safety everywhere.** Guard every access:
  `const x = obj?.a?.b || fallback;` / `const { gsap } = window.DRG || {};`.
  The project must keep running even when a value is missing. Anything touching
  ACF goes behind `function_exists()` — the theme must render with ACF absent.
- **Strings are plain, not gettext.** Translation goes through Polylang —
  `pll_register_string()` in `inc/functions-polylang.php`. Do not add `__()`.

## JS pattern (important)

2 files per page max: `layout.js` (global) + `pages/<name>.js` (page-specific).

- `layout.js` is the **only** file that imports GSAP/Bootstrap; it publishes the
  single instance on `window.DRG`.
- Page files read `window.DRG` (null-safe) and **must not import gsap** — a second
  import creates a second GSAP instance that can't coordinate with layout's.
- Adding a page JS file requires BOTH a `webpack.mix.js` entry AND a
  `drg_print_js(..., array('layoutJS'))` enqueue. See `docs/ARCHITECTURE.md`.

## CSS pattern

`layout.scss` = global (~80%); `source/scss/page/*.scss` = page-specific. Enqueue
page CSS inside the matching `is_page_template()` / `is_singular()` condition in
`inc/functions-style-script.php`.

## Images

**Always use the `imageRatio()` mixin when attaching an image**, whatever its size.
Big or small makes no difference: the mixin reserves the box before the image
loads, so nothing shifts on load.

```scss
@include imageRatio($desktopDimension, $mobileDimension, $maxWidth, $objectFit, $className);
@include imageRatio(1920 1080, 375 240, false, cover);
```

**A missing image renders a placeholder, not nothing.** `drg_the_acf_image()` and
`drg_the_post_thumbnail()` fall back to `assets/images/placeholder-image.png`, so a
frame keeps its size when an editor leaves a field empty. Pass `false` as the
fourth argument to opt out — do that only where a grey panel reads worse than
nothing (a full-bleed background behind light text, or one grey box scrolling
among real logos), and say why in place. The path lives in
`drg_placeholder_image_url()`; change it there, not at a call site.

## Motion — the scroll fade

Every module gets it. A module without it looks broken beside the ones that have
it, so the fade is the default and its **absence** is what needs justifying.

```php
data-drg-fade         // one element, its own trigger
data-drg-fade-group   // a container whose DIRECT CHILDREN batch and stagger
data-drg-fade-item    // inside a group, batch THESE instead of the children
```

Put the group on whatever element already has the right children. Adding a wrapper
purely to hold the attribute usually means the group is on the wrong element. Two
elements that are not siblings cannot share a group — give them a
`data-drg-fade` each and they fire in the same frame.

`template-parts/button.php` takes `'fade' => true` because a caller cannot put the
attribute on markup the part owns. Opt-in, since an element already a direct child
of a group would otherwise be armed twice.

**Never animate a `.swiper-slide` or a `.swiper-wrapper.`** GSAP's transform fights
Swiper's own translate3d, and the slide carries `transition-property: transform`,
which smears every frame GSAP writes. An element *inside* a slide is fine — that
is what `data-drg-fade-item` is for.

**If you change what a group animates, change the CSS start state with it.**
`layout.scss` hides exactly what `drgFadeInit()` reveals, kept in step by a
`:has()` guard. Hide something JS never reveals and it stays invisible for good —
silently, and only once JS has booted.

**Above-the-fold elements should not be tagged.** Hiding an LCP element behind
`opacity: 0` until JS boots trades a Core Web Vital for an animation nobody sees.

**Ask when a module has no animation spec.** What counts as a leaf, what counts as
a group and what order things reveal in differ per module; guessing produces
something that has to be redone.

## Sizing

Every dimension goes through `vwUnit()` / `vwDesktop()` / `vwMobile()` /
`vwStacked()`, taking the raw pixel value from the design at its own breakpoint
anchor (1920 and 1199 desktop, 767 and 480 mobile). Do not hand-write `px`, `rem`
or `vw`.

Values are capped at `$maxViewport` (`_variable.scss`), so a screen wider than
that renders pixel-identical rather than inflating the whole design.

**Desktop values are literal; mobile values are not.** A desktop value is the
design pixel straight from a 1920 comp. A mobile value is emitted at the 480
anchor, so it renders `value / 480 * 375` on a 375-wide phone — feed it a raw 375
design pixel and it comes out at 78% of the intended size. Store `design * 1.28`
and record the intent in a comment:

```scss
@include vwUnit(padding-bottom, 100, 38.4); // 30 @375
```

The same rule governs the `mobile` entry of every `$fontSizes` key.

**`vwStacked()` for anything that only exists once a layout has stacked.**
`vwMobile()` starts at 767, but Bootstrap's `xl` columns stack at 1200 — so a
property written with `vwMobile` inside a `max-width: 1199.98px` rule gives
768–1199 the stacked *structure* with none of its *values*. `vwStacked()` adds the
1199 anchor and carries its own media query, so it does **not** go inside one.

Known seam, accepted: the 1199 anchor renders a mobile design value against a
desktop width, so the property grows toward 1199 and steps crossing 767 into the
phone anchor. Do not "fix" it per component.

`$fontSizes` holds **only sizes a component actually uses**. Add one when a
component needs it; do not park speculative sizes there.

## Icons

```php
get_template_part( 'template-parts/icon', null, array(
    'name'  => 'lucide-circle-arrow-right',
    'class' => 'drg-hero__icon',
) );
```

Inline SVG files in `assets/images/icon/`, committed and inlined so they inherit
`currentColor`. Add one with `npm run make:icon lucide:circle-arrow-right`. The
fetch is build-time on a developer machine only — the site never depends on
Iconify at runtime and icons work with JavaScript disabled.

## Dynamic content modules

Each ACF flexible content layout maps to
`template-parts/dynamic-content/<layout_name>/` with `index.php`, `_style.scss`
and an optional `component.js`. `webpack.mix.js` compiles every such folder
automatically, and `inc/functions-style-script.php` enqueues a module's assets
only on pages whose layouts actually use it. Nothing to register in PHP.

```bash
npm run make:module <layout_name> [--js]
```

Restart the build after adding a folder — webpack scans the directory at startup.
Full contract in `template-parts/dynamic-content/readme.txt`.

## When changing a component

- Check whether it is shared (used by other templates/partials) before editing;
  if so, confirm the cross-impact.
- Renaming a CSS class that PHP emits (e.g. admin separators) means changing
  **both** the PHP and the SCSS — they are a connected pair.

## Conventions recap

- Theme metadata: `style.css` header.
- Plugins: `inc/functions-plugin.php` (TGMPA). Everything resolves from
  wordpress.org except ACF Pro, which is listed via `external_url`.
- Changelog: `docs/CHANGELOG.md` — add an entry per version bump.
