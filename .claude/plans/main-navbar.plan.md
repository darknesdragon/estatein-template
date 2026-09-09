# Plan: Main Navbar

**Source**: design exports `20260910 - module screenshot/main navbar/` (1920, 1440, 390)
**Complexity**: Medium — 5 files (1 new), one new menu location, restructured `header.php`
**Status**: awaiting confirmation

## Summary

Logo left, nav centred, a **Contact Us** button pinned right, collapsing to logo +
hamburger with a Bootstrap offcanvas below `xl`. The right-hand button comes from
a **new menu location** rather than hardcoded markup, so the client can change it.
The whole `<header>` — banner included — becomes sticky.

## Measurements — decoded from the exports

| | @1920 | @1440 | @390 |
|---|---|---|---|
| bar height | **99** | 77 | **68** |
| bar background | `#191919` = **grey/10** | same | same |
| pill fill | `#141414` = **grey/08** | same | — |
| pill border | `#262626` = **grey/15** | — | — |
| logo | x 162–321 (**160 × 41**) | — | 94w → **24h** |
| Home pill | x 757–852, y 22–76 | 74w | — |
| Contact Us | x 1622–1757, y 20–78 | 107w | — |
| hamburger | — | — | x 349–370 (22w) |

**Content is inset 162px each side at 1920 → 1596 wide**, which is Bootstrap's
`xxxl` container (1600, from `_custom-breakpoints.scss`). `header.php` already
wraps the nav in `.container`, so the navbar keeps `.container` — unlike the
banner, which is full-bleed. That split is what the comps show.

Pills are **darker** than the bar (grey/08 on grey/10) — the same tone as the page
body, not a lighter highlight.

The 1440 comp is again ~3.7% off proportional (77 vs 74.25 scaled) and its gutter
is 80 where Bootstrap's `xxl` gives 60. Same inconsistency as the banner; same
decision — scale from 1920 and absorb it.

### Height reconciliation

```
line box     18 × 1.5                     = 27
pill         27 + (12 × 2)                = 51
bar @1920    51 + (24 × 2)                = 99   ✓ matches the export
bar @390     24 (logo) + (22 × 2)         = 68   ✓ matches the export
```

### Mobile conversion (`× 480/390 = 1.230769`)

| Property | @390 | stored |
|---|---|---|
| nav-link font-size | 16 | **19.69** |
| bar padding-block | 22 | **27.08** |
| logo height | 24 | **29.54** |

## Decisions locked

| Question | Decision |
|---|---|
| Menu location | slug `main_menu_right`, label **Main Navbar - Right Area** |
| Home pill | **active** state (`.current-menu-item`) |
| Hover | purple 60 background, white text — pill geometry, on nav items *and* Contact Us |
| Active pill on mobile | yes, keeps the pill in the offcanvas |
| Contact Us on mobile | **plain**, like the other items — so it can't be mistaken for the active state |
| Expand breakpoint | `navbar-expand-xl` (≥1200) |
| Sticky | the whole `<header>`, banner included |
| Offcanvas | `offcanvas-end`, close button only, no logo |
| Offcanvas width | full-bleed ≤767, Bootstrap's default 400px from 768 |
| Pill padding | **12 / 20**, gap **10**, radius **10** |

### Two consequences of the 12/20 padding

1. **Pill height is 51, not the comp's 55** (and Contact Us 51, not 59). A
   deliberate 4px deviation.
2. **Bar padding-block goes to 24**, not the 20 the comp implies, so the bar still
   measures 99. At 20 it would come out 91.

Both are recorded rather than silently absorbed — say if you'd rather have exact
comp fidelity and revert to 14/25 padding.

## Patterns to mirror

| Category | Source | Pattern |
|---|---|---|
| Menu registration | `inc/functions-theme-setup.php:230` | `register_nav_menu('main_menu', 'Main Navbar')` — new location sits beside it |
| Component partial | `source/scss/components/_header-banner.scss:1` | `@use '../_config.scss' as *;` then a BEM block |
| Partial wiring | `source/scss/layout.scss:8` | `@use 'components/header-banner';` |
| Layout primitive | `source/scss/layout.scss` | `.drg-container-fluid` — structural rules live in layout, components in `components/` |
| Mobile comment | `_header-banner.scss:51` | `@include vwUnit(padding-top, 18, 49.23); // 40 @390` |
| Every element classed | `template-parts/header-banner/index.php` | `__container` exists purely for inspector debugging |
| Existing nav markup | `header.php:49-82` | `.navbar` > `.container` > brand + toggler + offcanvas |

## Files to change

