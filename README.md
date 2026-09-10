# Estatein

A custom WordPress **classic** theme building the Estatein real estate design.
No page builder, no block editor, no FSE.

| | |
|---|---|
| Staging | https://estatein-template.freedev.app |
| Design | [Figma - Real Estate Business Website UI Template, Dark Theme](https://www.figma.com/design/QcaIdBQsrrfy5JJFn8GLZV/Real-Estate-Business-Website-UI-Template---Dark-Theme-%7C-Produce-UI--Community-?node-id=139-8120&t=TKrFuvpqlpxj4nc3-0) |
| Stack | WordPress classic theme, laravel-mix 6 (webpack 5), SCSS, Bootstrap 5, GSAP, ACF Pro |

## What is delivered

- Design system built from the Figma comp - palette, type scale, gutter, breakpoint anchors
- Customised WordPress admin - login screen, colour scheme, contrast fixes
- Header banner - dismissible promo bar with a localStorage gate
- Navbar - sticky header, responsive offcanvas, inline SVG icons
- Main Banner module - hero, ScrollTrigger count-up stats, rotating SVG badge
- Quick Options module - ACF admin and module scaffold in place, markup and styling outstanding
- Written documentation in [`docs/`](docs/)

## On scope

The brief allowed 4 hours.
That window realistically covers the navigation alone, which would not show much.

I spent 8 hours total so I could also deliver the Main Banner and, more importantly, the system the rest of the design would be built on.
The sections below are that system.

## Design system

Every dimension goes through a sizing mixin rather than a hand-written `px`, `rem` or `vw`.

```scss
@include vwUnit(padding-bottom, 100, 38.4); // 30 @375
```

Values are taken from the comp at their own breakpoint anchor - 1920 and 1199 for desktop, 767 and 480 for mobile.
Output is capped at `$maxViewport`, so a screen wider than the cap renders pixel-identical instead of inflating the whole design.

`vwStacked()` exists for properties that only apply once a layout has stacked.
Bootstrap's `xl` columns stack at 1200 but `vwMobile()` starts at 767, so the 768-1199 band would otherwise get the stacked structure with none of its values.

Full detail, including the mobile multiplier and the contrast pairs, is in [`docs/DESIGN-SYSTEM.md`](docs/DESIGN-SYSTEM.md).

## Module architecture

Each ACF flexible content layout maps to one self-contained folder.

```
template-parts/dynamic-content/main_banner/
├── index.php        markup       required
├── _style.scss      styling      optional
└── component.js     behaviour    optional
```

`webpack.mix.js` compiles every such folder automatically.
`inc/functions-style-script.php` then enqueues a module's CSS and JS **only on pages whose ACF layouts actually use it**, so a page never ships assets for modules it does not render.
There is nothing to register in PHP.

Scaffolding a new module:

```bash
npm run make:module <layout_name> [--js]
```

`drg_render_page_sections()` renders every flexible content row in author order through `locate_template`, so a child theme can override a single module.
A `global_module` reference is followed one level and capped at depth 1, so a module referencing itself renders nothing rather than looping.

Contract in [`template-parts/dynamic-content/readme.txt`](template-parts/dynamic-content/readme.txt).

## Shared GSAP runtime

Two JS files per page at most - `layout.js` (global) plus `pages/<name>.js` (page-specific).

`layout.js` is the only file that imports GSAP and Bootstrap.
It publishes the single instance on `window.DRG`.
Page and module bundles read from `window.DRG` and never import GSAP themselves, because a second import creates a second GSAP instance that cannot coordinate with layout's ScrollTriggers.

On top of that runtime sits a declarative scroll-fade system:

```php
data-drg-fade         // one element, its own trigger
data-drg-fade-group   // a container whose DIRECT CHILDREN batch and stagger
data-drg-fade-item    // inside a group, batch THESE instead of the children
```

`layout.scss` hides exactly what the JS reveals, kept in step by a `:has()` guard.
Above-the-fold elements are deliberately left untagged, so an LCP element is never held behind `opacity: 0` waiting for JS to boot.

## Icons and images

Icons are inline SVG files committed under `assets/images/icon/`, so they inherit `currentColor` and work with JavaScript disabled.

```bash
npm run make:icon lucide:circle-arrow-right
```

The Iconify fetch is build-time on a developer machine only.
The site never depends on Iconify at runtime.

Images attach through an `imageRatio()` mixin that reserves the box before the image loads, so nothing shifts on load.
A missing image renders a placeholder rather than nothing, so a frame keeps its size when an editor leaves an ACF field empty.

## Null safety

The theme is written to keep rendering when a value is missing.

- Every ACF access sits behind `function_exists()`, so the theme renders with ACF absent
- JS reads the shared runtime defensively - `const { gsap } = window.DRG || {}` - and returns early rather than throwing
- Module JS guards against double-binding and respects `prefers-reduced-motion`

## Not built yet

The four Quick Options cards are not styled.
The ACF field group and the module scaffold are in place, and the measurements are already taken from the comp, so the remaining work is markup and styling.

Everything below the Quick Options row in the design is out of scope for this submission.

## Provenance

This theme sits on an in-house classic-theme starter I maintain, currently at version `0.4.0` (see `style.css` and [`docs/CHANGELOG.md`](docs/CHANGELOG.md)).
The starter provides the generic systems - build pipeline, module renderer, sizing mixins, enqueue helpers, icon reader, TGMPA plugin list, admin scaffolding.

Built fresh for Estatein: the palette and type scale, the admin colour scheme, the header banner, the navbar, the `main_banner` module, the `quick_options` scaffold, and the per-component documentation under `docs/components/`.

## Local setup

Requires WordPress, PHP 7.4+, and Node with npm.

```bash
npm install
npm run dev      # development build
npm run watch    # rebuild on change
npm run prod     # production build, minified
```

`source/` is the build input.
`assets/` is the generated output and is **gitignored**, so a fresh clone has no CSS or JS until you run a build.

**Edit `source/`, never `assets/`.**

### Plugins

Plugin requirements are declared through TGMPA in `inc/functions-plugin.php` and prompt on activation.

Everything resolves from wordpress.org except **ACF Pro**, which is not on the plugin repository and is listed via `external_url` for manual install.
ACF **Pro** specifically - the module system needs flexible content and repeater fields, which the free plugin does not include.

ACF options pages and the shared field groups are code-registered in `inc/functions-acf.php`.
The rest sync through the JSON in `acf-json/`, so no database import is needed.

### Conventions

- PHP functions are prefixed `drg_`
- CSS follows BEM under a `drg-` namespace
- Strings are plain, not gettext - translation runs through Polylang via `pll_register_string()`

## Documentation

| Doc | Covers |
|---|---|
| [ARCHITECTURE.md](docs/ARCHITECTURE.md) | folder layout, build pipeline, CSS and JS models, module system, enqueue helpers |
| [DESIGN-SYSTEM.md](docs/DESIGN-SYSTEM.md) | palette, type scale, gutter, sizing anchors, contrast pairs, font loading |
| [ADMIN.md](docs/ADMIN.md) | admin colour schemes, contrast handling, third-party admin overrides |
| [components/](docs/components/) | per-component specs - button, header banner, navbar, main banner |
| [CHANGELOG.md](docs/CHANGELOG.md) | one entry per version bump |
| [CLAUDE.md](CLAUDE.md) | working rules for AI assistants in this repo |

## Staging access

The staging site can sit behind HTTP basic auth, implemented in `inc/functions-basic-auth.php`.

The gate is deliberately tied to WordPress's own "Discourage search engines" setting under Settings -> Reading, so it can be switched on or off from the admin without a deploy.
Local requests are whitelisted by IP.

The WordPress login is moved off `/wp-admin` to `/admin-estatein` as a light hardening measure.

Admin credentials are not published here.
They are available on request.
