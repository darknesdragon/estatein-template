/**
 * Dynamic content module scaffolder.
 *
 *   npm run make:module <layout_name> [--js] [--force]
 *
 * Reads the ACF flexible content layout out of acf-json/ and writes a working
 * index.php with every sub field already wired by name and type, plus a
 * _style.scss skeleton whose BEM block matches the folder.
 *
 * If the layout is not found in acf-json (e.g. you have not saved the field
 * group yet) it falls back to a plain scaffold so the command is still useful.
 */

const fs = require('fs');
const path = require('path');

const THEME = path.resolve(__dirname, '..');
const MODULE_ROOT = path.join(THEME, 'template-parts', 'dynamic-content');
const ACF_JSON = path.join(THEME, 'acf-json');

// ---------------------------------------------------------------- arguments

const argv = process.argv.slice(2);
const flags = new Set(argv.filter((a) => a.startsWith('--')));
const layoutName = argv.find((a) => !a.startsWith('--'));

if (!layoutName) {
    console.error('Usage: npm run make:module <layout_name> [--js] [--force]');
    process.exit(1);
}

if (!/^[a-z0-9_]+$/.test(layoutName)) {
    console.error(`Invalid layout name "${layoutName}". Use lowercase, digits and underscores -- it must match the ACF layout name exactly.`);
    process.exit(1);
}

const withJs = flags.has('--js');
const force = flags.has('--force');

// ---------------------------------------------------------------- helpers

const kebab = (s) => s.replace(/_/g, '-');
const block = `drg-${kebab(layoutName)}`;
const el = (name) => `${block}__${kebab(name)}`;
const indent = (depth) => '    '.repeat(depth);

/** Find the layout's sub_fields across every field group in acf-json. */
function findLayout(name) {
    if (!fs.existsSync(ACF_JSON)) return null;

    for (const file of fs.readdirSync(ACF_JSON).filter((f) => f.endsWith('.json'))) {
        let group;
        try {
            group = JSON.parse(fs.readFileSync(path.join(ACF_JSON, file), 'utf8'));
        } catch {
            continue; // a malformed json file should not break scaffolding
        }

        const walk = (fields) => {
            for (const field of fields || []) {
                if (field.type === 'flexible_content') {
                    for (const layout of Object.values(field.layouts || {})) {
                        if (layout.name === name) return layout;
                    }
                }
                if (field.sub_fields) {
                    const hit = walk(field.sub_fields);
                    if (hit) return hit;
                }
            }
            return null;
        };

        const hit = walk(group.fields);
        if (hit) return { layout: hit, file };
    }

    return null;
}

/**
 * Detect the "page or url" trio this theme uses repeatedly:
 * a *_target select, a post_object, and a url field as siblings.
 */
function findLinkTrio(fields) {
    const select = fields.find((f) => f.type === 'select' && /target/.test(f.name));
    const post = fields.find((f) => f.type === 'post_object' && /target/.test(f.name));
    const url = fields.find((f) => f.type === 'url');

    if (select && post && url) {
        return { select, post, url, names: new Set([select.name, post.name, url.name]) };
    }
    return null;
}

// ---------------------------------------------------------------- php output

