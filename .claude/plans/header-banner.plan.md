# Plan: Header Banner

**Source**: design exports `20260910 - module screenshot/header-banner/` + ACF group `Banner Header Settings`
**Complexity**: Medium — 8 files (3 new), one new global layout primitive, one shared component touched
**Status**: approved with revisions — see "Revisions" below, which override the body of this plan

## Revisions (agreed after the plan was written)

1. **`.drg-container-fluid` padding is a flat `15px`**, not `32 / 19.69` fluid.
   Deliberately a literal px and deliberately constant at every viewport, so it
   lines up with Bootstrap's column gutter. This is a documented exception to the
   "no hand-written px" rule — a gutter that scaled would stop matching `.row`,
   which Bootstrap emits at a fixed size.

2. **Bootstrap's gutter is overridden to 30px.** Bootstrap **5**'s
   `$grid-gutter-width` default is `1.5rem` = **24px** (the 30px figure is
   Bootstrap 4), so columns currently sit at 12px, not 15. Setting it to 30 puts
   `.col` padding at 15 and, via `$container-padding-x: $grid-gutter-width * .5`,
   `.container` at 15 as well. Everything meets at 15.

3. **One source of truth**: `$containerPadding: 15px` in `_variable.scss`, used by
   `.drg-container-fluid`, the close button's `right`, and the Bootstrap override.

4. **Close button `right` = `$containerPadding`.** Correct reasoning — an
   absolutely positioned child resolves against the containing block's *padding
   box*, so `right: 0` would be flush with the bar edge. Needs no extra wrapper.
   **Deviation on record:** the comp measures the × at **32px** from the edge; this
   ships **15px**, trading comp fidelity for gutter consistency.

5. **Text ↔ button gap is 10px.** Stored as `vwUnit(column-gap, 10, 12.31)` so it
   renders 10px at both the 1920 and 390 anchors.

6. **Project defaults are `grey/08` background and white text**, set as Bootstrap
   variable overrides (`$body-bg`, `$body-color`, `$font-family-base`) in
   `_init-bootstrap.scss`, **not** as a `body { }` rule. Bootstrap re-exports them
   as `--bs-body-bg` / `--bs-body-color`, and its components build on those —
   `--bs-offcanvas-color: var(--bs-body-color)`, and that offcanvas is the mobile
   nav in `header.php`. A plain body rule would have left the mobile menu as dark
   text on white. This also retires the stale `body { font-family }` rule in
   `layout.scss`, whose own comment asked for exactly this move once Bootstrap was
   enabled. The banner's `grey/10` (`#1A1A1A`) then reads as a slightly lighter
   strip on the darker page.

7. `__inner` side padding becomes **42** (close 32 + gap 10) rather than 40, so
   long copy still clears the × now that the container gutter shrank to 15.

## Summary

A dismissible promo bar at the very top of `<header>`, above the nav, driven by the
ACF options page `Customize Settings`. Full-bleed dark ground with a pattern
image, centred text, an underlined "Learn More" link rendered through the shared
button part, and a circular × that remembers dismissal per visitor.

Brings three reusable things with it: a `.drg-container-fluid` layout primitive,
the first real `$fontSizes` key, and base styles for `.drg-button` (which has
markup but no CSS today).

## Measurements — derived, not eyeballed

The PNG exports were decoded and measured. Every stated value reconciled, which
is why the numbers below are trusted rather than estimated:

```
1920:  18 × 1.5 = 27 line box  +  18 × 2 padding = 36   →  63   ✓ export is 1920×63
 390:  12 × 1.5 = 18 line box  +  40 + 20 padding = 60  →  78   ✓ export is  390×78
```

| Measured | @1920 | @390 |
|---|---|---|
| close button bbox | **32 × 32** | **26 × 26** |
| close icon bbox | 14 (12 + stroke AA) | 10 (9 + stroke AA) |
| right margin | **32** | **16** |
| circle vertical centre | 30.5 | **48.5** |
| bar vertical centre | 31.0 | 38.5 |
| background | `#191919` | `#191919` |

Two conclusions fall out of that table:

