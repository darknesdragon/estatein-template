# Design system

Tokens, sizing and the global layout primitives. Everything here lives in
`source/scss/config/` and `source/scss/layout.scss`.

See also: [ARCHITECTURE.md](ARCHITECTURE.md) · [ADMIN.md](ADMIN.md) ·
[components/](components/)

---

## Gutter — `$containerPadding`

`source/scss/config/_variable.scss`

```scss
$containerPadding: 15px;
```

Horizontal gutter shared by `.drg-container-fluid` and Bootstrap's grid.

A literal `px`, **constant at every viewport** — a deliberate exception to the
"every dimension goes through `vwUnit()`" rule. It has to stay equal to half
Bootstrap's `$grid-gutter-width`, and Bootstrap emits that at a fixed size, so a
value that scaled with the viewport would drift out of alignment with `.row` and
`.col` everywhere except the anchor width.

`_init-bootstrap.scss` reads it back as `$grid-gutter-width: $containerPadding * 2`,
so `.col` padding, `.container` padding and `.drg-container-fluid` padding all
resolve from this one number.

### Why the Bootstrap override exists at all

Bootstrap 5 defaults `$grid-gutter-width` to `1.5rem` (24px), which puts `.col`
padding at **12px** — the 30px figure people remember is Bootstrap **4**.
Doubling `$containerPadding` puts `.col` back at 15, and because Bootstrap derives
`$container-padding-x: $grid-gutter-width * .5`, `.container` lands on 15 too.

> The override **must sit before** the Bootstrap variables import. Bootstrap
> declares `$grid-gutter-width` with `!default`, so an override afterwards is
> ignored.

---

## Palette — `$color`

Shade keys are the **HSL lightness** of the colour. That is why the ramps read
`08..60` for grey and `60..99` for purple rather than `100..900` — grey 50 is
literally `hsl(0, 0%, 50%)`. Reading a key tells you how light the result is.

Keys are **bare numbers, not strings**. Sass normalises `08` to `8` on both the
write and the read, so `color(grey, 08)` and `color(grey, 8)` both resolve.
Quoting them would make `color(purple, 60)` — the natural thing to type — return
`null`, and `colorMod()` fatals on null rather than warning.

`white` and `grey` are **separate ramps, not one**: white carries a faint blue
tint (hue 240, sat ~6%) while grey is pure neutral (sat 0).

### Contrast pairs that must move together

| Pair | Ratio | Note |
|---|---|---|
| white on `purple/75` | **2.85:1** | fails AA — never use |
| `grey/08` on `purple/75` | **6.46:1** | the filled-control hover pair |
| `purple/75` on `grey/10` | **6.10:1** | the text-on-dark hover |
| `purple/60` on `grey/10` | 3.03:1 | fails AA for 18px/500 — why hover uses 75 |
| white on `grey/10` | 17.4:1 | resting text |

**Purple 75 as a fill needs dark text.** The fill and the label are a pair;
changing one without the other breaks it. This pair is used by `.drg-button`,
the navbar pill, and the main banner badge — see [components/](components/).

`purple/60` is the brand colour and is correct as a *fill* under white text; it
is `purple/75` specifically that inverts the label.

---

## Type scale — `$fontSizes`

Holds **only sizes a component actually uses**. Add a key when a component needs
it; do not park speculative sizes here. Every key emits a `--tdfs-<name>` custom
property, an `%fs-<name>` placeholder **and** an `.fs-<name>` class, so an unused
key is dead weight in every stylesheet.

Desktop values are literal design pixels from the 1920 comp. Mobile values are
emitted at the **480 anchor**, so store `design * multiplier` and record the
intent inline:

```scss
mobile: ( font-size: 25.6 )  // 20 @375
```

A mobile map may carry **font-size only** — `createFontExtend()` iterates just
the keys present, so `weight` and `line-height` fall through from desktop.

### Naming

- `body-m` — nav links, button labels, section descriptions, stat labels.
  Was `nav-link` until the main banner needed the identical size for its
  description and stat labels. **Renamed rather than duplicated**: two
  byte-identical keys are pure waste and can silently drift apart.
  Still identical to `banner-text` on desktop; the two diverge on mobile
  (16 vs 12), which is why they stay separate keys.
