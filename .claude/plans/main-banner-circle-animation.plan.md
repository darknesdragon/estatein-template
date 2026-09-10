# main_banner — circle text rotation + badge link hover

Part A follow-up. Two changes: spin the curved badge text counter-clockwise, and
give the linked badge a hover state that matches the button pill.

## Decisions (confirmed with user)

| Question | Answer |
|---|---|
| Click target | Whole badge stays the `<a>` (current behaviour, 175px hit area) |
| Rotation speed | 20s per revolution, counter-clockwise |
| Pause on hover | Yes |
| Hover colours | Follow the button hover pair — arrow `grey/08`, inner circle fill + border `purple/75` |
| ACF conditional gap | User already fixed `circle_text_target` |

## Why counter-clockwise

The `<textPath>` is set on `M 50,88 a 38,38 0 1,1 0,-76 a 38,38 0 1,1 0,76` —
starting at 6 o'clock and sweeping clockwise. So later glyphs sit further
clockwise, i.e. to the **right** at 12 o'clock. Turning the ring counter-clockwise
pulls them leftward into the top-centre reading position, which is what a reader
following the string expects. Clockwise would run the string backwards past the eye.

## Findings before changing anything

1. **The link already exists.** `index.php:14-24` flips `$badge_tag` from `div`
   to `a` when `circle_text_clickable` is on, resolving through
   `drg_resolve_link()` behind `function_exists()`. No PHP change needed.
2. **The arrow is already a separate sibling.** `__badge-icon` is a sibling of
   `__badge-text`, so rotating the SVG leaves the arrow upright. No markup change.
3. **`__badge-text` inherits `fill: currentColor` from the badge's
   `color: white`.** Setting `color` on `__badge-icon` alone therefore recolours
   the arrow WITHOUT touching the curved text. That is what we want.
4. **The badge's own `transform` is in use** — `translate(-50%, -50%)` desktop,
   `none` under 1200. We rotate `__badge-text` instead, so nothing collides.
5. **`docs/components/` does not exist yet**, though `index.php:1` and
   `component.js:1` both point at `docs/components/main-banner.md`. Left for Part B.

## Cross-component impact

| Touched | Shared? | Impact |
|---|---|---|
| `main_banner/component.js` | No — module-scoped, webpack globs it per folder | none |
| `main_banner/_style.scss` | No — enqueued only on pages using the layout | none |
| `.drg-button` hover tokens | **Read only.** We copy the values, we do not edit `_button.scss` | If the button hover pair ever changes, this badge must move with it — noted in the code |

No PHP, no ACF, no `webpack.mix.js` entry, no build restart (both files already exist).

---

## Change 1 — `template-parts/dynamic-content/main_banner/component.js`

### 1a. Add the duration constant

**line 4** (after `DRG_COUNT_START`)

```js
// before
    const DRG_COUNT_DURATION = 1.6;
    const DRG_COUNT_START = 'top 80%';

// after
    const DRG_COUNT_DURATION = 1.6;
    const DRG_COUNT_START = 'top 80%';
    const DRG_SPIN_DURATION = 20;
```

### 1b. Run the spin before the counter's early returns

The two early returns below currently abandon the whole `forEach` when a banner
has no stats. The spin has to be set up **above** them or a stat-less banner
would render a dead ring.

**lines 30-40**

```js
// before
    roots.forEach((root) => {
        if (root.dataset.drgBannerBound) return;
        root.dataset.drgBannerBound = '1';

        const numbers = Array.from(root.querySelectorAll('[data-drg-count]'));
        if (!numbers.length) return;

        // the finished value is already rendered server-side, so leaving it
        // untouched is the correct reduced-motion outcome
        if (reduceMotion) return;

// after
    roots.forEach((root) => {
        if (root.dataset.drgBannerBound) return;
        root.dataset.drgBannerBound = '1';

        const badge = root.querySelector('.drg-main-banner__badge');
        const badgeText = root.querySelector('.drg-main-banner__badge-text');

        // set up above the counter's early returns -- a banner with no stats
        // still has a ring to turn
        if (badgeText && !reduceMotion) {
            const spin = gsap.to(badgeText, {
                rotation: -360,
                duration: DRG_SPIN_DURATION,
                ease: 'none',
                repeat: -1,
                transformOrigin: '50% 50%',
            });

            if (badge) {
                const pause = () => spin.pause();
                const resume = () => spin.resume();

                badge.addEventListener('mouseenter', pause);
                badge.addEventListener('mouseleave', resume);
                // focus/blur only ever fire on the linked variant
                badge.addEventListener('focus', pause);
                badge.addEventListener('blur', resume);
            }
        }

        const numbers = Array.from(root.querySelectorAll('[data-drg-count]'));
        if (!numbers.length) return;

        // the finished value is already rendered server-side, so leaving it
        // untouched is the correct reduced-motion outcome
        if (reduceMotion) return;
```