**The × tracks the text line, not the bar.** At 390 the circle centre measures
48.5 while the bar centre is 38.5. Text-line centre is `40 + 9 = 49`. The 10px
offset is the asymmetric mobile padding, and the measurement confirms the
alignment decision independently.

**The close button must be absolutely positioned.** At 1920 it is 32px tall
against a 27px line box — in normal flow the bar would compute to `32 + 36 = 68`,
not 63.

`#191919` vs grey/10 `#1A1A1A` is one step per channel, i.e. PNG export rounding.
Confirms grey/10.

### Mobile conversion

Design comp is **390**, so the multiplier is `480 / 390 = 1.230769` — **not** the
`1.28` in CLAUDE.md, which assumes a 375 comp. Getting this wrong renders
everything ~5% small.

| Property | @390 | stored | comment to write |
|---|---|---|---|
| font-size | 12 | **14.77** | `// 12 @390` |
| padding-top | 40 | **49.23** | `// 40 @390` |
| padding-bottom | 20 | **24.62** | `// 20 @390` |
| container padding-inline | 16 | **19.69** | `// 16 @390` |
| close button | 26 | **32** | exact |
| close icon | 9 | **11.08** | `// 9 @390` |
| close `top` | 49 | **60.31** | `// 49 @390` |

`close top` is the text-line centre: `padding-top + lineBox / 2` → desktop
`18 + 13.5 = 31.5`, mobile `40 + 9 = 49`.

## Decisions locked

| Question | Decision |
|---|---|
| SCSS home | `source/scss/components/_header-banner.scss`, `@use`'d from `layout.scss` |
| Dismiss | localStorage, keyed to the banner text so edited copy re-shows |
| Button | `variant => 'link'` + new `style => 'light'` |
| 1440 comp | Scale fluidly from 1920, absorb the 1.75px |
| × alignment | Centre on the text line |
| × colours | `rgba(255,255,255,.1)` circle, white icon |
| Learn More | Shares `banner-text`, adds underline |
| Layout | New `.drg-container-fluid` (below) |
| ✨ | Emoji inside `banner_text` — no icon |
| Background | `color(grey, 10)` |
| Placement | Inside `<header>`, above `<nav>` |

## Patterns to mirror

| Category | Source | Pattern |
|---|---|---|
| Pre-paint gate | `header.php:24` | synchronous inline `<script>` before first paint; the dismiss gate copies this exactly |
| Runtime + gate split | `layout.js` + `header.php` | inline script sets state pre-paint, `layout.js` owns behaviour |
| Null-safe runtime read | `source/js/layout.js:1` | `const { gsap } = window.DRG || {}` |
| ACF guard | `inc/functions-dynamic-content.php:30` | `function_exists()` before every ACF call |
| Image fallback opt-out | CLAUDE.md "Images" | `$fallback = false` for a full-bleed background behind light text |
| Mobile comment | CLAUDE.md "Sizing" | `@include vwUnit(padding-bottom, 100, 38.4); // 30 @375` |
| Template-part args | `template-parts/button.php:5` | `get_template_part(..., null, array(...))` with `isset()` on every read |

No existing component partial to mirror — `source/scss/components/` is empty.
`.drg-button` has markup but zero CSS.

## Files to change

| File | Action | Why |
|---|---|---|
| `source/scss/config/_variable.scss` | UPDATE | add `banner-text` to the empty `$fontSizes` |
| `source/scss/layout.scss` | UPDATE | `.drg-container-fluid` + `@use` the two new partials |
| `source/scss/components/_button.scss` | **CREATE** | `.drg-button` base + `--link` + `--light` |
| `source/scss/components/_header-banner.scss` | **CREATE** | the module |
| `template-parts/header-banner/index.php` | **CREATE** | markup + pre-paint dismiss gate |
| `template-parts/button.php` | UPDATE | accept `style => 'light'` |
| `header.php` | UPDATE | render the part inside `<header>` |
| `source/js/layout.js` | UPDATE | × click handler |

Already done: `assets/images/icon/heroicons-x-mark.svg` fetched.

---

## Task 1 — `banner-text` type token

**File**: `source/scss/config/_variable.scss`

