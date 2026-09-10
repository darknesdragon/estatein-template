# Header banner

**Markup**: `template-parts/header-banner/index.php` ·
**Styles**: `source/scss/components/_header-banner.scss` ·
**Dismiss click**: `source/js/layout.js`

```php
get_template_part( 'template-parts/header-banner/index' );
```

Dismissible promo bar at the top of `<header>`, above the nav.

Content comes from the ACF options page **Customize Settings** (post_id
`customize_settings`), so it is one set of values for the whole site rather than
per page.

`banner_button` is a **seamless clone** of the Button Settings group with no name
prefix, so its sub fields resolve as plain top-level names — `button_label`,
`button_target`, `page_target`, `url_target` — and hand straight to
`template-parts/button.php` with no adapter.

Deliberately **not** a dynamic-content module: those are enqueued per page from a
flexible-content row, and this appears on every page. Its CSS compiles into
`layout.css`.

Renders nothing when ACF Pro is absent (same `function_exists()` guard as the
dynamic-content helpers) and nothing when `banner_text` is empty — the bar exists
to carry a message, and an empty strip would still occupy 63px.

---

## Sizing

Design anchors **1920×63** and **390×78**. Both reconcile from type and padding
alone:

```
desktop   18 × 1.5 + (18 × 2)   = 63
mobile    12 × 1.5 + (40 + 20)  = 78
```

Which is why **no height is declared anywhere**. Longer editor copy wraps and
grows the bar instead of being clipped.

Mobile values use the **390 multiplier (× 1.230769)**, not CLAUDE.md's 1.28 —
see [../DESIGN-SYSTEM.md](../DESIGN-SYSTEM.md#mobile-multiplier--the-390-comp).

### No fade attribute

CLAUDE.md makes the scroll fade the default and asks for a reason when it is
absent. This is the topmost element on the page, fully above the fold — tagging
it would hide an LCP-region element behind `opacity: 0` until GSAP boots.

---

## Background pattern

The pattern arrives as a **custom property set inline by `index.php`**, not an
`<img>`.

It is decoration rather than content, so it wants no alt text and no place in the
DOM. Doing it this way also deletes the stacking problem an `<img>` created: no
`z-index` on the content, and no `overflow: hidden` to clip it, because a
background cannot escape its own box.

The field returns an array, so `['url']` is already the full-size URL — no
`wp_get_attachment_image_url()` round trip. Full size is the only size that
exists anyway: `functions-theme-setup.php` disables intermediate sizes and no
`add_image_size()` is registered, so there is no `srcset` to give up.

An empty field leaves the property unset, and the CSS `none` fallback means
nothing paints. That is the behaviour we want for a full-bleed pattern behind
light text, where a placeholder would read worse than no image — and it is the
same outcome the `$fallback = false` argument used to buy, without the argument.

---

## Layout

### Full-bleed strip, capped content

The section stays full-bleed while `.drg-container-fluid` caps the **content** at
1920 — so past that width the dark strip still spans the viewport instead of
leaving page background down either side.

That is why the two classes are on **separate elements**: `.drg-container-fluid`
carries the `max-width`, and merging them would cap the strip too.

### Close-button reserve

The close button is absolutely positioned, so the row has no idea it exists.
Without a symmetric padding reserve the text slides underneath it, and the
ellipsis truncates against the bar edge rather than against the button.

Symmetric so the centred text stays **optically** centred — padding on one side
only would shift it:

```
desktop  42 = close 32 + gap 10
390      36 = close 26 + gap 10
```

Below **480** the content runs flush left, so the left reserve is dead space —
but *only* there. Between 481 and 767 the row is still centred and still needs
both sides. That override must come **after** the `vwUnit` include above it,
which emits its own 480 query; source order is what lets it win.

### Truncation

The message truncates to one line rather than wrapping. `banner_text` is a free
text field with no character limit, so the copy can be any length. No width is
calculated anywhere — the browser truncates against whatever space the row leaves
after the gap, the link and the close-button reserve, at every viewport.

> **`min-width: 0` is the load-bearing line.** A flex item defaults to
> `min-width: auto`, which refuses to shrink below its content's intrinsic width
> — with `nowrap` that is the whole sentence, so the text would overflow the bar
> and `text-overflow` would never fire.

The call to action **never** truncates; the sentence gives way instead. Without
that, the link is an equally valid shrink target and both collapse together.

### Close button position

Aligned to the **text line**, not the bar: `padding-top` + half the line box —
desktop `18 + 13.5`, mobile `40 + 9`. They differ because mobile padding is
asymmetric (40/20), which puts the text 10px below the bar's centre. Measuring
the comp confirms the circle sits at y 48.5 where the bar centre is 38.5.

An absolutely positioned child resolves against its containing block's **padding
box**, so `right: 0` would sit flush against the bar edge rather than the
container's content edge. Matching `$containerPadding` lines the button up with
the gutter without needing a wrapper element.

`aria-label` is required, not decoration: `drg_get_icon()` injects
`aria-hidden="true"`, and the icon is the button's only content — without a label
it is announced as an unnamed button. `type="button"` keeps it out of any form it
might one day sit inside.

---

## Dismissal

### The key is a hash of the copy

Storing a hash of the banner text rather than a fixed flag means **rewording the
banner produces a new key**, so a fresh message re-appears for everyone who
dismissed the previous one. Truncated, because it only has to be unique against
the single value the visitor has stored.

### Split across two places, on purpose

| Where | What |
|---|---|
| inline `<script>` beside the markup | hides an already-dismissed banner |
| `source/js/layout.js` | handles the click |

The gate **must stay synchronous and immediately after the markup**. `layout.js`
is enqueued in the footer, so hiding the banner from there would let it paint
first and flash on every page load for someone who already dismissed it — the
same problem, and the same solution, as the `.drg-js` gate in `header.php`.

### localStorage throws

Reading localStorage **throws outright** in some privacy modes rather than
returning null. Both the inline gate and the click handler wrap it in
`try`/`catch`:

- In the inline gate an exception would be thrown during parsing and stop the
  rest of the script, so it fails open — the banner shows.
- In the click handler a write failure still closes the banner for that page
  view; it just will not be remembered.
