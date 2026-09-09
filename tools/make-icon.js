/**
 * Icon fetcher / importer.
 *
 *   npm run make:icon <set>:<name> [--force]
 *   npm run make:icon lucide:circle-arrow-right
 *
 *   npm run make:icon -- --from <path/to/file.svg> <name> [--force]
 *   npm run make:icon -- --from ~/exports/map-pin.svg drg-map-pin
 *
 * Pulls one SVG into assets/images/icon/, ready for template-parts/icon.php to
 * inline. Either from the Iconify API (named <set>-<name>.svg) or from a local
 * file such as a Figma export (named <name>.svg).
 *
 * The network path is the only one in the theme that touches the network, and it
 * runs on a developer machine only -- the resulting .svg is committed, so the
 * site never depends on Iconify at runtime. Browse the sets at
 * https://icon-sets.iconify.design/
 *
 * Both paths run through normalise() below, because an icon is only useful to
 * this theme if it inherits colour and font size from its context.
 */

const fs = require('fs');
const https = require('https');
const path = require('path');

const THEME = path.resolve(__dirname, '..');
const ICON_DIR = path.join(THEME, 'assets', 'images', 'icon');
const API_HOST = 'api.iconify.design';

// ---------------------------------------------------------------- normalise

/**
 * Bring an SVG in line with what template-parts/icon.php expects.
 *
 * The part inlines the file rather than linking it, specifically so the icon
 * inherits currentColor and the surrounding font size. A design-tool export
 * satisfies neither by default -- Figma writes literal fills, pixel dimensions,
 * a white background rect and a bounding-box clipPath -- so all four are undone
 * here rather than by hand across every file.
 *
 * clipPath ids are the one that is a bug rather than a tidiness issue: ids are
 * document-global, so inlining the same icon twice on a page yields duplicates
 * and the second instance can clip against the wrong path.
 */
function normalise(svg) {
    const notes = [];
    let out = svg.trim();

    /**
     * clipPaths are handled FIRST, before the white-rect sweep below.
     *
     * Figma puts its bounding-box rect INSIDE the clipPath, and that rect is
     * white -- so sweeping white rects first empties the clipPath, which then no
     * longer looks like a bounding box and gets kept. An empty clipPath clips
     * everything away, and the icon renders blank. Order is load-bearing here.
     *
     * A clipPath whose only child is a rect clips nothing worth keeping. Anything
     * more complex is left alone and reported, because silently removing a clip
     * that does real work would change the artwork.
     */
    const clipIds = [...out.matchAll(/<clipPath id="([^"]+)">([\s\S]*?)<\/clipPath>/g)];

    for (const [block, id, inner] of clipIds) {
        const rects = inner.match(/<rect/g) || [];
        const isBoundingBox = rects.length === 1 && !/<(path|circle|ellipse|polygon)/.test(inner);

        if (!isBoundingBox) {
            notes.push(`kept clipPath #${id} -- not a plain bounding box, check it by hand`);
            continue;
        }

        out = out.replace(block, '');
        out = out.replace(new RegExp(`\\s*clip-path="url\\(#${id}\\)"`, 'g'), '');
        notes.push(`removed bounding-box clipPath #${id}`);
    }

    // an emptied <defs> is left behind by the above
    out = out.replace(/<defs>\s*<\/defs>/g, '');

    // whatever white rects remain are artboard backgrounds, not artwork
    const before = out;
    out = out.replace(/<rect[^>]*fill="(?:white|#fff|#ffffff)"[^>]*\/>/gi, '');
    if (out !== before) notes.push('removed white background rect');

    // literal colours defeat the point of inlining -- fill="none" is structural, keep it
    const colours = new Set(
        [...out.matchAll(/(?:fill|stroke)="(#[0-9a-fA-F]{3,8}|black|white|rgb\([^)]*\))"/g)].map((m) => m[1])
    );

    if (colours.size) {
        out = out.replace(
            /(fill|stroke)="(#[0-9a-fA-F]{3,8}|black|white|rgb\([^)]*\))"/g,
            '$1="currentColor"'
        );
        notes.push(`recoloured ${[...colours].join(', ')} -> currentColor`);
    }

    // size comes from the font, not the file; the viewBox is left as authored
    if (/width="(?!1em)[^"]*"/.test(out) || /height="(?!1em)[^"]*"/.test(out)) {
        out = out.replace(/\swidth="[^"]*"/, ' width="1em"').replace(/\sheight="[^"]*"/, ' height="1em"');
        notes.push('width/height -> 1em');
    }

    // collapse the whitespace the removals leave behind
    out = out.replace(/\n\s*\n/g, '\n').replace(/>\s+</g, '>\n<').trim();

    return { svg: out, notes };
}

