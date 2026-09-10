# Plan: Main Banner Corrections + Documentation Sweep

**Source**: DevTools inspection of the live page, `circle-text.png` (4×), `mobile-390.png` (4×)
**Complexity**: Large — two independent workstreams, ~20 files
**Status**: awaiting confirmation

## Summary

Two things in one pass. **A** fixes the main banner: the image never reached the
viewport edge, the badge is missing its inner circle and starts its text at the
wrong point, the columns need a 20/20 gap, mobile needs real insets, and the
stats need `+` / `k` / `M` formatting plus a scroll-triggered count-up.
**B** strips the prose comment blocks I wrote across every file this session and
moves that material into `docs/`.

The two are independent — B can be dropped or deferred without affecting A.

---

# Part A — Main banner corrections

## What DevTools found

```
.container (inner)   153..1753   w=1600
content              168..953    w=785
imageBox             993..1778   w=785    ← should reach 1920
margin-right         -175px               ← computed, and inert
```

**Root cause**: `flex: 0 0 50%` pins the image's width at 785. A negative
`margin-right` on the **last** flex item only reduces the space it claims from
the container — it cannot make the item wider. The bleed never did anything.
That technique works for a block in normal flow, not a fixed-basis flex child.

Live badge state confirmed two more:

```
pathLength 239   textLength 179   → text closes only 75% of the ring
innerCircle: false                → the second ring is simply absent
fontSize 9px
```

## Measurements

### Badge, from the 4× `circle-text.png` (700px → 175 @1×)

| | value | % of badge |
|---|---|---|
| diameter | **175** (built 168) | — |
| fill / border | grey/08 + **grey/15** | — |
| **inner circle** | **78** | 44.6% |
| inner fill / border | **grey/10 + grey/15** | — |

### Mobile, from the 4× `mobile-390.png` (1560×3800 → 390×950)

```
IMAGE   x 16..374 (w 358)   y 40..342 (h 302)
        left 16   right 16   top 40
BADGE   ~113 diameter, x 19..132, y 263..377
```

Badge is **flush with the image's left edge** (19 vs 16) and hangs **~35px below
the image's bottom** — roughly a third of its diameter, at the bottom-left
corner. Not half outside the *left* edge, which never fit inside a 15px gutter.

## Decisions locked

| | |
|---|---|
| Structure | image **out of** `.container`, absolute on desktop |
| Column gap | 40 → `padding-right: 20` on text, `padding-left: 20` on image |
| Image split | **50%** (image box at 960; photo edge at 980 after its padding) |
| Badge | 175 desktop / 113 mobile, inner circle added, path starts at 6 o'clock |
| Stats | always append `+`; `1500 → 1.5k`, `10000 → 10k`, `1000000 → 1M` |
| Counter | ScrollTrigger, on scroll-into-view |
| Emoji | **no change** — ✨ is correctly first; my "it's last" was a misread of a closed loop |

## Files to change — Part A

| File | Action | Why |
|---|---|---|
| `template-parts/dynamic-content/main_banner/index.php` | UPDATE | restructure, inner circle, formatted stats |
| `template-parts/dynamic-content/main_banner/_style.scss` | UPDATE | absolute image, gap, badge, mobile |
| `template-parts/dynamic-content/main_banner/component.js` | UPDATE | ScrollTrigger count-up |
| `inc/functions-dynamic-content.php` | UPDATE | add `drg_format_compact_number()` |

## Task A1 — restructure

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

Image first in the DOM, so mobile stacks correctly **with no `order` property** —
visual order falls out of source order. On desktop the image is absolute, so DOM
order stops mattering.

```scss
.drg-main-banner {
    position: relative;
    @include vwDesktop(min-height, 814);

    &__image-container {
        position: absolute;
        inset-block: 0;
        right: 0;
        width: 50%;
        @include vwDesktop(padding-left, 20);
    }

    &__content {
        width: 50%;
        @include vwDesktop(padding-right, 20);
        @include vwDesktop(padding-block, 80);
    }
}
```

Content stays in flow, so section height is still content-driven with `min-height`
as the floor — the property that made me reject the fully-absolute version.

## Task A2 — badge

Three fixes:

1. **Inner circle** — `__badge-icon` becomes the ring: 44.6% of the badge,
   grey/10 fill, grey/15 border, arrow centred inside.