| File | Action | Why |
|---|---|---|
| `inc/functions-theme-setup.php` | UPDATE | register `main_menu_right` |
| `source/scss/config/_variable.scss` | UPDATE | add `nav-link` to `$fontSizes` |
| `source/scss/components/_navbar.scss` | **CREATE** | the component |
| `source/scss/layout.scss` | UPDATE | `@use` the partial + sticky `.drg-header` |
| `header.php` | UPDATE | expand-xl, second menu, offcanvas cleanup, `.drg-header` |

---

## Task 1 — register the menu location

**File**: `inc/functions-theme-setup.php:230`

```php
// before
register_nav_menu('main_menu', 'Main Navbar');
register_nav_menu('footer', 'Footer Navbar');

// after
register_nav_menu('main_menu', 'Main Navbar');
// the CTA lives in a menu rather than hardcoded markup so the client can change
// it -- and "right area" rather than "cta" because the slot can later take a
// phone number or language switcher without the name going stale
register_nav_menu('main_menu_right', 'Main Navbar - Right Area');
register_nav_menu('footer', 'Footer Navbar');
```

## Task 2 — `nav-link` type token

**File**: `source/scss/config/_variable.scss`

```scss
nav-link: (
    desktop: (
        font-size: 18,
        font-weight: 500,
        line-height: 1.5
    ),
    mobile: (
        font-size: 19.69 // 16 @390
    )
),
```

Identical to `banner-text` on desktop; they diverge only on mobile (16 vs 12), so
both keys are needed. If those ever converge, they collapse into one.

## Task 3 — sticky header

**File**: `source/scss/layout.scss`, beside `.drg-container-fluid`

```scss
.drg-header {
    position: sticky;
    top: 0;
    z-index: 1020; // Bootstrap's $zindex-sticky -- below the offcanvas backdrop (1040)
}
```

`z-index` is Bootstrap's own sticky level on purpose: picking an arbitrary number
risks sitting above the offcanvas backdrop, which would leave the header
interactive behind the scrim.

Sticky rather than fixed, so the header keeps its space in flow and the page
below does not jump. It also means the header's height changes when the banner is
dismissed, which sticky handles for free.

**Cost to note:** banner 63 + navbar 99 = **162px of permanent sticky chrome** at
1920.

## Task 4 — the component

**File**: `source/scss/components/_navbar.scss` — **new**

```scss
.drg-navbar {
    background-color: color( grey, 10 );
    @include vwUnit(padding-block, 24, 27.08);   // 22 @390

    &__logo {
        @include vwUnit(height, 41, 29.54);      // 24 @390
        width: auto;
        max-width: 100%;
    }

    &__menu {
        @include vwUnit(column-gap, 10, 10);
    }

    &__link {
        @include typo(nav-link);
        @include vwUnit(padding-block, 12, 12);
        @include vwUnit(padding-inline, 20, 20);
        border: 1px solid transparent;           // reserved so hover adds no shift
        border-radius: 10px;
        color: color( white );
        text-decoration: none;
    }

    // active page, and the always-on Contact Us button
    &__link--active,
    &__link--button {
        background-color: color( grey, 08 );
        border-color: color( grey, 15 );
    }

    // hover wins over both
    &__link {
        @include fullState {
            color: color( white );
            background-color: color( purple );
            border-color: color( purple );
        }
    }
}
```

**`border: 1px solid transparent` at rest is load-bearing.** A plain nav item has
no border until hover; adding one then would grow the box by 2px and shove every
sibling sideways. Reserving it transparent keeps the geometry fixed.

## Task 5 — `header.php`

Three changes to the existing nav, plus the second menu.

```php
// before
<header>
    <nav class="navbar navbar-expand-lg">

// after
<header class="drg-header">
    <nav class="navbar navbar-expand-xl drg-navbar">
```

The offcanvas header loses its placeholder heading:

```php
// before
<div class="offcanvas-header">
    <h5 class="offcanvas-title" id="offcanvasNavbarLabel">Offcanvas</h5>
    <button type="button" class="btn-close" ...></button>
</div>

// after -- close button only; the logo is still visible in the bar behind
<div class="offcanvas-header drg-navbar__offcanvas-header">
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
</div>
```

**`aria-labelledby="offcanvasNavbarLabel"` must come off the offcanvas** at the
same time — it would otherwise point at an element that no longer exists, and a
screen reader announces the dialog as unnamed. Replaced with a literal
`aria-label`.

The body gains the second menu, after the first:

```php
<div class="offcanvas-body drg-navbar__offcanvas-body">
    <ul class="navbar-nav drg-navbar__menu">
        <?php wp_nav_menu( array( 'theme_location' => 'main_menu', 'depth' => 1,
            'container' => '', 'items_wrap' => '%3$s' ) ); ?>
    </ul>

    <ul class="navbar-nav drg-navbar__menu drg-navbar__menu--right">
        <?php wp_nav_menu( array( 'theme_location' => 'main_menu_right', 'depth' => 1,
            'container' => '', 'items_wrap' => '%3$s' ) ); ?>
    </ul>
</div>
```

