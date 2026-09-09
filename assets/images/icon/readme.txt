Inline SVG icons.

Add one with:

    npm run make:icon lucide:circle-arrow-right       from Iconify
    npm run make:icon -- --from ~/export.svg drg-logo   from a local/Figma export

Browse sets at https://icon-sets.iconify.design/

The fetch happens at build time on a developer machine only -- the resulting
.svg is committed, so the site never depends on Iconify at runtime and icons
work with JavaScript disabled.

Render one with:

    get_template_part( 'template-parts/icon', null, array(
        'name'  => 'lucide-circle-arrow-right',
        'class' => 'drg-hero__icon',
    ) );

Files are inlined rather than linked, so they inherit currentColor and the
surrounding font size. `name` is the filename without the extension.
