# Plan: Urbanist Font + Estatein Colour Palette

**Source**: conversational (design asset `colors.png`, 2026-09-09)
**Complexity**: Small — 5 files, no logic, no PHP behaviour change
**Status**: COMPLETE — all 6 tasks applied, `npm run dev` green, validation passed

## Summary

Swap the theme font from Manrope to Urbanist and replace the inherited Petrane
example palette with the Estatein palette. All example design tokens are cleared
(`$color` **and** `$fontSizes`) so project-specific tokens get added on demand
rather than inherited speculatively — matching the CLAUDE.md rule that
`$fontSizes` holds only sizes a component actually uses.

Clearing `$fontSizes` forces two knock-on edits: the demo `.section` block in
`layout.scss` must go (it `@extend`s font placeholders that will no longer
exist), and the admin SCSS must stop reading the three Petrane colour keys.

## Decisions locked in this session

| Question | Decision |
|---|---|
| Purple hexes | Read from swatch colour, not the mislabelled text on `colors.png` |
| Old palette | Delete entirely, remap admin call sites (option **a**) |
| `purple` default | `60` → `#703BF7` |
| Shade key style | **Bare numbers** (`60:`, not `'60':`) |
| Font weights | 400 regular / 500 medium / 600 semiBold / 700 bold |
| `$fontSizes` | Emptied — project sizes added as components need them |

**Shade keys are bare on purpose.** Quoted keys would make `color(purple, 60)`
— the natural thing to type — silently return `null`, and `colorMod()` fatals on
null downstream. Bare keys are forgiving: Sass normalises `08` to `8`, so
`color(grey, 08)` and `color(grey, 8)` both resolve.

## Patterns to mirror

| Category | Source | Pattern |
|---|---|---|
| Colour map shape | `source/scss/config/_variable.scss:58` | `name: (default: hex, <shade>: hex)` — `color()` reads `default` when no shade is passed |
| Colour accessor | `source/scss/config/_function.scss:12` | `color($name, $value: default)` → `map.get(var.$color, $name, $value)` |
| Font/URL coupling | `source/scss/config/_variable.scss:23` | comment ties `$fontPrimary` to the enqueued Google URL — both move together |
| Admin token indirection | `source/scss/admin/_adminConfig.scss:38` | admin reads brand colour through `color()`, never a raw hex |

No existing pattern for a numeric shade ramp — `body` and `pure-white` each have
a single `50` alpha shade, which is the closest precedent.

## Files to change

| File | Action | Why |
|---|---|---|
| `source/scss/config/_variable.scss` | UPDATE | `$fontPrimary`, `$color`, `$fontSizes` |
| `inc/functions-style-script.php` | UPDATE | Google Fonts URL → Urbanist |
| `source/scss/layout.scss` | UPDATE | delete demo block, apply `$fontPrimary` to `body` |
| `source/scss/admin/_adminConfig.scss` | UPDATE | remap 3 deleted colour keys |
| `source/scss/admin/login.scss` | UPDATE | remap `--web-identity` |

Not touched: `$maxViewport`, `$desktopBreakpoints`, `$mobileBreakpoints`,
`$themeUnit`, every mixin and function, all PHP except the font URL.

---

## Task 1 — `$fontPrimary` → Urbanist

**File**: `source/scss/config/_variable.scss`
**Line**: 24

```scss
// before
$fontPrimary: 'Manrope', sans-serif;

// after
$fontPrimary: 'Urbanist', sans-serif;
```

Line 23's comment stays — it is the only thing tying this to the enqueue.

---

## Task 2 — Google Fonts URL → Urbanist

**File**: `inc/functions-style-script.php`
**Line**: 26

```php
// before
$google_font_url = 'https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700&display=swap';

// after
$google_font_url = 'https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700&display=swap';
```

Weights map to the four design states: 400 regular, 500 medium, 600 semiBold,
700 bold. Manrope's 300 is dropped (no light state in the design); 800 is dropped
because its only consumers were the example `$fontSizes` entries being cleared.

---

## Task 3 — replace `$color`

**File**: `source/scss/config/_variable.scss`
**Lines**: 36–75