DOM order puts the right-area menu last, which is exactly what mobile wants —
it falls to the bottom of the offcanvas with no extra work.

### Getting Bootstrap classes onto WP's markup

`wp_nav_menu` emits `<li class="menu-item current-menu-item"><a>` — no
`.nav-link`. Two options:

- **A (recommended)**: style through our own selectors —
  `.drg-navbar__menu > li > a` and `.current-menu-item > a` for the active pill.
  No filters, nothing to keep in sync.
- **B**: add `nav_menu_css_class` / `nav_menu_link_attributes` filters to inject
  `.nav-item` / `.nav-link`.

A is fewer moving parts and we are custom-styling everything anyway. `.navbar-nav`
stays on the `<ul>` because Bootstrap's column→row switch at the expand
breakpoint is worth keeping.

### Centring — a known ~14px seam

Above `xl`, Bootstrap turns the offcanvas into a flex row, so `.offcanvas-body`
holds `[main menu][right menu]` and starts *after* the logo. Giving the main menu
`margin-inline: auto` centres it in that remaining space, not in the viewport.

With logo 160 and Contact Us 136, that lands the menu at roughly **x 771–1171**
against the comp's **757–1157** — about 14px right.

Exact viewport-centring needs the brand and the right menu to be equal-width flex
siblings, which they cannot be across the offcanvas boundary; the alternative is
absolutely positioning the centre menu and unsetting it inside the offcanvas.
**Recommend accepting the 14px** — the simpler rule is responsive and has nothing
to unset.

## Validation

```bash
npm run dev

rtk grep -o "\-\-tdfs-nav-link:[^;]*" assets/css/layout.css     # 4 anchors
rtk grep -c "drg-navbar" assets/css/layout.css
rtk grep -A3 "^\.drg-header" assets/css/layout.css              # sticky + z-index 1020
php -l header.php
```

In the browser:

- Appearance → Menus shows **Main Navbar - Right Area** as an assignable location.
- Bar measures **99px** at 1920, **68px** at 390.
- Header stays pinned on scroll; dismissing the banner shrinks it with no jump.
- Active page shows the pill; hovering any item gives purple + white with **no
  horizontal shift** of siblings.
- Below 1200 the nav collapses; the offcanvas is full-bleed under 768 and 400px
  above; Contact Us is last and plain; the active item keeps its pill.
- Tab through: toggler → items → Contact Us → close, all with visible focus.

## Risks

| Risk | Likelihood | Mitigation |
|---|---|---|
| Hover border shifts siblings | **Certain** without the fix | `border: 1px solid transparent` at rest |
| Stale `aria-labelledby` after removing the heading | **Certain** if missed | swap to `aria-label` in the same edit |
| Menu not centred to the viewport | Certain, ~14px | documented and accepted; exact option noted |
| Sticky header covers in-page anchor targets | Medium | `scroll-margin-top` on anchor targets, later, when anchors exist |
| Right-area location unassigned on a fresh site | Medium | `wp_nav_menu` renders nothing for an unassigned location — degrades to no button |
| 162px of sticky chrome on small laptops | Medium | flagged; only-navbar-sticky is a one-line change if it bothers you |
| Logo uploaded at an odd ratio | Low | capped by height with `width: auto`, so any ratio stays 41px tall |

## Out of scope

- `inc/functions-theme-setup.php:241` replaces `custom-logo` with
  **`img-responsive`** — a Bootstrap **3** class that has no styles in BS5, so it
  has always been a no-op. `img-fluid` is the modern equivalent. Real, but not
  this task; the height cap in Task 4 makes it moot for the navbar anyway.
- `docs/CHANGELOG.md` — still owes entries for the `make-icon.js` Iconify fix and
  the banner.

## Acceptance

- [ ] `npm run dev` compiles clean; `php -l header.php` passes
- [ ] **Main Navbar - Right Area** assignable in Appearance → Menus
- [ ] Bar 99px @1920, 68px @390; logo capped to 41 / 24
- [ ] Pill 51h, radius 10, grey/08 fill + grey/15 border
- [ ] Hover purple with no layout shift; active pill on desktop and mobile
- [ ] Collapses at 1200; offcanvas full-bleed <768, 400px ≥768
- [ ] Contact Us last and plain in the offcanvas
- [ ] Header sticky at `z-index: 1020`, below the offcanvas backdrop
- [ ] No file outside the five listed is modified
