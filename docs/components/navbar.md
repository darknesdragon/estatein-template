# Navbar

**Markup**: `header.php` · **Styles**: `source/scss/components/_navbar.scss` ·
**Menu locations**: `inc/functions-theme-setup.php`

Logo left, menu centred, right-area menu pinned right; collapses to logo +
hamburger below `xl`.

---

## Sizing

Design anchors **1920×99** and **390×68**, both reconciling from type and padding:

```
pill   18 × 1.5 + (12 × 2)   = 51
bar    51 + (24 × 2)         = 99
bar    24 (logo) + (22 × 2)  = 68    mobile, where the logo is the tallest child
```

Bar padding is **24 rather than the 20 the comp implies**, because the agreed
12/20 pill padding makes the pill 51 instead of the comp's 55 — 24 keeps the bar
itself on its designed 99.

Unlike the header banner this sits inside `.container`, **not**
`.drg-container-fluid`: the comp insets content 162px at 1920, which is the
`xxxl` container (1600) rather than a full-bleed gutter.

## `navbar-expand-xl`, not `-lg`

Logo + four items + the right-area button do not fit comfortably between 992 and
1200 — and 1200 is already the theme's stacking line everywhere else (see
`vwStacked` in `_mixin.scss`).

---

## Menu locations

| Location | Registered as |
|---|---|
| main menu | starter default |
| `main_menu_right` | **Main Navbar - Right Area** |

The right-hand slot is the Contact Us button in this design. It is a **menu
location rather than hardcoded markup**, so the client can change the label and
target without a deploy. Named *"right area"* rather than *"cta"* because the
slot can later take a phone number or language switcher without the name going
stale.

It is **last in the DOM on purpose**: above `xl` it lands at the far right of the
flattened row, and below `xl` it falls to the bottom of the offcanvas, which is
where it belongs on mobile. One order serves both.

An unassigned location renders nothing, so a fresh site simply has no button
rather than an empty `<ul>`.

---

## Icons — why Bootstrap's are replaced

> **Bootstrap's `.navbar-toggler-icon` and `.btn-close` bake their colour into a
> `background-image` SVG data URI.** `color` cannot reach them. That is why an
> earlier `.btn-close { color: white }` did nothing, and why `.btn-close-white`
> only offers a filter-based invert.

Both are replaced with inlined heroicons via `template-parts/icon.php`, which
inherit `currentColor` — so colour and hover become ordinary CSS. The toggler and
the offcanvas close share one treatment: a bare icon button inheriting
`currentColor`, hovering the same purple the nav links use.

`bars-3-bottom-right` matches the comp: its bottom bar measures 12px against the
other two at 22px, right-aligned. The `x-mark` is reused from the header banner —
one icon file, two components.

### Glyphs do not fill their viewBox

`icon.php` inlines the SVG at `width`/`height` `1em`, so these are **sized by
font-size**. But the heroicon paths do not reach the viewBox edges, so the
visible width is not the font-size:

| Icon | Path extent | Visible |
|---|---|---|
| `bars-3-bottom-right` | `M3.75..h16.5` = 16.5 + 1.5 stroke = 18 of 24 | **0.75 × font-size** |
| `x-mark` | `M6 18L18 6` = 12 + 1.5 stroke = 13.5 of 24 | **0.5625 × font-size** |
| `heroicons-arrow-up-right` | — | **≈ 0.6875 × font-size** |

The comp's hamburger is 22px wide at 390, hence `22 / 0.75 = 29.33`.

Sized with `vwUnit()` rather than `vwStacked()`: these are **hidden** above `xl`,
not merely restyled, and `vwUnit` already emits an unconditional base plus all
four anchors, so there is no 768–1199 gap for `vwStacked` to fill.

Bootstrap's own focus ring on `.navbar-toggler` is removed — the hover fill
replaces it.

### Touch target

A fixed **44px** floor, not a design dimension — WCAG 2.5.5 / the mobile HIG
minimum. Left in `px` on purpose: a target that scaled with the viewport would
drop below the floor on exactly the small screens that need it most.

---

## Logo

The logo is a **Customizer upload**, so its intrinsic size and aspect ratio are
whatever the client chose. Capping **height** with `width: auto` is what keeps a
navbar tidy — wordmark length varies between clients, height does not.

Deliberately **not** `imageRatio()`: that mixin locks one aspect ratio, which
would letterbox or squash an upload that does not match it.

> `inc/functions-theme-setup.php` used to pass `img-responsive` as the logo class
> — a **Bootstrap 3** name with no styles since the theme moved to Bootstrap 5,
> so it was a no-op. Replaced with `drg-navbar__logo`, the class the navbar
> actually styles.

---

## Menu items

WordPress emits `<li class="menu-item current-menu-item"><a>`, with no Bootstrap
classes. Styling through those **native** classes avoids the
`nav_menu_css_class` / `nav_menu_link_attributes` filters — one less thing to
keep in sync, and everything here is custom-styled anyway.

Two things wear the pill at rest: the **current page**, and the **right-area
menu**, which is a button by design.

> **`border: 1px solid transparent` reserved at rest.** A plain item has no
> border until hover; introducing one then would grow the box by 2px and shove
> every sibling sideways on each pointer move.

Hover outranks both resting states, and uses the shared filled-control pair —
**`purple/75` fill with a `grey/08` label**. White on `purple/75` is only 2.85:1,
below AA; the dark label lifts the same fill to 6.46:1. See
[button.md](button.md#hover--the-contrast-pair) — the pair moves together and
neither half should be changed alone.

### Centring

`--main` centres in the space the offcanvas row leaves after the logo.

**Known seam, accepted:** that is not quite the viewport centre — it lands ~14px
right of the comp, because the brand and the right-hand menu sit on opposite
sides of the offcanvas boundary and cannot be made equal-width flex siblings. The
simple rule needs nothing unset when the offcanvas takes over below `xl`.

An explicit `--main` modifier rather than `:not(--right)`: the negation would
have to interpolate the parent selector inside `:not()`, which reads badly and
breaks the moment another modifier is added.

---

## Offcanvas

Full-bleed on the phone, a drawer above it. **767** is the theme's own mobile
anchor, so "mobile" means the same thing here as in every `vwUnit()` call.
Bootstrap's default 400px covers 768 and up on its own — only the full-bleed case
needs stating.

> **`--bs-offcanvas-width` is driven, not overridden.** `.offcanvas-end` reads its
> width from that variable, so feeding the variable wins wherever that rule
> applies instead of racing it on source order — and it keeps working if
> Bootstrap later adds a higher-specificity width rule.

A left border on a full-bleed panel is just a stray line, so it is removed.

The panel carries **no title**: the heading was removed because the logo is still
visible in the bar behind the panel. That makes the close button the only child,
which would sit left without an explicit alignment.

Consequently the panel uses **`aria-label`, not `aria-labelledby`**. It used to
point at an `<h5>` reading "Offcanvas"; a dangling `labelledby` reference makes a
screen reader announce an unnamed dialog. A literal label has nothing to dangle.

The nav list is **stacked by default, a row once the offcanvas flattens into the
bar at `xl`** — written mobile-first rather than with `vwStacked()`, because the
`row-gap` only applies while the panel is a column, which is exactly the nested
media-query case `vwStacked()` must not be used for.