function write(fileName, svg, notes, usageName) {
    fs.mkdirSync(ICON_DIR, { recursive: true });
    fs.writeFileSync(path.join(ICON_DIR, fileName), `${svg}\n`, 'utf8');

    console.log(`Wrote assets/images/icon/${fileName}`);
    notes.forEach((n) => console.log(`  - ${n}`));
    console.log('Use it with:');
    console.log(`    get_template_part( 'template-parts/icon', null, array( 'name' => '${usageName}' ) );`);
}

// ---------------------------------------------------------------- arguments

const argv = process.argv.slice(2);
const flags = new Set(argv.filter((a) => a.startsWith('--')));
const force = flags.has('--force');

const fromIndex = argv.indexOf('--from');
const isLocal = fromIndex !== -1;

const positional = argv.filter((a, i) => !a.startsWith('--') && i !== fromIndex + 1);
const target = positional[0];

if (!target) {
    console.error('Usage: npm run make:icon <set>:<name> [--force]');
    console.error('       npm run make:icon -- --from <file.svg> <name> [--force]');
    console.error('Example: npm run make:icon lucide:circle-arrow-right');
    console.error('         npm run make:icon -- --from ./exports/map-pin.svg drg-map-pin');
    process.exit(1);
}

// ---------------------------------------------------------------- local file

if (isLocal) {
    const source = argv[fromIndex + 1];

    if (!source) {
        console.error('--from needs a path to an .svg file.');
        process.exit(1);
    }

    if (!/^[a-z0-9-]+$/.test(target)) {
        console.error(`Invalid name "${target}". Lowercase letters, digits and hyphens only -- e.g. drg-map-pin`);
        process.exit(1);
    }

    if (!fs.existsSync(source)) {
        console.error(`No such file: ${source}`);
        process.exit(1);
    }

    const fileName = `${target}.svg`;

    if (fs.existsSync(path.join(ICON_DIR, fileName)) && !force) {
        console.error(`${fileName} already exists. Pass --force to overwrite.`);
        process.exit(1);
    }

    const raw = fs.readFileSync(source, 'utf8').trim();

    if (!raw.startsWith('<svg')) {
        console.error(`${source} does not look like an SVG. Nothing written.`);
        process.exit(1);
    }

    const { svg, notes } = normalise(raw);
    write(fileName, svg, notes, target);

    return;
}

// ---------------------------------------------------------------- iconify

if (!/^[a-z0-9-]+:[a-z0-9-]+$/.test(target)) {
    console.error(`Invalid icon id "${target}". Expected <set>:<name>, lowercase with digits and hyphens -- e.g. lucide:circle-arrow-right`);
    process.exit(1);
}

const [set, name] = target.split(':');
const fileName = `${set}-${name}.svg`;

if (fs.existsSync(path.join(ICON_DIR, fileName)) && !force) {
    console.error(`${fileName} already exists. Pass --force to overwrite.`);
    process.exit(1);
}

const request = https.get(
    { host: API_HOST, path: `/${set}/${name}.svg`, headers: { accept: 'image/svg+xml' } },
    (response) => {
        if (response.statusCode !== 200) {
            console.error(`Iconify returned HTTP ${response.statusCode} for ${target}. Check the id at https://icon-sets.iconify.design/${set}/`);
            response.resume();
            process.exit(1);
        }

        let body = '';
        response.setEncoding('utf8');
        response.on('data', (chunk) => {
            body += chunk;
        });

        response.on('end', () => {
            const raw = body.trim();

            // the API answers 404s with a plain-text body, so a 200 is not enough
            if (!raw.startsWith('<svg')) {
                console.error(`Response for ${target} is not an SVG. Nothing written.`);
                process.exit(1);
            }

            const { svg, notes } = normalise(raw);
            write(fileName, svg, notes, `${set}-${name}`);
        });
    }
);

request.on('error', (error) => {
    console.error(`Could not reach ${API_HOST}: ${error.message}`);
    process.exit(1);
});