2. **Outer border** — grey/15 on the badge itself.
3. **Path start** — currently `m -38,0` starts at 9 o'clock, so the string runs up
   the left. Change to start at **6 o'clock** so ✨ sits at the bottom and
   `Discover` climbs the left side, matching the export.
4. **font-size 9 → 12**, closing the 179/239 gap so the text rings the circle.

Mobile placement, from the measurement:

```scss
left: 0;          // flush with the image's left edge
bottom: -35;      // ~1/3 of the badge hangs below   (stored -43.08)
transform: none;  // desktop's translate(-50%, -50%) must be unset
```

## Task A3 — stat formatting

New helper in `inc/functions-dynamic-content.php`:

```php
drg_format_compact_number( 200 )      // "200"
drg_format_compact_number( 1500 )     // "1.5k"
drg_format_compact_number( 10000 )    // "10k"
drg_format_compact_number( 1000000 )  // "1M"
```

Lives in `inc/` rather than the module because it is content formatting, not
markup, and the next module that shows a count will want it.

Template renders the **final** string plus `+`, and stashes the raw target:

```php
<p class="drg-main-banner__stat-number" data-drg-count="<?php echo esc_attr( $n ); ?>">
    <?php echo esc_html( drg_format_compact_number( $n ) ); ?>+
</p>
```

Rendering the finished value server-side means **no-JS and pre-hydration both show
the right number** — the animation only ever replaces a correct value with the
same correct value.

Field values are parsed with `preg_replace('/[^0-9.]/', '', $raw)` so a `200+`
typed by an editor does not become `200++`.

## Task A4 — count-up

`component.js`, reading the shared runtime:

```js
const { gsap, ScrollTrigger } = window.DRG || {};
```

- one `ScrollTrigger` per section, `once: true`, `start: 'top 80%'`
- tweens a plain object and writes the formatted string on each update, so `k`/`M`
  formatting holds throughout the count rather than snapping at the end
- **`prefers-reduced-motion` skips the count** and leaves the server-rendered
  value, matching how `drgFadeInit()` already treats motion
- the JS formatter mirrors the PHP one; both are named in the docs so they stay
  in step

---

# Part B — Documentation sweep

Your global CLAUDE.md says *"DO NOT ADD **ANY** COMMENTS unless asked"*, and I
wrote multi-line prose blocks above nearly every rule. Those move to `docs/`.

**Kept in code**: the one-line unit annotations (`// 12 @390`). The project
CLAUDE.md explicitly requires them, they sit at end-of-line rather than forming a
block, and they are meaningless away from the number they annotate.

**Removed**: every `/** … */` prose block and standalone `//` explanation.

## New structure

```
docs/
├── ARCHITECTURE.md            existing — add links to the new files
├── CHANGELOG.md               existing
├── DESIGN-SYSTEM.md           NEW  tokens, colour, type, spacing, hover contrast
├── ADMIN.md                   NEW  colour schemes, plugin overrides, login
└── components/
    ├── button.md              NEW
    ├── header-banner.md       NEW
    ├── navbar.md              NEW
    └── main-banner.md         NEW
```

## Files to strip — Part B

| File | Comment material moves to |
|---|---|
| `source/scss/config/_variable.scss` | `DESIGN-SYSTEM.md` |
| `source/scss/config/_init-bootstrap.scss` | `DESIGN-SYSTEM.md` |
| `source/scss/layout.scss` | `DESIGN-SYSTEM.md` (container, sticky header) |
| `source/scss/components/_button.scss` | `components/button.md` |
| `source/scss/components/_header-banner.scss` | `components/header-banner.md` |
| `source/scss/components/_navbar.scss` | `components/navbar.md` |
| `template-parts/dynamic-content/main_banner/*` | `components/main-banner.md` |
| `source/scss/admin/colorScheme-drg.scss` | `ADMIN.md` |
| `source/scss/admin/colorScheme-client.scss` | `ADMIN.md` |
| `source/scss/admin/_adminConfig.scss` | `ADMIN.md` |
| `header.php` | `components/navbar.md` + `header-banner.md` |
| `template-parts/button.php` | `components/button.md` |
| `template-parts/header-banner/index.php` | `components/header-banner.md` |
| `source/js/layout.js` | `components/header-banner.md` (dismiss) |
| `inc/functions-theme-setup.php` | `components/navbar.md` (menu location, logo class) |
| `inc/functions-style-script.php` | `DESIGN-SYSTEM.md` (font loading) |
| `tools/make-icon.js` | `ARCHITECTURE.md` (the `fromIndex` bug) |

