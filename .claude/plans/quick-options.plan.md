# Plan: quick_options module

**Source**: measured from `quick-options/{desktop-1920,desktop-1920-4x,laptop-1440,mobile-390}.png`
**Status**: awaiting confirmation on one global change (grid breakpoints)

Replaces the `make:module` scaffold wholesale. The stub also causes a horizontal
scrollbar on every page (`__icon-img` reaches 1071px in a 500px viewport) — that
markup goes.

---

## Confirmed decisions

| | |
|---|---|
| Shadow | `0 0 0 <spread>` — position 0 0, blur 0, colour `#191919` at 100% |
| Spread | **10** desktop / **4** mobile, through `vwUnit`. The 1440 comp's 6 is ignored |
| Desktop border | **inside** the canvas — unlike the shadow, the border must not spill |
| Desktop grid | edge-to-edge by design |
| Card | one clickable component — the whole card is the `<a>` |
| Card hover | background `#1A0C3D`, arrow turns white |
| Columns | `col-12 col-xs-6 col-xl-3` — full width below 375 |
| Container | `.drg-container-fluid`, nested inside the module's own class |

---

## ACF fields (already scaffolded)

```
options [repeater]
    icon          [image]
    text          [text]
    option_target [select]        page | url
    page_target   [post_object]   COND on option_target
    url_target    [url]           COND on option_target
```

No title/description field above the grid — the module is the grid alone, which
matches every export. The scaffold's "Learn more" button is **not** in the design
and is removed; the whole card becomes the link instead.

---

## Measurements

Design px, decoded per-pixel from the 4× exports.

| | desktop 1920 | mobile 390 |
|---|---|---|
| box inset from viewport | 0 (edge-to-edge) | 15 (`$containerPadding`) |
| box bg / border | `grey/08` + 1px `grey/15` | same |
| box radius | 0 | 12 |
| box padding | 20 | 11 |
| shadow spread | 10 | 4 |
| grid | 4 × 1 | 2 × 2 |
| gap | 20 | 10 |
| card | 455 × 212 | 163 × 144 |
| card bg / border | `grey/10` + 1px `grey/15` | same |
| card radius | 10 | 10 |
| icon ring outer / inner | pending | 47 / 35, centred |

Reconciles: `20 + 4×455 + 3×20 + 20 = 1920` · `15 + 11 + 2×163 + 10 + 11 = 374.5`

**The card is LIGHTER than its container here** (`grey/10` on `grey/08`) — the
inverse of `main_banner`, where cards sit on the darker section. The shadow
colour is that same `grey/10`, so on a `grey/08` page the shadow reads as a
*lighter* halo, not a dark one.

Still to measure during implementation: desktop ring diameter, arrow size and
offset, card padding, type sizes.

---

## Structure

```html
<section class="drg-quick-options">
    <div class="drg-quick-options__container drg-container-fluid">
        <div class="drg-quick-options__box">
            <div class="row">
                <div class="col-12 col-xs-6 col-xl-3">
                    <a class="drg-quick-options__card" href="…">
                        <span class="drg-quick-options__icon">…</span>
                        <span class="drg-quick-options__arrow">heroicons-arrow-up-right</span>
                        <span class="drg-quick-options__text">…</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
```

### Resolving the desktop/mobile split

The tricky part you flagged. The box's inset differs — 0 on desktop, 15 on
mobile — while `.drg-container-fluid` carries a fixed 15px padding and the
`$maxViewport` cap.

Answer: **let the container keep its cap, and zero only its padding at xl.**

```scss
&__container {
    // .drg-container-fluid still caps at $maxViewport -- that is what it is here for
    @media (min-width: 1200px) { padding-inline: 0; }
}
```

- **desktop** — container padding 0, so `__box` runs edge to edge; its own 20px
  padding sets the card inset. ✓
- **mobile** — container padding 15 insets `__box` by exactly the gutter, and the
  box's own 11px padding sets the card inset. ✓