```scss
// before
// color example taken from Drago - Petrane Figma
// https://www.figma.com/design/TAvIMAc18SOhop5Cs9he7t/Petrane?node-id=6-3&...

$color: (
    pumpkin: (
        default: #F6871F,
    ),
    prussian-blue: (
        default: #003559
    ),
    french-grey: (
        default: #C5C4C9
    ),
    dutch-white: (
        default: #FCE6C4
    ),
    anti-flash-white: (
        default: #E9ECF1
    ),
    baby-powder: (
        default: #F8F4FD
    ),
    body: (
        default: #6A6A6A,
        50: rgba(106, 106, 106, 0.5),
    ),
    pure-white: (
        default: #FFFFFF,
        50: rgba(255, 255, 255, 0.5)
    ),
    black: (
        default: #000000
    ),
    light-grey: (
        default: #FBFBFB
    ),
    error: (
        default: #FF1818
    )
);
```

```scss
// after
/**
 * Estatein palette.
 *
 * Shade keys are the HSL LIGHTNESS of the colour, which is why the ramps are
 * 08..60 for grey and 60..99 for purple rather than 100..900 -- grey 50 is
 * literally hsl(0, 0%, 50%). Reading a key tells you how light the result is.
 *
 * Keys are bare numbers, not strings. Sass normalises 08 to 8 on both the write
 * and the read, so color(grey, 08) and color(grey, 8) both resolve. Quoting them
 * would make color(purple, 60) -- the natural thing to type -- return null, and
 * colorMod() fatals on null rather than warning.
 *
 * white and grey are separate ramps, not one: white carries a faint blue tint
 * (hue 240, sat ~6%) while grey is pure neutral (sat 0).
 */
$color: (
    white: (
        default: #FFFFFF,
        90: #E4E4E7,
        95: #F1F1F3,
        97: #F7F7F8,
        99: #FCFCFD,
    ),
    black: (
        default: #000000,
    ),
    purple: (
        default: #703BF7,
        60: #703BF7,
        65: #8254F8,
        70: #946CF9,
        75: #A685FA,
        90: #DBCEFD,
        95: #EDE7FE,
        97: #F4F0FE,
        99: #FBFAFF,
    ),
    grey: (
        default: #1A1A1A,
        08: #141414,
        10: #1A1A1A,
        15: #262626,
        20: #333333,
        30: #4D4D4D,
        40: #666666,
        50: #808080,
        60: #999999,
    ),
);
```

Lines 77–87 (the commented `$variant` / `map.deep-merge` example) stay as-is —
it documents the shade-as-default technique and costs nothing.

### Open call inside this task

`grey`'s `default` is **not** in the design asset — the image gives no default
for the grey ramp. Set to `10` (`#1A1A1A`) as the template's dominant background.
Say so if it should be `15` (`#262626`, the card surface) instead.

---

## Task 4 — empty `$fontSizes`

**File**: `source/scss/config/_variable.scss`
**Lines**: 89–259 (end of file)

```scss
// before
$fontSizes: (
    display-1: (
        desktop: ( font-size: 70, font-weight: 800, line-height: 1.1 ),
        mobile:  ( font-size: 40, text-decoration: underline ),
        480:     ( text-transform: uppercase ),
    ),
    heading-1: ( ... ),
    // ... 17 keys total: display-1, heading-1, heading-2, title-1,
    // body-xl-bold, body-xl, body-l-bold, body-l, body-m-medium, body-m,
    // body-s, nav-link, button, form-title, table-header, tag-bold, tag
);
```

```scss
// after
/**
 * Type scale.
 *
 * Holds ONLY sizes a component actually uses -- add a key when a component needs
 * it, do not park speculative sizes here. Every key emits a --tdfs-<name> custom
 * property, an %fs-<name> placeholder and an .fs-<name> class, so an unused key
 * is dead weight in every stylesheet.
 *
 * Desktop values are literal design pixels from the 1920 comp. Mobile values are
 * emitted at the 480 anchor, so store design * 1.28 and record the intent:
 *     mobile: ( font-size: 25.6 )  // 20 @375
 */
$fontSizes: ();
```

