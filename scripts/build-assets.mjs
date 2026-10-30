import { mkdir, copyFile } from 'node:fs/promises';
import { execFileSync } from 'node:child_process';

await mkdir('public/assets', { recursive: true });
execFileSync(process.execPath, ['node_modules/tailwindcss/lib/cli.js', '-c', 'tailwind.config.cjs', '-i', 'resources/css/app.css', '-o', 'public/assets/app.css', '--minify'], { stdio: 'inherit' });
for (const [source, destination] of [
    ['alpinejs/dist/cdn.min.js', 'alpine.min.js'],
    ['lucide/dist/umd/lucide.min.js', 'lucide.min.js'],
    ['flatpickr/dist/flatpickr.min.js', 'flatpickr.min.js'],
    ['flatpickr/dist/flatpickr.min.css', 'flatpickr.min.css'],
    ['flatpickr/dist/l10n/id.js', 'flatpickr-id.js'],
]) await copyFile(`node_modules/${source}`, `public/assets/${destination}`);
await copyFile('resources/js/sidebar.js', 'public/assets/sidebar.js');
await copyFile('resources/js/forms.js', 'public/assets/forms.js');
await mkdir('public/assets/fonts', { recursive: true });
await mkdir('public/assets/licenses', { recursive: true });
await copyFile('node_modules/lucide/LICENSE', 'public/assets/licenses/lucide.txt');
await copyFile('node_modules/flatpickr/LICENSE.md', 'public/assets/licenses/flatpickr.txt');
for (const [packageName, font] of [
    ['inter', 'inter-latin-wght-normal.woff2'],
    ['archivo', 'archivo-latin-wght-normal.woff2'],
    ['material-symbols-outlined', 'material-symbols-outlined-latin-wght-normal.woff2'],
]) {
    const directory = `node_modules/@fontsource-variable/${packageName}`;
    await copyFile(`${directory}/files/${font}`, `public/assets/fonts/${font}`);
    await copyFile(`${directory}/LICENSE`, `public/assets/licenses/${packageName}.txt`);
}
console.log('Local UI assets built.');