Null-safety: `badge` / `badgeText` are both `?`-free but guarded by explicit
truthiness before every use; `gsap` and `ScrollTrigger` are already destructured
null-safe from `window.DRG || {}` at the top of the file and the IIFE returns if
either is missing.

---

## Change 2 — `template-parts/dynamic-content/main_banner/_style.scss`

### 2a. Transition on the inner circle

**line 123-135**

```scss
// before
    &__badge-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 44.6%;
        aspect-ratio: 1;

        @include vwUnit(font-size, 40, 31.79); // 25.8 @390

        background-color: color( grey, 10 );
        border: 1px solid color( grey, 15 );
        border-radius: 50%;
    }

// after
    &__badge-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 44.6%;
        aspect-ratio: 1;

        @include vwUnit(font-size, 40, 31.79); // 25.8 @390

        background-color: color( grey, 10 );
        border: 1px solid color( grey, 15 );
        border-radius: 50%;

        @include transition(all .3s ease);
    }
```

### 2b. Hover, scoped to the linked variant

New block immediately after `__badge-icon`.

```scss
// after (new)
    // only the linked badge has an affordance, so only it gets a hover. the
    // colours are .drg-button's one hover pair -- purple 75 fill, dark glyph.
    // move them together if that pair ever changes.
    &__badge:is(a) {
        @include fullState {
            .drg-main-banner__badge-icon {
                color: color( grey, 08 );
                background-color: color( purple, 75 );
                border-color: color( purple, 75 );
            }
        }
    }
```

The curved text is unaffected: it is a sibling, and it takes `fill: currentColor`
from the badge's own `color: white`, which this rule never touches.

`grey/08` on `purple/75` measures 6.46:1 — the same AA-passing pair the button
uses. White on `purple/75` would be 2.85:1 and fail.

---

## Success criteria — verified in Chrome at 1440x900

- [x] Ring turns counter-clockwise, one revolution ≈ 20s — measured -21.77° over 1.2s = 19.8s/rev
- [x] Arrow stays upright throughout — confirmed on the hover screenshot
- [x] Hovering the badge stops the ring; leaving resumes from the same angle — 
      paused at -161.53° across 900ms, resumed to -172.33° after leave
- [x] Keyboard focus pauses, blur resumes — held -151.25° through focus, moved to -160.38° after blur
- [x] Hover fills the inner circle `purple/75` with a `grey/08` arrow, .3s ease —
      compiled to `rgb(166, 133, 250)` / `rgb(20, 20, 20)`, byte-identical to
      `.drg-button.btn-primary:hover`. Confirmed visually.
- [x] `npm run dev` builds clean, no console errors — 3.27s, console empty
- [x] Spin survives a stat-less banner — set up above the counter's early returns
- [ ] `prefers-reduced-motion: reduce` → no spin. Code-verified only; the
      chrome-devtools `emulate` tool exposes no reduced-motion flag. The guard is
      the same `reduceMotion` constant the counter already uses.
- [ ] `circle_text_clickable` off → `div`, no hover, still spins. Not exercised —
      the home page's banner has the toggle on.

## Left for Part B

`docs/components/` still does not exist, though `index.php:3` and
`component.js:1` both point at `docs/components/main-banner.md`. The prose blocks
added here (the counter-clockwise rationale, the shared hover pair) belong on the
Part B survival list.