### Why this cascades

Three mixins loop `$fontSizes` and all three degrade cleanly to no output on an
empty map — verified by reading, not assumed:

| Consumer | Behaviour when empty |
|---|---|
| `fontVar()` — `_mixin.scss:224` | `@each` over empty map → `:root {}` emits nothing |
| `printFontClass()` — `_mixin.scss:317` | no classes emitted |
| `createFontExtend()` — `_mixin.scss:283`, called from `_extend.scss:20` | no `%fs-*` placeholders emitted |

The one hard break is Task 5.

---

## Task 5 — `layout.scss`: delete demo block, apply the font

**File**: `source/scss/layout.scss`
**Lines**: 59–68

```scss
// before
// this code is only example, you can delete the code here
.section {
    &__title {
        @include typo(display-1);
    }

    &__desc {
        @include typo(nav-link, true, 3);
    }
}
```

```scss
// after
body {
    font-family: $fontPrimary;
}
```

**This deletion is mandatory, not cleanup.** `typo(display-1)` resolves to
`@extend %fs-display-1`; with `$fontSizes` empty that placeholder does not exist
and Sass **fatals** the build:

```
Error: The target selector was not found.
```

These are the only two `typo()` call sites in the theme
(`source/scss/layout.scss:62` and `:66`) — confirmed by grep across `source/`.

### Why `body` and why here

`$fontPrimary` is currently declared at `_variable.scss:24` and **read nowhere in
the repo** — no `font-family` rule exists in any SCSS file, and Bootstrap's
reboot (which would set `$font-family-base`) is commented out at `layout.scss:6`.
So changing the variable alone renders identically to before. A `body` rule in
the custom section is the minimum that makes the swap real, and it survives
Bootstrap being switched on later.

If `_init-bootstrap.scss` is uncommented later, move this to a
`$font-family-base: $fontPrimary;` override at its step 2 and drop the `body`
rule, so the value lives in one place.

---

## Task 6 — remap admin colour call sites

Deleting `pumpkin` / `prussian-blue` / `error` makes `color()` return `null` at
four call sites. Three of those feed `colorMod()` → `color.scale()`, which
**fatals on null** — so this task is not optional; it is the same build.

**File**: `source/scss/admin/_adminConfig.scss`
**Lines**: 38–40

```scss
// before
$primary          : color( pumpkin );
$secondary        : color( prussian-blue );
$error            : color( error );

// after
$primary          : color( purple );
$secondary        : color( grey, 20 );
// admin chrome only -- a notification red is not a brand token, so it does not
// belong in $color
$error            : #FF1818;
```

**File**: `source/scss/admin/login.scss`
**Line**: 10

```scss
// before
:root {
    --web-identity: #{color(pumpkin)};
}

// after
:root {
    --web-identity: #{color(purple)};
}
```

Downstream derivations still hold — `colorMod($primary, -30%)`,
`colorMod($button, +25%)`, `colorMod($button, +75%)` and
`str-replace(meta.inspect($highlight), '#', '')` all work on `#703BF7`
(`$checkMark` becomes `703bf7`).

**Visible effect**: the WP login screen and the Dragon admin colour scheme turn
purple. Intended under option (a), but it is a user-facing change outside the
frontend, so flagging it.

### False positive checked

`source/scss/admin/colorScheme-drg.scss:7` declares a **local** `$anti-flash-white: #f1f1f1`
and uses it 13 times. Same name as a deleted `$color` key, but it never goes
through `color()` — it is a plain local variable and needs no change.

---

## Validation

```bash
# 1. build must complete with no Sass error -- this is the real gate,
#    since every failure mode in this plan is a compile-time fatal
npm run dev

# 2. no stale reference to a deleted colour key (expect: no output)
grep -rn "pumpkin\|prussian-blue\|french-grey\|dutch-white\|baby-powder\|light-grey\|pure-white" source/ --include=*.scss

# 3. no orphan typo() / $fontSizes consumer (expect: only the _mixin.scss definition)
grep -rn "typo(" source/

# 4. Urbanist actually reaches the browser (expect: 2 hits, variable + PHP URL)
grep -rn "Urbanist" source/ inc/

# 5. font-family is emitted (expect: body{font-family:Urbanist,sans-serif})
grep -o "font-family:[^;}]*" assets/css/layout.css

# 6. admin stylesheets still built and now purple (expect: 703bf7 present)
grep -ci "703bf7" assets/css/admin/login.css assets/css/admin/colorScheme-drg.css
```