- `stats-number` — named for the thing it sizes, not a heading level. It never
  renders as a heading, so calling it `heading-2` would mislead every later reader.

---

## Mobile multiplier — the 390 comp

**`× 480/390 = 1.230769`, not the `1.28` in CLAUDE.md.**

CLAUDE.md's 1.28 assumes a **375** comp. This project's mobile exports are
**390** wide, so 1.28 renders roughly 5% small. Every mobile value in this
project is stored as `design × 1.230769` and carries the raw design value as an
end-of-line annotation:

```scss
@include vwUnit(padding-block, 20, 19.69);  // 16 @390
```

Those `// N @390` annotations are the one comment form kept in code. They are
meaningless away from the number they annotate, and the project CLAUDE.md
requires them.

## `vwUnit()` takes unitless numbers

`@include vwUnit(width, 100%)` breaks — the mixin does arithmetic on the value to
emit `min(Xvw, Ypx)`, and a percentage has no pixel equivalent to divide by an
anchor. Pass raw design pixels as bare numbers. For a genuine percentage, write
the declaration directly.

## `vwStacked()`

**Carries its own media query, so it must never be nested inside one.**

`vwMobile()` starts at 767, but Bootstrap's `xl` columns stack at 1200 — a
property written with `vwMobile` inside a `max-width: 1199.98px` rule gives
768–1199 the stacked *structure* with none of its *values*. `vwStacked()` adds
the 1199 anchor and its own query to close that gap.

Where a value only applies *while* a layout is stacked (a `row-gap` that
disappears once a column flattens into a row), write it mobile-first instead —
that is exactly the nested case `vwStacked()` cannot serve.

---

## Body defaults

`source/scss/config/_init-bootstrap.scss`

Dark ground, light text and Urbanist are set as **Bootstrap variables**
(`$body-bg`, `$body-color`, `$font-family-base`) rather than as a `body { }` rule.

Bootstrap re-exports them as `--bs-body-color` / `--bs-body-bg`, and its
**components build on those**. The offcanvas is the one that bites: it declares
`--bs-offcanvas-color: var(--bs-body-color)`, and that offcanvas is the mobile
nav in `header.php` — a plain `body` rule would leave the mobile menu as dark
text on white while the rest of the site is dark.

It also keeps each value in **one** place: Reboot styles `body` from the same
variable, so no separate rule is needed and none can drift.

---

## `.drg-container-fluid`

`source/scss/layout.scss`

Full-bleed container that **freezes with the design**.

Bootstrap's `.container-fluid` is `width: 100%` forever, but every `vwUnit()`
value caps at `$maxViewport` because `vwSize()` emits `min(Xvw, Ypx)`. Past 1920
the box would keep widening while its padding stood still, pulling the layout
apart. Capping the box on the same line the padding caps keeps a 2560 screen
pixel-identical to a 1920 one.

`max-width` is tied to `$maxViewport` rather than a literal `1920px`, so the box
and every `vwUnit()` cap move together if that constant changes.

Named `drg-` rather than overriding Bootstrap's `.container-fluid`, which **is**
compiled (`_init-bootstrap.scss` imports `containers`) — redefining it would
silently change behaviour anywhere Bootstrap or future markup relies on it.

---

## `.drg-header` — sticky site header

Banner and navbar together.

**Sticky, not fixed**, so the header keeps its space in flow and the page below
never jumps. It also means dismissing the banner simply shrinks the header, with
no offset to recalculate anywhere.

`z-index` is Bootstrap's own `$zindex-sticky` (1020) on purpose: an arbitrary
number risks landing above the offcanvas backdrop at 1040, which would leave the
header clickable through the scrim while the menu is open.

Shared components (`_button.scss`, `_navbar.scss`, `_header-banner.scss`) are
imported into `layout.scss` because their markup lives in `template-parts/` —
any page can emit it, so the CSS has to be global.

---

## Font loading

`inc/functions-style-script.php`

Urbanist is requested at four weights — **400 regular, 500 medium, 600 semiBold,
700 bold** — mapping to the four states the design uses.

**Keep the weight list in sync with `$fontPrimary` in
`source/scss/config/_variable.scss`.** They are a connected pair: a weight used
in SCSS but absent from the request renders as a synthesised faux-bold.
