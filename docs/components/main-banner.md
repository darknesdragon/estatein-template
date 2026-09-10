# Main banner

**Layout**: `main_banner` (ACF flexible content, `page_sections`)
**Files**: `template-parts/dynamic-content/main_banner/{index.php,_style.scss,component.js}`
**Helper**: `drg_format_compact_number()` in `inc/functions-dynamic-content.php`

Split hero: copy left, full-bleed photo right, a circular badge straddling the
photo's left edge, and three counting stat cards.

## Fields

| Field | Type | Note |
|---|---|---|
| `main_image` | image | |
| `circled_text` | text | the string that rings the badge |
| `circle_text_clickable` | true/false | turns the badge into a link |
| `circle_text_target` | select | `page` / `url`; shown only when clickable is on |
| `page_target` / `url_target` | post_object / url | conditional on the select |
| `title` / `description` | textarea | rendered through `drg_the_multiline()` |
| `banner_buttons` | group | two seamless Button Settings clones |
| `information_highlight` | repeater | `number` + `title` |

The heading renders as `<h1>` only when `get_row_index() === 1`, `<h2>` otherwise
— so a banner used lower down a page does not fight the page's real H1.

---

## Structure

```html
<section class="drg-main-banner">
    <div class="drg-main-banner__image-container">   <!-- absolute on desktop -->
        <img class="drg-main-banner__image">
        badge
    </div>
    <div class="drg-main-banner__inner container">   <!-- content only -->
        …
    </div>
</section>
```

Image **first in the DOM**, so mobile stacks correctly with no `order` property —
visual order falls out of source order. On desktop the image is absolute, so DOM
order stops mattering.

Content stays **in flow**, so section height is still content-driven with
`min-height` as the floor. That is why the image alone is absolute rather than
the whole layout.

### Why not a flex bleed

The original attempt used `flex: 0 0 50%` on the image with a negative
`margin-right` to reach the viewport edge. It never did anything:

```
.container (inner)   153..1753   w=1600
imageBox             993..1778   w=785    ← should reach 1920
margin-right         -175px               ← computed, and inert
```

A negative `margin-right` on the **last** flex item only reduces the space it
claims from the container — it cannot make the item wider. That technique works
for a block in normal flow, not a fixed-basis flex child.

---

## Badge

175 desktop / 113 at 390, measured off the 4× `circle-text.png` export.

| | value | % of badge |
|---|---|---|
| diameter | 175 | — |
| fill / border | `grey/08` + `grey/15` | — |
| inner circle | 78 | **44.6%** |
| inner fill / border | `grey/10` + `grey/15` | — |

### The curved text

The `<textPath>` runs on a path starting at **6 o'clock** and sweeping clockwise,
so the string opens at the bottom and `Discover` climbs the left side.

> **`<textPath href="#…">` ids are document-wide.** Two banners on one page would
> both point at the first path. The id is derived per instance from
> `get_row_index()`.

`__badge-text` is sized in **user units, not CSS px**: the SVG carries
`viewBox="0 0 100 100"` and scales with the badge, so a fixed number keeps the
type proportional at every breakpoint. A `vwUnit()` value would not scale with
the viewBox and the ring would drift.

### Rotation

`component.js` spins `__badge-text` **counter-clockwise, 20s per revolution**,
`ease: 'none'`, infinite.

Counter-clockwise because the path is set clockwise: later glyphs sit to the
**right** at 12 o'clock, so turning left pulls the string through the reading
position in order. Clockwise would run it backwards past the eye.

The arrow is a **sibling** of the rotating SVG, not a child, so it stays upright.

The spin is set up **above the counter's early returns** — a banner with no stats
still has a ring to turn.

Pauses on `mouseenter` and `focus`, resumes on `mouseleave` and `blur`.
`focus`/`blur` only ever fire on the linked variant; a `div` badge takes no focus.

### The link

`circle_text_clickable` flips the badge element from `<div>` to `<a>`, resolved
through `drg_resolve_link()` behind a `function_exists()` guard. The **whole
badge** is the hit target, not just the arrow.

Hover fills the inner circle `purple/75` with a `grey/08` arrow — the same pair
`.drg-button` uses, at 6.46:1. See
[button.md](button.md#hover--the-contrast-pair). **If that pair changes, this
changes with it.**

Scoped `&__badge:is(a)`, so a non-clickable badge gets no phantom affordance.

The curved text is untouched by hover: it is a sibling taking
`fill: currentColor` from the badge's own `color`, which the hover rule never
sets.

### Mobile placement

Measured off the 4× `mobile-390.png` export:

```
IMAGE   x 16..374 (w 358)   y 40..342 (h 302)
BADGE   ~113 diameter, x 19..132, y 263..377
```

Badge is **flush with the image's left edge** and hangs **~35px below** it —
roughly a third of its diameter, at the bottom-left corner. Not half outside the
left edge, which never fit inside a 15px gutter.

Two things that must not be lost:

- **`transform: none` in the stacked block.** Desktop's
  `translate(-50%, -50%)` would otherwise leak down.
- **`__image-container` stays `position: relative`, never `static`.** It is the
  badge's containing block; going static reparents the badge to the section. No
  `overflow: hidden` either — the badge is meant to hang past the bottom edge.

`bottom` uses `vwStacked()`, which **carries its own media query** and so sits
outside the `max-width: 1199.98px` block — and it covers 768–1199, which
`vwMobile` alone would leave without a value.

---

## Stats

Rendered **server-side in final form**, with the raw target stashed for JS:

```php
<p class="drg-main-banner__stat-number" data-drg-count="<?php echo esc_attr( $n ); ?>">
    <?php echo esc_html( drg_format_compact_number( $n ) ); ?>+
</p>
```

So no-JS and pre-hydration both show the right number — the animation only ever
replaces a correct value with the same correct value.

`drg_format_compact_number()`:

```
200      -> "200"
1500     -> "1.5k"
10000    -> "10k"
1000000  -> "1M"
```

It lives in `inc/` rather than the module because it is content formatting, not
markup, and the next module that shows a count will want it. It strips
non-numerics first, so a value an editor typed as `200+` formats as `200` rather
than becoming `200++` or failing the cast.

**The JS formatter in `component.js` mirrors the PHP one.** They are a connected
pair — change both or the count-up will disagree with its own final frame.

### The count-up

GSAP tween on a plain object, writing the formatted string on each update, so
`k`/`M` formatting holds throughout the count rather than snapping at the end.

> **`ScrollTrigger`'s `onEnter` fires on a scroll *transition*.** An element
> already in view at load never triggers it — and this banner is always above the
> fold. `ScrollTrigger.isInViewport()` covers that case; the trigger covers
> everything below it.

The count zeroes **inside** the tween, never up front, so a path that never runs
leaves the server-rendered value rather than a stuck `0`.

`prefers-reduced-motion: reduce` skips both the count and the spin, leaving the
server-rendered value — matching how `drgFadeInit()` already treats motion.
