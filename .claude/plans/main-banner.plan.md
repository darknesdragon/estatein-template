# Plan: Main Banner Module

**Source**: design exports `20260910 - module screenshot/main-banner/` + ACF layout `main_banner`
**Complexity**: Medium-Large — 7 files (2 new), first real `.drg-button` styles, a rotating-text badge
**Status**: awaiting confirmation — **one open decision on hover colour, see below**

## Summary

The first dynamic-content module: a split hero with copy left and a full-bleed
image right, a circular rotating-text badge straddling the seam, two buttons, and
three stat cards. Mobile reflows completely — image on top, badge overlapping it,
buttons stacked full-width, stats 2 + 1.

Brings three things the theme still lacks: the `.drg-button` pill styles (markup
has existed since the starter with **zero** CSS), the `heading-1` / `stats-number`
/ `body-m` tokens, and a position-aware heading level.

## Blockers cleared before planning

| | |
|---|---|
| ACF field was `page_sections`, theme default `page_section` | resolved — theme default changes to `page_sections` (Task 1) |
| Two seamless clones with `prefix_name: 0` collided | **fixed by you** — verified `prefix_name: 1` on both |

Without the first, `have_rows()` would never match and the module would render
nothing at all — silently, with the CSS never enqueuing either.

## Measurements

| | desktop | mobile |
|---|---|---|
| export | 1920 × 814 | **358** × 893 |
| section ground | `#000000` | `#000000` |
| content starts | x 162 (the 1600 container) | x 0 of a 358 box |
| image starts | x ≈ **1000** | full width, top |
| Learn More | x 162–300 (139w), transparent + border | full width |
| Browse Properties | x 321–511 (191w), `#703BF7` | full width |
| button gap | 20 | stacked |
| stat cards | 3 × ~237w, gap ~23, fill `#191919` | 2 in a row + 1 full-width |

Mobile exports at **358** = `390 − 32`, so a 16px gutter (we ship 15px via
`$containerPadding` — close enough to absorb).

**Two things the export disagrees with us on**, both flagged rather than assumed:

1. The ground measures `#000000`, not grey/08 `#141414`. Your instruction was
   "leave it transparent, the project is already grey-08", so the plan does that
   — the section paints nothing. If the design really wants pure black, that is a
   one-line change.
2. The image starts at **x 1000**, which is 52% of the container, not the 50% a
   `col-xl-6` gives (962). Plan uses 50%; the 38px is inside the noise of a
   gradient edge, but say if you want it exact.

### Type tokens

| token | desktop | mobile stored | colour | used by |
|---|---|---|---|---|
| `heading-1` | 60 / 600 / 1.2 | **34.46** (28 @390) | white | title |
| `stats-number` | 40 / 700 / 1.5 | **29.54** (24 @390) | white | stat number |
| `body-m` | 18 / 500 / 1.5 | **19.69** (16 @390) | grey/60 | description, stat label, buttons |

`stats-number` rather than `heading-2` — your call, and the right one: a size
named for a heading level that never renders as a heading misleads every future
reader.

`body-m` **replaces** `nav-link`, which was byte-identical. The navbar repoints to
it, so the theme keeps one token instead of two that must be edited in lockstep.

Contrast: grey/60 `#999999` on grey/08 is **6.47:1** — comfortably AA.

## ⚠️ Open decision — hover fill