One element (`__box`) carries border, radius, shadow and padding at both
breakpoints; only the container's padding switches. No duplicated box styling.

### Border inside the canvas, shadow outside it

```scss
&__box {
    border: 1px solid color( grey, 15 );
    box-shadow: 0 0 0 #{vwSize(10)} color( grey, 10 );
}
```

`box-shadow` never contributes to scroll width, so a 10px spread on a
full-width box self-clips left and right and only the top and bottom edges read
— which is the export. The **border** is inside `box-sizing: border-box`, so it
stays within 1920 with no negative margin needed.

---

## Changes

### 1. `source/scss/config/_variable.scss` — the hover purple

**line ~53**, inside the `purple` ramp

```scss
// before
    purple: (
        default: #703BF7,
        60: #703BF7,

// after
    purple: (
        default: #703BF7,
        14: #1A0C3D,
        60: #703BF7,
```

Key `14` follows the ramp convention — `#1A0C3D` is HSL lightness **14.3%**.
White on it measures **18.0:1**, so the white label and white arrow both pass AA
comfortably. `purple/60` as a card fill was rejected as too bright.

### 2. `source/scss/config/_custom-breakpoints.scss` — a 375 tier ⚠️

**This is a global grid change — see Cross-impact below.**

`col-xs-6` needs a tier at 375. Bootstrap 5 already uses `xs` as the name of its
**infix-less, zero-width** tier, so `map-merge` cannot add one — it would
overwrite `xs: 0` with `375px`, and Bootstrap's `assert-starts-at-zero` requires
the first entry to be 0.

The map has to be declared outright rather than merged:

```scss
// before
$custom-grid-breakpoints: (
    xxxl: 1680px
);
$grid-breakpoints: map-merge($grid-breakpoints, $custom-grid-breakpoints);

// after
$grid-breakpoints: (
    xxs:  0,
    xs:   375px,
    sm:   576px,
    md:   768px,
    lg:   992px,
    xl:   1200px,
    xxl:  1400px,
    xxxl: 1680px
);
```

`xxs` becomes the infix-less tier, so `.col-12` still means "from 0" and
`.col-xs-6` now means "from 375". `$container-max-widths` is untouched.

### 3. `template-parts/dynamic-content/quick_options/index.php`

Rewritten. Every row renders one card; a row with no resolvable target renders a
`div` rather than an `<a>`, the same pattern as the main banner badge.

### 4. `template-parts/dynamic-content/quick_options/_style.scss`

Rewritten from the measurements above.

---

## Cross-impact

| Change | Scope | Impact |
|---|---|---|
| `purple/14` | new key, additive | none — no existing rule reads it |
| **`$grid-breakpoints`** | **global** | **two new tiers generate `.col-xxs-*` and `.col-xs-*` across every breakpoint utility Bootstrap emits — `layout.css` grows. `.col-*`, `.col-sm-*` … keep their current meaning; nothing in the theme calls `media-breakpoint-*(xs)` (grepped: zero hits) and no template uses `col-xs-*` (grepped: zero hits), so nothing existing changes behaviour.** |
| `.drg-container-fluid` padding | scoped to `&__container` | the shared class is unchanged; only this module's instance zeroes its padding at xl |
| `quick_options` stub | replaced | fixes the site-wide horizontal scrollbar |

## Success criteria

- [ ] Desktop 1920: box edge-to-edge, top/bottom border + shadow visible, sides clipped
- [ ] Cards 455 wide with a 20 gap; first card 20 from the viewport edge
- [ ] Mobile 390: box inset 15, radius 12, 4px shadow fully inside the canvas
- [ ] 2 × 2 at 390, **1 column below 375**, 4 across at ≥1200
- [ ] Whole card is one link; hover fills `#1A0C3D` and turns the arrow white
- [ ] No horizontal scrollbar at any width — the current stub's bug is gone
- [ ] `npm run dev` clean, `php -l` clean, console empty