## What must survive the move

These are the non-obvious decisions currently only recorded in comments. If they
are lost, someone re-breaks them:

- **Purple 75 as a fill needs dark text** — white is 2.85:1, grey/08 is 6.46:1
- **`border: 1px solid transparent` at rest** on every pill, or hover shifts siblings
- **Mobile multiplier is `× 480/390 = 1.230769`**, not CLAUDE.md's 1.28 (a 375 comp)
- **`vwStacked()` must never nest inside a media query** — it carries its own
- **Bootstrap's `.btn-close` / `.navbar-toggler-icon` bake their colour into a
  background-image**, so `color` cannot reach them — hence the inlined heroicons
- **Heroicon glyphs do not fill their viewBox** — `bars-3-bottom-right` is 0.75 ×
  font-size, `x-mark` is 0.5625 ×
- **`--bs-offcanvas-width` is driven, not overridden**, so it does not race source order
- **Both `page_sections` defaults move together** — renderer draws markup, collector
  enqueues CSS
- **`<textPath>` ids must be unique per instance** — `href="#…"` is document-wide
- **`img-responsive` was a dead Bootstrap 3 class**, replaced by `drg-navbar__logo`
- **`make-icon.js` `fromIndex === -1` guard** — without it the Iconify path drops its own argument

## Validation

```bash
npm run dev
php -l template-parts/dynamic-content/main_banner/index.php
php -l inc/functions-dynamic-content.php
php -l header.php

# no prose blocks left in the files I wrote (expect only unit annotations)
node scratchpad/check-comments.js
```

In the browser, at 1920:

- image right edge reaches **1920**, photo edge at **980**
- badge shows its inner circle; text closes the ring; ✨ at the bottom
- stats read `200+`, `10k+`, `16+` before any JS runs
- scrolling in counts each from 0, formatting held throughout
- `prefers-reduced-motion` → no count, correct values

At 390:

- image inset 16 / 16, top 40
- badge flush left with the image, ~35px below its bottom edge
- stats 2 + 1

## Risks

| Risk | Likelihood | Mitigation |
|---|---|---|
| Stripping comments loses a hard-won decision | **High** | the survival list above is written into the docs *before* any comment is deleted |
| Desktop `translate(-50%, -50%)` leaks to mobile | **Certain** if missed | explicit `transform: none` in the stacked block |
| Absolute image collapses the section | Medium | `min-height` on the section, content still in flow |
| Count-up fights the server-rendered value | Medium | JS reads `data-drg-count`, writes through the same formatter |
| Editor types `200+` → `200++` | Medium | strip non-numerics before formatting |
| Doc sweep and code fixes land tangled in one diff | Medium | Part A committed before Part B starts |

## Out of scope

- `quick_options` — the sibling layout, still a separate pass
- Badge **rotation** — the GSAP spin; `component.js` is shaped for it
- `docs/CHANGELOG.md` entries for this session's work

## Acceptance

Part A — done and verified in an earlier pass.

- [x] `npm run dev` clean; all `php -l` pass
- [x] Image reaches the viewport edge at 1920, verified in DevTools not by eye
- [x] Badge has its inner circle; text closes the ring
- [x] Stats render formatted server-side, count up on scroll, respect reduced motion
- [x] Mobile insets 16/16/40, badge flush-left and ~35 below

Part B — done.

- [x] No prose comment blocks remain in any file written this session —
      88 blocks removed across 20 files, 659 deletions. The 23 comments still
      matching the audit are starter-template comments whose line numbers moved
      (verified against `8e55023`), the `quick_options` scaffold docblock, and
      six deliberate one-line `see docs/` pointers.
- [x] Every item in the survival list appears in `docs/`
- [x] `docs/ARCHITECTURE.md` links the new files
- [x] Rebuild clean, `php -l` clean, `node --check` clean, console empty,
      badge still spinning, stats still `200+ / 10k+ / 16+`

### Scope note

Only lines **this session added** were stripped, computed from
`git diff -U0 8e55023..HEAD` per file. The starter template's own comments were
left alone — an early attempt using `git blame <range>` matched every line and
would have deleted all 292 blocks in these files rather than the 92 that are ours.

### Still owed

`docs/CHANGELOG.md` — needs a version decision first. `style.css` carries
`Version: 0.0.1` (project) alongside `Starter Version: 0.4.0`, and the changelog
so far documents starter versions only.