Manual check: load any page, confirm Urbanist renders and DevTools shows the
`fonts.googleapis.com` stylesheet resolving. `assets/css/layout.css` should
**shrink** — the `--tdfs-*` block and all `.fs-*` classes are gone.

## Risks

| Risk | Likelihood | Mitigation |
|---|---|---|
| Build fatals on `@extend %fs-display-1` | **Certain** if Task 5 is skipped | Tasks 4 + 5 land in the same commit |
| `colorMod(null)` fatals in admin SCSS | **Certain** if Task 6 is skipped | Tasks 3 + 6 land in the same commit |
| Login screen turns purple unexpectedly | High | Called out above; inherent to option (a) |
| Urbanist 500/600 look different from Manrope at the same weight | Medium | Urbanist runs slightly narrower; revisit sizes when real `$fontSizes` keys are added per component |
| A future component uses `color(grey)` expecting a mid grey | Low | `default` is `10` (near-black); documented in the map comment |
| `body { font-family }` gets overridden when Bootstrap is enabled | Low | Migration note in Task 5 |

## Out of scope

Carried over from the codebase read, **not** part of this change:

- `layout.scss:6` — Bootstrap SCSS still commented out, so no `.navbar` /
  `.offcanvas` / `.modal` / `.btn` styling ships
- `header.php:96` — `drg_show_debug_helper()` runs unconditionally, leaking
  `debug_backtrace()` to every visitor
- `inc/functions-style-script.php:9` — `drg_print_css()` takes no `$deps`
  despite the docs claiming it does
- `style.css` — Theme Name is still `-`
- `docs/CHANGELOG.md` — no entry; this is a project change, not a starter
  version bump

## Acceptance — verified 2026-09-09

- [x] `npm run dev` completes with no Sass error — *Compiled successfully in 1.70s*
- [x] `$color` holds only `white`, `black`, `purple`, `grey`
- [x] `$fontSizes` is `()` — file went 268 → 110 lines
- [x] `color(purple)` → `#703BF7`; `color(grey, 08)` → `#141414`
- [x] `assets/css/layout.css` emits `font-family: "Urbanist", sans-serif` on `body`
- [x] All three admin stylesheets still compile
- [x] Validation greps 2 and 3 return no unexpected hits
- [x] No file outside the five listed is modified *(see note)*

### Bare-key claim verified empirically

Compiled the accessor against the real map — every spelling resolves, which is
the whole reason the keys are bare:

```
color(purple)     → #703BF7      color(grey, 08)  → #141414
color(purple, 60) → #703BF7      color(grey, 8)   → #141414
color(grey)       → #1A1A1A      color(white, 90) → #E4E4E7
color(black)      → #000000
```

### Correction to this plan's own prediction

Task 6 said "both admin stylesheets ... derive from `#703BF7`". Wrong about
which files. Actual `703bf7` occurrences in build output:

| File | Hits | Why |
|---|---|---|
| `admin/colorScheme-client.css` | 40 | the *Website Color Scheme* — follows `$primary` |
| `admin/login.css` | 1 | `--web-identity` |
| `admin/colorScheme-drg.css` | **0** | correct — the fixed Dragon-brand scheme references `$primary`/`$secondary` **zero** times, using its own local hex vars (`$gunmetal`, `$outer-space`, …) |

So the purple-login risk is real, but the *Dragon* admin scheme is untouched by
the palette swap. Only the *Website Color Scheme* option turns purple.

---

## Follow-up: admin Dragon scheme recoloured purple

Requested after the plan above landed. Chosen over repointing the default to the
already-purple `website_color_scheme`, so the Dragon scheme itself is now purple.

### Why the greens could not map to palette shades