/** Render one field as PHP, returning an array of lines. */
function fieldToPhp(field, depth) {
    const i = indent(depth);
    const n = field.name;
    const cls = el(n);
    const out = [];

    switch (field.type) {
        case 'image':
            out.push(`${i}<?php if ( $${n} = get_sub_field( '${n}' ) ) : ?>`);
            out.push(`${i}    <div class="${cls}">`);
            out.push(`${i}        <?php drg_the_acf_image( $${n}, '${cls}-img' ); ?>`);
            out.push(`${i}    </div>`);
            out.push(`${i}<?php endif; ?>`);
            break;

        case 'gallery':
            out.push(`${i}<?php if ( $${n} = get_sub_field( '${n}' ) ) : ?>`);
            out.push(`${i}    <ul class="${cls}">`);
            out.push(`${i}        <?php foreach ( $${n} as $image ) : ?>`);
            out.push(`${i}            <li class="${cls}-item">`);
            out.push(`${i}                <?php drg_the_acf_image( $image, '${cls}-img' ); ?>`);
            out.push(`${i}            </li>`);
            out.push(`${i}        <?php endforeach; ?>`);
            out.push(`${i}    </ul>`);
            out.push(`${i}<?php endif; ?>`);
            break;

        case 'textarea':
            // textareas in this theme carry no automatic formatting
            out.push(`${i}<?php if ( $${n} = get_sub_field( '${n}' ) ) : ?>`);
            out.push(`${i}    <p class="${cls}"><?php drg_the_multiline( $${n} ); ?></p>`);
            out.push(`${i}<?php endif; ?>`);
            break;

        case 'wysiwyg':
            out.push(`${i}<?php if ( $${n} = get_sub_field( '${n}' ) ) : ?>`);
            out.push(`${i}    <div class="${cls}"><?php echo wp_kses_post( $${n} ); ?></div>`);
            out.push(`${i}<?php endif; ?>`);
            break;

        case 'true_false':
            out.push(`${i}<?php if ( get_sub_field( '${n}' ) ) : ?>`);
            out.push(`${i}    <!-- TODO ${n} -->`);
            out.push(`${i}<?php endif; ?>`);
            break;

        case 'post_object':
            out.push(`${i}<?php $${n} = drg_to_post_array( get_sub_field( '${n}' ) ); ?>`);
            out.push(`${i}<?php if ( ! empty( $${n} ) ) : ?>`);
            out.push(`${i}    <ul class="${cls}">`);
            out.push(`${i}        <?php foreach ( $${n} as $post_item ) : ?>`);
            out.push(`${i}            <li class="${cls}-item">`);
            out.push(`${i}                <a href="<?php echo esc_url( get_permalink( $post_item ) ); ?>">`);
            out.push(`${i}                    <?php echo esc_html( get_the_title( $post_item ) ); ?>`);
            out.push(`${i}                </a>`);
            out.push(`${i}            </li>`);
            out.push(`${i}        <?php endforeach; ?>`);
            out.push(`${i}    </ul>`);
            out.push(`${i}<?php endif; ?>`);
            break;

        case 'repeater':
            out.push(`${i}<?php if ( have_rows( '${n}' ) ) : ?>`);
            out.push(`${i}    <ul class="${cls}">`);
            out.push(`${i}        <?php while ( have_rows( '${n}' ) ) : the_row(); ?>`);
            out.push(`${i}            <li class="${cls}-item">`);
            out.push(...renderFields(field.sub_fields || [], depth + 4));
            out.push(`${i}            </li>`);
            out.push(`${i}        <?php endwhile; ?>`);
            out.push(`${i}    </ul>`);
            out.push(`${i}<?php endif; ?>`);
            break;

        case 'select':
        case 'radio':
            out.push(`${i}<?php if ( $${n} = get_sub_field( '${n}' ) ) : ?>`);
            out.push(`${i}    <span class="${cls}"><?php echo esc_html( $${n} ); ?></span>`);
            out.push(`${i}<?php endif; ?>`);
            break;

        case 'url':
        case 'link':
            out.push(`${i}<?php if ( $${n} = get_sub_field( '${n}' ) ) : ?>`);
            out.push(`${i}    <a class="${cls}" href="<?php echo esc_url( $${n} ); ?>"><?php echo esc_html( $${n} ); ?></a>`);
            out.push(`${i}<?php endif; ?>`);
            break;

        default: // text, number, email, and anything unrecognised
            out.push(`${i}<?php if ( $${n} = get_sub_field( '${n}' ) ) : ?>`);
            out.push(`${i}    <p class="${cls}"><?php echo esc_html( $${n} ); ?></p>`);
            out.push(`${i}<?php endif; ?>`);
    }

    return out;
}

