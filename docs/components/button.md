# Button

**Markup**: `template-parts/button.php` · **Styles**: `source/scss/components/_button.scss`

```php
get_template_part( 'template-parts/button', null, array(
    'label' => 'Learn More',
    'type'  => 'page',        // or 'url'
    'page'  => $page_object,
    'url'   => '',
    'style' => 'secondary',   // omitted = primary
    'class' => 'drg-hero__button',
    'fade'  => true,
) );
```

Its own SCSS partial rather than a module's, because the part is **shared** —
any section can render it, so its CSS belongs in `layout.css` alongside markup
every page can emit.

Renders nothing without a resolvable target; `drg_resolve_link()` returns an
empty string when the field set is incomplete.

---

## Shapes

### `.btn` — the pill

Bootstrap's `buttons` module is **commented out** in `_init-bootstrap.scss`, so
`.btn` / `.btn-primary` / `.btn-secondary` carry **no** styles from the
framework. Everything in `_button.scss` is the only definition. `button.php` has
been emitting those class names since the starter with nothing behind them.

Geometry deliberately matches the navbar pill — padding `12`/`20`, radius `10` —
so a button and a nav item read as one family.

> **`border: 1px solid transparent` is reserved at rest.** A pill with no border
> until hover grows by 2px on hover and shoves every sibling sideways on each
> pointer move. This applies to every pill in the theme, not just this one.

- `.btn-primary` — filled: white label on `purple/60`.
- `.btn-secondary` — outlined: no fill, `grey/15` edge.

### `.drg-button--link` — the bare label

Takes neither Bootstrap's `.btn` nor our `.btn` overrides, so there is no pill
padding, fill or border to unset.

### `.drg-button--light` — a palette, not a shape

A palette for a **dark ground**, so it composes with either variant rather than
replacing one.

`btn-primary` / `btn-secondary` only mean anything on a filled pill, so the link
variant used to ignore `style` entirely. `light` is emitted as its own modifier
class instead of being folded into the pill branch, because either shape can sit
on a dark ground.

---

## Hover — the contrast pair

**One hover for every filled shape: `purple/75` fill with a `grey/08` label.**

White on `purple/75` measures **2.85:1**, under AA. `grey/08` on the same fill is
**6.46:1**. The fill and the label are a **pair** — changing one without the
other breaks it.

`.drg-button--light` hovers to **`purple/75` text**, not the brand `purple/60`.
Hover on a dark ground drops contrast sharply: white sits at 17.4:1 against
`grey/10`, and `purple/60` would land at **3.03:1** — under the 4.5:1 AA needs
for 18px at weight 500. (Not large text: that wants 24px, or 19px bold.) Shade 75
reads unmistakably purple at **6.10:1**.

### Who else shares this pair

- the navbar pill — `components/navbar.md`
- the main banner badge — `components/main-banner.md`

Those copy the values rather than sharing a token. **If this pair changes, they
change with it.**