```scss
// before
$fontSizes: ();

// after
$fontSizes: (
    banner-text: (
        desktop: (
            font-size: 18,
            font-weight: 500,
            line-height: 1.5
        ),
        mobile: (
            font-size: 14.77 // 12 @390
        )
    )
);
```

`mobile` carries **only** `font-size` on purpose — `createFontExtend()` iterates
just the keys present in the `mobile` map, so weight 500 and line-height 1.5 fall
through from `desktop`. Verified against `_mixin.scss:283`, not assumed. Urbanist
500 is already in the Google Fonts URL.

## Task 2 — `.drg-container-fluid`

**File**: `source/scss/layout.scss`, custom section

```scss
/**
 * Full-bleed container that freezes with the design.
 *
 * Bootstrap's .container-fluid is width: 100% forever, but every vwUnit() value
 * caps at $maxViewport because vwSize() emits min(Xvw, Ypx). Past 1920 the box
 * would keep growing while its padding stood still, pulling the layout apart.
 *
 * max-width is tied to $maxViewport rather than a literal 1920px so the box and
 * every vwUnit() cap move together if that constant ever changes.
 *
 * Named drg- rather than overriding Bootstrap's .container-fluid, which is
 * compiled (_init-bootstrap.scss imports `containers`) and would silently change
 * behaviour anywhere it is used.
 */
.drg-container-fluid {
    position: relative; // containing block for the banner's absolute close button
    width: 100%;
    max-width: #{$maxViewport}px;
    margin-inline: auto;
    @include vwUnit(padding-inline, 32, 19.69); // 16 @390
}
```

| Viewport | Box | Padding | Content | |
|---|---|---|---|---|
| 1440 | 1440 | 24 (`1.667vw`) | 1392 | scales with the comp |
| 1920 | 1920 | 32 (cap) | 1856 | the comp exactly |
| 2560 | **1920** centred | 32 | **1856** | pixel-identical to 1920 |

`box-sizing: border-box` is already global from Bootstrap's reboot, so padding
sits inside `max-width` and the 1920 and 2560 renders are the same pixels.

## Task 3 — `.drg-button` base styles

**File**: `source/scss/components/_button.scss` — **new**

Needed because `template-parts/button.php` has existed with **zero CSS**. Lives
in its own partial, not in the banner's, because the part is shared.

```scss
@use 'config' as *;

.drg-button {
    display: inline-flex;
    align-items: center;
    text-decoration: none;

    &--link {
        padding: 0;
        background: none;
        border: 0;
        color: inherit;
    }

    // pairs with a dark ground; applies to either shape
    &--light {
        color: color( white );
        text-decoration: underline;

        @include fullState {
            color: color( white );
        }
    }
}
```

`fullState` is the existing mixin at `_mixin.scss:106`.

## Task 4 — `style => 'light'` in the shared button part

**File**: `template-parts/button.php`

```php
// before
$button_style = ( isset( $args['style'] ) && 'secondary' === $args['style'] ) ? 'btn-secondary' : 'btn-primary';
$button_base  = $is_link_variant ? 'drg-button drg-button--link' : 'btn ' . $button_style . ' drg-button';

// after
$button_style = ( isset( $args['style'] ) && 'secondary' === $args['style'] ) ? 'btn-secondary' : 'btn-primary';

/**
 * A style that applies to BOTH shapes.
 *
 * btn-primary / btn-secondary only mean something on a filled pill, so the link
 * variant never saw `style` at all. 'light' is a palette for a dark ground, which
 * both shapes can sit on, so it is emitted as its own modifier class.
 */
$button_modifier = ( isset( $args['style'] ) && 'light' === $args['style'] ) ? ' drg-button--light' : '';

$button_base = $is_link_variant
    ? 'drg-button drg-button--link' . $button_modifier
    : 'btn ' . $button_style . ' drg-button' . $button_modifier;
```

**Cross-impact: none today.** `button.php` currently has zero callers — verified
by grep. The banner will be its first. Nothing can regress.

## Task 5 — the module SCSS

**File**: `source/scss/components/_header-banner.scss` — **new**

