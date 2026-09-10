# Admin

WordPress admin styling: the two colour schemes, the shared config, and the
third-party overrides. Source lives in `source/scss/admin/`.

See also: [DESIGN-SYSTEM.md](DESIGN-SYSTEM.md) · [ARCHITECTURE.md](ARCHITECTURE.md)

---

## `_adminConfig.scss` — shared tokens

Admin-only colours stay out of `$color`. A notification red is admin chrome, not
a brand token, so it does not belong in the project palette.

`$link-color` is written `colorMod($primary, -30%)` rather than picking a palette
shade — see the ramp-direction note below for why.

---

## `colorScheme-drg.scss` — the Dragon scheme

### Brand colours are derived, not literal

The neutrals at the top of the file keep the colour-name convention because they
are literal hex. The brand variables below them are named for their **role**, not
their hue: they follow `$color`, so a hue name would go stale the moment the
palette changes.

### Why hover/active use `colorMod()` and not a palette shade

The purple ramp runs `60..99` — **lighter as the key rises**. Hover and active
states need to go **darker** than the base, and the ramp has nothing below 60. So
those states come through `colorMod()` instead. Same reason `$link-color` in
`_adminConfig.scss` is written `colorMod($primary, -30%)`.

### `$brand` as bare hex

The checkbox SVG data-URI cannot take a colour object, and `#` has to be
percent-encoded inside a URI — so the hash is stripped once into a bare-hex
variable and re-added as `%23` at the call site.

Derived from `$brand`, **not** from `_adminConfig`'s `$checkMark`. That one
follows `$primary`; this scheme is deliberately independent of it (it stayed
green while `$primary` was orange), and borrowing it would couple the two
silently.

---

## Disabled primary button

Applies to **both** schemes (`colorScheme-drg.scss` and `colorScheme-client.scss`).

Keeps the brand background and fades the **label**, which is what WordPress core
does — `rgba` white at 40% over the primary colour.

**The defect this fixed:** the previous rule painted a dark brand colour on a
brand background at **1.28:1**, effectively unreadable. The Customizer ships its
Save button disabled, so that was the first thing you hit there. The client
scheme had the same defect from the other direction — `$button-disabled` is
`colorMod($button, +75%)`, so it painted a light brand tint on a light brand tint.

Fading the text rather than greying the whole control also keeps a disabled
primary distinguishable from a disabled secondary.

---

## Third-party overrides

Both of these are written with a **`body` prefix rather than `!important`**, so
they win on specificity rather than on load order. The colour-scheme stylesheet
is not guaranteed to print after the concatenated core styles from
`load-styles.php`, so load order is not something to rely on.

### Toolbar Publish Button plugin

The plugin paints its button from its own setting (**Settings → Toolbar Publish
Button**, default `#0073AA`), injected at runtime as a `<style>` tag from
`js/tpb.js` — so it does not follow the scheme on its own.

Its selector scores `(0,2,3,1)`. Prefixing `body` makes ours `(0,2,3,2)`, which
wins on specificity alone. No `!important` is needed, and none would work
reliably anyway against a tag injected after this stylesheet.

**Only the background is overridden.** The plugin already sets the label to
`#fff` and handles hover with `filter: brightness(1.1)`, both of which read
correctly on the brand colour.

> **Trade-off:** this beats the plugin's own colour picker, so changing that
> setting will appear to do nothing while the rule is here.

### Admin bar site icon

Core paints a light grey plate behind the favicon
(`wp-includes/css/admin-bar.css` — *"matching my-account (user avatar) node's
background"*). Deliberate on their part, but it frames a transparent or dark
favicon with a grey slab against the dark bar.

The `body` prefix takes ours to `(0,2,2,1)` against core's `(0,2,2,0)`.

**Background only.** Core's mobile rule repositions the icon without re-setting
`background`, so this one declaration covers every breakpoint.
