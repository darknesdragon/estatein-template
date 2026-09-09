const mix = require('laravel-mix');

mix.sass('source/scss/layout.scss', 'assets/css/')
    .sourceMaps(true, 'source-map');

mix.js('source/js/layout.js', 'assets/js/')
    .sourceMaps(true, 'source-map');

// Page-specific JS — add one line per page file (see docs/ARCHITECTURE.md)
mix.js('source/js/pages/home.js', 'assets/js/page/')
    .sourceMaps(true, 'source-map');

// Wordpress Custom Admin Login CSS
mix.sass('source/scss/admin/login.scss', 'assets/css/admin')
    .sass('source/scss/admin/colorScheme-drg.scss', 'assets/css/admin')
    .sass('source/scss/admin/colorScheme-client.scss', 'assets/css/admin')
    .sourceMaps(true, 'source-map');


/**
 * Dynamic content modules -- auto-compiled.
 *
 * Any folder under template-parts/dynamic-content/ containing _style.scss or
 * component.js is built without an entry here. Add a module folder, restart the
 * build, done.
 *
 * The output path is written in full because every module's source shares the
 * same filename; passing a directory would make them all collide on _style.css.
 * includePaths lets a module write `@use 'config' as *` instead of climbing
 * three levels back out to source/scss.
 */
const fs = require('fs');
const path = require('path');

const moduleRoot = 'template-parts/dynamic-content';
const moduleSassOptions = {
    sassOptions: { includePaths: [path.resolve(__dirname, 'source/scss')] },
};

if (fs.existsSync(moduleRoot)) {
    fs.readdirSync(moduleRoot, { withFileTypes: true })
        .filter((entry) => entry.isDirectory())
        .forEach((entry) => {
            const name = entry.name;
            const style = `${moduleRoot}/${name}/_style.scss`;
            const script = `${moduleRoot}/${name}/component.js`;

            if (fs.existsSync(style)) {
                mix.sass(style, `assets/css/module/${name}.css`, moduleSassOptions);
            }

            if (fs.existsSync(script)) {
                mix.js(script, `assets/js/module/${name}.js`);
            }
        });
}


mix.options({
    processCssUrls: false, // Process/optimize relative stylesheet url()'s. Set to false, if you don't want them touched.
});

// mix.disableNotifications()