```scss
@use 'config' as *;

.drg-header-banner {
    position: relative;
    overflow: hidden;                 // clips the pattern image
    background-color: color( grey, 10 );

    &__bg { z-index: 0; }             // .ratio-item does the inset positioning

    &__inner {
        position: relative;           // above the pattern
        z-index: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        @include vwUnit(column-gap, 8, 7.38);        // 6 @390   ESTIMATE
        @include vwUnit(padding-top, 18, 49.23);     // 40 @390
        @include vwUnit(padding-bottom, 18, 24.62);  // 20 @390
        // keeps long copy clear of the close button, applied both sides so the
        // centring stays true
        @include vwUnit(padding-inline, 40, 40);
    }

    &__text { @include typo(banner-text); margin: 0; }
    &__link { @include typo(banner-text); }

    &__close {
        position: absolute;
        // the TEXT-line centre, not the bar centre -- padding-top + lineBox / 2.
        // Measured: at 390 the circle sits at y 48.5 while the bar centre is 38.5.
        @include vwUnit(top, 31.5, 60.31);           // 49 @390
        transform: translateY(-50%);
        @include vwUnit(right, 32, 19.69);           // 16 @390, matches container padding
        @include vwUnit(width, 32, 32);              // 26 @390
        @include vwUnit(height, 32, 32);
        @include vwUnit(font-size, 12, 11.08);       // 9 @390, icon is 1em
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        color: color( white );
        background-color: rgba( color( white ), .1 );
        border: 0;
        border-radius: 50%;
        cursor: pointer;
    }
}
```

**No `height` property.** `padding + line-height` produces 63 and 78 on their own,
and leaving it implicit means longer editor copy wraps and grows the bar instead
of clipping.

**No fade attribute.** CLAUDE.md makes the scroll fade the default and asks for
justification when absent — this is it: the banner is the topmost element, fully
above the fold. Tagging it would hide an LCP-region element behind `opacity: 0`
until GSAP boots.

## Task 6 — markup + pre-paint gate

**File**: `template-parts/header-banner/index.php` — **new**

Shape:

1. `function_exists( 'get_field' )` guard, then bail unless
   `get_field( 'enable_header_banner', 'customize_settings' )`, then bail on empty
   `banner_text`.
2. `<section class="drg-header-banner" hidden-until-checked data-drg-banner-key="…">`
   where the key is `substr( md5( $banner_text ), 0, 8 )` — editing the copy
   changes the key, so a reworded banner re-shows to everyone who dismissed the
   old one.
3. Background image via `drg_the_acf_image( $image, 'drg-header-banner__bg ratio-item', 'large', false )`
   — **`false`** is the documented opt-out: a grey placeholder behind white text
   reads worse than nothing.
4. `.drg-container-fluid > .drg-header-banner__inner` holding text, the button
   part, and the close `<button>`.
5. The button part called with the seamless clone's fields:
   ```php
   get_template_part( 'template-parts/button', null, array(
       'label'   => get_field( 'button_label',  'customize_settings' ),
       'type'    => get_field( 'button_target', 'customize_settings' ),
       'page'    => get_field( 'page_target',   'customize_settings' ),
       'url'     => get_field( 'url_target',    'customize_settings' ),
       'variant' => 'link',
       'style'   => 'light',
       'class'   => 'drg-header-banner__link',
   ) );
   ```
6. A **synchronous inline `<script>` immediately after the section** that reads
   localStorage and sets `hidden` before paint.

### Why the gate is inline rather than in layout.js

`drg_print_js()` enqueues with `$in_footer = true`, so `layout.js` runs after the
banner has already painted — a returning visitor who dismissed it would see it
flash on every page load. The same problem the `.drg-js` gate in `header.php:24`
exists to solve, solved the same way.

### Accessibility

`drg_get_icon()` injects `aria-hidden="true"`, and the × is the button's only
content — so the button carries `aria-label="Dismiss banner"` or it is unlabelled
for screen readers. `type="button"` too, so it never submits anything.

## Task 7 — render it

**File**: `header.php`

```php
// before
<header>
    <nav class="navbar navbar-expand-lg">

// after
<header>
    <?php get_template_part( 'template-parts/header-banner/index' ); ?>

    <nav class="navbar navbar-expand-lg">
```

## Task 8 — dismiss handler

**File**: `source/js/layout.js`, appended before the fade bootstrap