You asked for every hover to become purple 75. That works for **text on a dark
ground** (the banner's link) but inverts as a **fill behind white text**:

| Fill | White on it | AA |
|---|---|---|
| purple 60 `#703BF7` | **5.74:1** | ✓ |
| purple 65 `#8254F8` | 4.59:1 | ✓ barely |
| purple 70 `#946CF9` | 3.64:1 | ✗ |
| purple 75 `#A685FA` | **2.85:1** | ✗ |

**This plan assumes purple 60 fill + white text** for every filled hover, and
keeps purple 75 for the text-only `--light` link. Both read as purple, both pass.
Alternative if you want 75 as the fill: pair it with grey/08 text (6.46:1), which
passes but inverts the look.

## Patterns to mirror

| Category | Source | Pattern |
|---|---|---|
| Module contract | `template-parts/dynamic-content/readme.txt` | `index.php` + `_style.scss`, auto-compiled, auto-enqueued |
| Module SCSS head | scaffolded `main_banner/_style.scss` | `@use 'config' as *` — resolves via `includePaths`, unlike `components/` |
| Field guards | `inc/functions-dynamic-content.php:30` | `function_exists()` before every ACF call |
| Link trio | `template-parts/button.php:31` | `drg_resolve_link( $type, $page, $url )` |
| Multiline text | `drg_the_multiline()` | textarea with `nl2br`, no auto-formatting |
| Mobile comment | `_navbar.scss:142` | `@include vwUnit(padding-block, 12, 14.77); // 12 @390` |
| Every element classed | `header-banner/index.php` | `__container` exists purely for the inspector |
| Icon | `template-parts/icon.php` | inlined SVG, `1em`, inherits `currentColor` |

## Files to change

| File | Action | Why |
|---|---|---|
| `inc/functions-dynamic-content.php` | UPDATE | default field name → `page_sections` (both functions) |
| `source/scss/config/_variable.scss` | UPDATE | `nav-link` → `body-m`; add `heading-1`, `stats-number` |
| `source/scss/components/_navbar.scss` | UPDATE | `typo(nav-link)` → `typo(body-m)` |
| `source/scss/components/_button.scss` | UPDATE | the pill styles — primary, secondary, hover |
| `template-parts/dynamic-content/main_banner/index.php` | REWRITE | scaffold is a stub |
| `template-parts/dynamic-content/main_banner/_style.scss` | REWRITE | scaffold is a stub |
| `assets/images/icon/heroicons-arrow-up-right.svg` | CREATE | `npm run make:icon heroicons:arrow-up-right` |

---

## Task 1 — `page_sections` as the default

**File**: `inc/functions-dynamic-content.php`, lines 27 and 68

```php
// before
function drg_render_page_sections( $field_name = 'page_section', $post_id = null, $depth = 0 )
function drg_get_page_section_layouts( $post_id = null, $field_name = 'page_section', $depth = 0 )

// after
function drg_render_page_sections( $field_name = 'page_sections', $post_id = null, $depth = 0 )
function drg_get_page_section_layouts( $post_id = null, $field_name = 'page_sections', $depth = 0 )
```

**Both must move together.** The renderer draws the markup and the collector
decides which module CSS to enqueue; changing one leaves a page that either
renders unstyled or styles nothing.

## Task 2 — tokens

**File**: `source/scss/config/_variable.scss`

`nav-link` is renamed, not duplicated:

```scss
// before                          // after
nav-link: (                        body-m: (
    desktop: (                         desktop: (
        font-size: 18,                     font-size: 18,
        font-weight: 500,                  font-weight: 500,
        line-height: 1.5                   line-height: 1.5
    ),                                 ),
    mobile: ( font-size: 19.69 )       mobile: ( font-size: 19.69 ) // 16 @390
)                                  )
```

Plus two new keys:

```scss
heading-1: (
    desktop: ( font-size: 60, font-weight: 600, line-height: 1.2 ),
    mobile:  ( font-size: 34.46 ) // 28 @390
),
stats-number: (
    desktop: ( font-size: 40, font-weight: 700, line-height: 1.5 ),
    mobile:  ( font-size: 29.54 ) // 24 @390
)
```

Colour stays out of `$fontSizes` — the map only emits size/weight/line-height,
and the same token is white in one place and grey/60 in another.

## Task 3 — navbar repoint

**File**: `source/scss/components/_navbar.scss:141`

```scss
@include typo(nav-link);   →   @include typo(body-m);
```

Purely mechanical; the values are identical, so nothing renders differently.

## Task 4 — the pill styles

**File**: `source/scss/components/_button.scss`

`template-parts/button.php` has shipped since the starter emitting
`btn btn-primary drg-button` with **no CSS behind any of it** — Bootstrap's
`buttons` module is commented out in `_init-bootstrap.scss:66`. This module is
what finally defines them.

```scss
.drg-button {
    @include typo(body-m);
    @include vwUnit(padding-block, 12, 14.77);   // 12 @390
    @include vwUnit(padding-inline, 20, 24.62);  // 20 @390
    border-radius: 10px;
    border: 1px solid transparent;   // reserved, same reason as the nav pill
    @include transition(all .3s ease);

    &.btn-primary {
        color: color( white );
        background-color: color( purple );
        border-color: color( purple );
    }

    // the outlined "Learn More": transparent fill, visible edge
    &.btn-secondary {
        color: color( white );
        background-color: transparent;
        border-color: color( grey, 15 );
    }

    &.btn-primary,
    &.btn-secondary {
        @include fullState {
            color: color( white );
            background-color: color( purple );   // 60 -- see the open decision
            border-color: color( purple );
        }
    }
}
```

Geometry deliberately matches the nav pill (12/20, radius 10) so a button and a
nav item read as the same family.

## Task 5 — the module markup

**File**: `template-parts/dynamic-content/main_banner/index.php` — rewrite

### Heading level by position

```php
$heading_tag = ( function_exists( 'get_row_index' ) && 1 === get_row_index() ) ? 'h1' : 'h2';
```

`get_row_index()` is 1-based and valid inside the `have_rows()` loop the renderer
already runs, so no counter has to be threaded through. Guarded because
everything ACF-touching in this theme is.

### The button pair

The clones are prefixed now, so they read independently:

```php
$buttons = get_sub_field( 'banner_buttons' );

get_template_part( 'template-parts/button', null, array(
    'label' => $buttons['secondary_button_button_label']  ?? '',
    'type'  => $buttons['secondary_button_button_target'] ?? '',
    'page'  => $buttons['secondary_button_page_target']   ?? null,
    'url'   => $buttons['secondary_button_url_target']    ?? '',
    'style' => 'secondary',
    'class' => 'drg-main-banner__button',
) );
```

Primary is the same shape without `'style'`. `button.php` already renders nothing
when the target does not resolve, so an unfilled button simply disappears.

### The badge

```
.drg-main-banner__badge            (an <a> when clickable, else a <div>)
    svg .drg-main-banner__badge-text     text on a circular path
    span .drg-main-banner__badge-icon    heroicons arrow-up-right, static
```

SVG `<textPath>` rather than per-character spans: one element, real selectable
text, no JS, and scales without re-measuring. **It also sets up the GSAP work
cleanly** — spinning the badge later is one tween on the `<svg>`, and because the
arrow is a *sibling* rather than a child it stays upright while the text turns.

**The path needs a unique id.** `<textPath href="#…">` resolves document-wide, so
two banners on one page would both bind to the first path. Derived from
`get_row_index()`.

`circle_text_clickable` decides `<a>` vs `<div>`; the href comes from
`drg_resolve_link()` on the layout-level `circle_text_target` / `page_target` /
`url_target` trio — which does **not** collide with the buttons' identically named
fields, because those sit inside the `banner_buttons` group.

## Task 6 — the module SCSS

**File**: `template-parts/dynamic-content/main_banner/_style.scss` — rewrite

Layout in normal flow, not absolute:

```scss
.drg-main-banner {
    &__inner {                 // .container
        display: flex;
        align-items: center;
    }

    &__content         { flex: 0 0 50%; }

    &__image-container {
        flex: 0 0 50%;
        position: relative;    // the badge parents here

        /**
         * Escapes the container to the viewport edge, then stops.
         *
         * 50% is half the container, 50vw half the viewport, so the fluid term
         * is exactly the container's right gutter. The fixed term freezes that
         * at its 1920 value -- max() picks the less negative, so past 1920
         * nothing grows and a 4K screen renders the 1920 composition.
         *
         * Same principle as .drg-container-fluid, hence the same $maxViewport.
         */
        margin-right: max(calc(50% - 50vw), calc(50% - #{$maxViewport * 0.5}px));
    }

    @media (max-width: 1199.98px) {
        &__inner { flex-direction: column; }
        &__content, &__image-container { flex: 0 0 auto; width: 100%; }
        &__image-container { margin-right: 0; }   // bleed off once stacked
    }
}
```

Height stays `max(content, image)` because nothing leaves the flow — which is the
one thing the absolute version cannot promise. Stats become a 2-column grid below
xl with the third cell spanning both.

## Validation

```bash
npm run dev
php -l template-parts/dynamic-content/main_banner/index.php

# module CSS compiled to its own file, not into layout.css
ls assets/css/module/

# tokens present, nav-link gone
node scratchpad/check-tokens.js
```

In the browser:

- A page whose **first** row is this module renders `<h1>`; second row renders `<h2>`.
- Both buttons resolve and point at different targets — proof the clone prefix works.
- Image reaches the viewport's right edge at 1920; at 2560 it stops at the 1920 line.
- Section height follows the taller of image and content — test with a 3-line title.
- Below 1200: image on top, badge overlapping, buttons full-width, stats 2 + 1.
- Two `main_banner` rows on one page — badges must not share a path id.
- Empty image field → no placeholder, no collapse.

## Risks

| Risk | Likelihood | Mitigation |
|---|---|---|
| Only one of the two field-name defaults changed | **Certain** if rushed | Task 1 changes both in one edit |
| `<textPath>` id collides across two instances | **Certain** without it | id derived from `get_row_index()` |
| White text on a purple 75 fill | **Certain** if 75 is chosen | open decision above; plan uses 60 |
| Content taller than image overflows | Low | flow layout, not absolute |
| Bleed grows unbounded on 4K | **Certain** without the clamp | `max()` against `$maxViewport` |
| Stat label and description drift apart | Low | one `body-m` token serves both |
| `.btn-primary` unstyled elsewhere | Low | Bootstrap's buttons module stays off; ours is the only definition |

## Out of scope

- `quick_options` — the other layout in the same group, agreed as a separate pass.
- Badge **animation** — GSAP discussed later; markup is built so it needs no
  restructuring.
- `make-module.js` has no `case 'group'`, so it scaffolded `banner_buttons` as
  `esc_html( $array )`. Harmless here since this file is rewritten, but the next
  module with a group will hit it.
- `docs/CHANGELOG.md` still owes entries for `make-icon.js`, the banner and the navbar.

## Acceptance

- [ ] `npm run dev` clean; `php -l` passes
- [ ] `assets/css/module/main_banner.css` emitted
- [ ] First-row instance renders `h1`, later rows `h2`
- [ ] Primary and secondary buttons resolve to independent targets
- [ ] Image bleeds to the viewport edge at 1920, frozen beyond it
- [ ] Section height driven by the taller column
- [ ] Mobile: image top, badge overlap, stacked buttons, stats 2 + 1
- [ ] `nav-link` gone from `$fontSizes`; navbar unchanged visually
- [ ] No file outside the seven listed is modified