/** Render a list of sibling fields, collapsing any link trio into one <a>. */
function renderFields(fields, depth) {
    const i = indent(depth);
    const trio = findLinkTrio(fields);
    const lines = [];

    for (const field of fields) {
        if (trio && trio.names.has(field.name)) continue; // handled below
        if (!field.name) continue; // unnamed fields are unreachable in PHP
        lines.push(...fieldToPhp(field, depth));
        lines.push('');
    }

    if (trio) {
        const label = fields.find((f) => f.type === 'text' && /label/.test(f.name));
        const text = label ? `esc_html( $${label.name} )` : `esc_html( 'Learn more' )`;
        lines.push(`${i}<?php`);
        lines.push(`${i}$link_href = drg_resolve_link(`);
        lines.push(`${i}    get_sub_field( '${trio.select.name}' ),`);
        lines.push(`${i}    get_sub_field( '${trio.post.name}' ),`);
        lines.push(`${i}    get_sub_field( '${trio.url.name}' )`);
        lines.push(`${i});`);
        lines.push(`${i}?>`);
        lines.push(`${i}<?php if ( $link_href ) : ?>`);
        lines.push(`${i}    <a class="btn btn-primary ${el('link')}" href="<?php echo esc_url( $link_href ); ?>">`);
        lines.push(`${i}        <?php echo ${text}; ?>`);
        lines.push(`${i}    </a>`);
        lines.push(`${i}<?php endif; ?>`);
        lines.push('');
    }

    return lines;
}

function buildIndexPhp(layout) {
    const fields = layout ? layout.sub_fields || [] : [];
    const summary = fields.length
        ? fields.map((f) => ` * Fields: ${f.name} [${f.type}]`).join('\n')
        : ' * Fields: none found in acf-json -- plain scaffold';

    const body = fields.length
        ? renderFields(fields, 2).join('\n')
        : `        <!-- TODO markup for ${layoutName} -->`;

    return `<?php
/**
 * Module: ${layoutName}
${summary}
 *
 * Scaffolded by tools/make-module.js -- edit freely.
 * Runs inside the_row(), so get_sub_field() works directly.
 */
?>

<section class="${block}">
    <div class="container">
${body}
    </div>
</section>
`;
}

function buildStyleScss() {
    return `@use 'config' as *;

// |‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾| //
// | Module: ${layoutName}
// |_________________________________________| //

.${block} {
    @include vwUnit(padding-top, 120, 64);
    @include vwUnit(padding-bottom, 120, 64);
}
`;
}

function buildComponentJs() {
    return `/**
 * Module: ${layoutName}
 *
 * Read the shared runtime from window.DRG -- never import gsap here, that
 * creates a second instance which cannot coordinate with layout.js.
 */
(function () {
    const root = document.querySelector('.${block}');
    if (!root) return;

    const { gsap, ScrollTrigger } = window.DRG || {};
    if (!gsap) return;

    // TODO behaviour for ${layoutName}
})();
`;
}

// ---------------------------------------------------------------- write

const dir = path.join(MODULE_ROOT, layoutName);

if (fs.existsSync(dir) && !force) {
    console.error(`Module "${layoutName}" already exists at template-parts/dynamic-content/${layoutName}`);
    console.error('Pass --force to overwrite it.');
    process.exit(1);
}

const found = findLayout(layoutName);
if (!found) {
    console.warn(`! Layout "${layoutName}" not found in acf-json -- writing a plain scaffold.`);
    console.warn('  Register the layout in ACF, save the field group, then re-run with --force to wire the fields.');
}

fs.mkdirSync(dir, { recursive: true });

const written = [];
const write = (file, contents) => {
    fs.writeFileSync(path.join(dir, file), contents);
    written.push(file);
};

write('index.php', buildIndexPhp(found ? found.layout : null));
write('_style.scss', buildStyleScss());
if (withJs) write('component.js', buildComponentJs());

console.log(`\nCreated template-parts/dynamic-content/${layoutName}/`);
written.forEach((f) => console.log(`  ${f}`));
if (found) console.log(`\nFields wired from ${found.file}`);
console.log('\nRestart the build -- webpack scans the module folders at startup, so');
console.log('a running `npm run watch` will not see this folder until restarted.\n');