Click → write the key to localStorage → hide. Every storage access wrapped in
`try/catch`: Safari private mode throws on write, and a thrown error here would
kill the fade init below it.

```js
const DRG_BANNER_STORE = 'drgBannerDismissed';
// null-safe: no banner on the page is the normal case once dismissed
const banner = document.querySelector('[data-drg-banner-key]');
```

## Wiring the partials up

**File**: `source/scss/layout.scss`

```scss
@use 'components/button';
@use 'components/header-banner';
```

Both resolve `@use 'config' as *` through Sass's relative lookup from
`source/scss/`, so **no `webpack.mix.js` change is needed** — that was the reason
for choosing `source/scss/components/` over a folder under `template-parts/`.

## Validation

```bash
npm run dev                                              # must compile clean

grep -c "banner-text" assets/css/layout.css              # token emitted (--tdfs-banner-text + .fs-banner-text)
grep -o "\-\-tdfs-banner-text:[^;]*" assets/css/layout.css | head -4
grep -A4 "drg-container-fluid" assets/css/layout.css     # max-width: 1920px present
grep -c "drg-header-banner" assets/css/layout.css        # module compiled into layout, not a separate file
ls assets/css/module/ 2>/dev/null                        # expect: nothing new — this is NOT a dynamic-content module
```

Manual, in the browser:

- Bar renders at **63px** at 1920 and **78px** at 390 (DevTools box model).
- At 2560 the strip still spans the viewport while text and × hold the 1920 line.
- × sits on the text line at 390, not the bar centre.
- Dismiss, reload → no banner, **no flash**.
- Edit `banner_text` in ACF → banner returns.
- Toggle `enable_header_banner` off → no markup at all.
- Clear the image field → no grey placeholder appears.
- Tab to the × → focus visible, screen reader announces a labelled button.

## Risks

| Risk | Likelihood | Mitigation |
|---|---|---|
| `1.28` used instead of `1.230769` | Medium | every mobile value carries a `// N @390` comment; recheck against the table above |
| Dismissed banner flashes before JS | **Certain** without Task 6.6 | synchronous inline gate, mirroring `header.php:24` |
| `localStorage` throws (private mode) | Low | try/catch on read and write; failure degrades to "always shows" |
| Long copy runs under the × | Medium | symmetric `padding-inline: 40` on `__inner` |
| `.drg-container-fluid` padding is banner-specific | **Open** | 32/16 is measured from *this* comp; a page section may want a wider gutter — see below |
| Bootstrap reboot overrides `.drg-button` | Low | our partials `@use` after `_init-bootstrap.scss`, so they win at equal specificity |

## Assumptions to confirm — 2 open

1. **`.drg-container-fluid` padding is 32 @1920 / 16 @390.** Measured from the
   banner export, which is a thin utility bar. If page sections want a wider
   gutter, this becomes the banner's own padding and the primitive takes the
   theme-wide value instead.
2. **Background is full-bleed, content caps at 1920.** So past 1920 the strip
   still spans the viewport with no page background leaking at the edges.

Both are safe to build on and cheap to change — one value, one rule.

## Out of scope

- `layout.scss` — the `body { font-family }` comment now says "Bootstrap's reboot
  is commented out above", which stopped being true when Bootstrap was enabled.
  Its own note says to move the value to `$font-family-base` at
  `_init-bootstrap.scss` step 2. Real, but not this task.
- `docs/CHANGELOG.md` — no entry; project work, not a starter version bump.
- The `make-icon.js` Iconify-path fix already landed this session and wants a
  changelog line of its own.

## Acceptance

- [ ] `npm run dev` compiles clean
- [ ] Bar measures 63px @1920, 78px @390
- [ ] Renders pixel-identical at 2560 and 1920
- [ ] × centred on the text line at 390, `rgba(255,255,255,.1)` circle
- [ ] Dismiss persists across reloads with no flash; new copy re-shows it
- [ ] `enable_header_banner` off → zero markup
- [ ] Empty image field → no placeholder
- [ ] × is keyboard reachable and labelled
- [ ] `assets/css/module/` gains nothing — this compiles into `layout.css`
- [ ] No file outside the eight listed is modified