The green ramp went **darker** for hover and active states
(`#10af13` → `#0e9810` → `#0a690b`). The purple ramp runs 60..99 and gets
**lighter** as the key rises, so 60 (`#703BF7`) is the darkest shade available —
there is nothing below it. Hover states therefore go through `colorMod()`, which
is already the idiom at `_adminConfig.scss:44` (`colorMod($primary, -30%)`).

### Variables renamed, not just repointed

`$drg-green: #703BF7` would be a lie, so the palette-derived vars are now named
for their role. The literal-hex neutrals (`$gunmetal`, `$charcoal`,
`$quick-silver`) keep the file's colour-name convention — the split is
"derived from `$color`" vs "literal hex".

| Before | After | Role | Emitted | Uses |
|---|---|---|---|---|
| `$drg-green` | `$brand` | button bg, focus ring, borders | `#703BF7` | 44 |
| `$india-green` | `$brand-hover` | button hover / active | `#490AEB` | 18 |
| `$deep-green` | `$brand-dark` | darkest border | `#3B08BF` | 2 |
| `$myrtle-green` | `$brand-link` | link | −30% | 2 |
| `$zomp` | `$brand-link-hover` | link hover (lighter) | `#703BF7` | 3 |
| `$chinese-silver` | `$brand-disabled` | disabled button text | `#3B08BF` | 1 |

`$flame` / `$dark-pastel-red` are notification red, not brand — untouched.

### Files changed

| File | Change |
|---|---|
| `source/scss/admin/colorScheme-drg.scss` | 6 vars renamed + derived from `color(purple)`; `@use 'sass:meta'` added; `$brandCheckMark` added; SVG `fill` interpolated |
| `source/scss/admin/login.scss` | `$drgGreen`/`$drgGreenHover` → `$brand`/`$brandHover`, derived from `color(purple)` (12 uses) |
| `header.php:39` | `<meta name="theme-color">` `#10AF13` → `#703BF7` |
| `inc/functions-theme-setup.php:33` | profile-picker swatches, 4th dot → `#703bf7` |

### Checkbox SVG kept independent of `$primary`

`colorScheme-client.scss:130` interpolates `#{$checkMark}` from `_adminConfig`,
which follows `$primary`. The Dragon scheme is deliberately independent of
`$primary` — it stayed green while `$primary` was orange — so it derives its own
`$brandCheckMark` from `$brand` instead. Same value today; borrowing the other
would have coupled them silently.

Compiled result: `fill%3D%27%23703BF7%27`.

### Bug this fixed

`login.scss` still declared a local `$drgGreen: #10af13` used 12 times, which the
main plan's `--web-identity` remap did not reach. The login page was rendering
**purple background with green buttons and links** until this pass.

### Contrast checked

`#703BF7` on white is **5.74:1** — passes WCAG AA for normal text, and
white-on-purple `.button-primary` is the same ratio. The `colorMod()` hover and
link variants are darker, so they only increase it.

### Pre-existing smell left in place

`$brand-disabled` resolves to the same value as `$brand-dark`, inherited from the
green scheme where `$chinese-silver` duplicated `$deep-green`. Disabled
`.button-primary` text therefore reads as a *darker, more saturated* brand colour
rather than a muted grey — more prominent than the enabled state. Commented in
place and left alone to keep this pass a recolour; `color(grey, 50)` is the
likely fix.

### Verification

```
npm run dev ......................... Compiled successfully in 1.70s
green hexes in assets/css/ .......... none (10af13 / 0e9810 / 0a690b / 31836d / 3fa88c)
colorScheme-drg.css ................. 45x #703BF7, 17x #490aeb, 2x #3b08bf
login.css ........................... 6x #703BF7, 5x #4509dd
checkbox SVG fill ................... fill%3D%27%23703BF7%27
```

---

### Note on files changed outside this plan

`npm run dev` rewrote `package-lock.json`'s `"name"` from `fe-starter-wp` to
`estatein` — `package.json` declares no `name` field, so npm infers it from the
directory. `style.css` (Theme Name → `Estatein`) changed outside this plan's
tool calls. Neither reverted.
