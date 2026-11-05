# Build — Bun bundler

> Toolchain, modes, pipeline, placeholders, release ZIPs, translations, tests.
> Read only when working on the build.

## Toolchain

- Bun (>= 1.3) — package manager, script runner and the bundler runtime
  (`bundler/index.js` modes). `bun.lock` is committed; `package-lock.json` is
  gitignored. Node is not required (no wp-scripts — this plugin has no blocks).
- PHP ≥ 8.1; `wp-cli.phar` (downloaded by the `postinstall` script, gitignored)
  for translations; PHPUnit for tests.
- Prettier, sass, terser and clean-css run **in-process** (`bundler/tools.mjs`) —
  no CLI spawns per file. Only wp-cli and PHPUnit are child processes.
- All dist writes are atomic (temp + rename); tool failures print as friendly
  one-liners (`tool — file` + message, no stack trace) and fail the build.

## Files (`bundler/`)

| File                | Role                                                                                          |
| ------------------- | --------------------------------------------------------------------------------------------- |
| `index.js`          | Modes: `dev` (default) / `compile` / `build` / `build:wporg` / `build:github` / `make-pot` / `test` |
| `config.mjs`        | Paths, names (from `package.json`), placeholder patterns, release copy rules per flavor        |
| `pipelines.mjs`     | CSS (sass → concat → clean-css) and JS (concat → terser) pipelines, prettier pass, clean       |
| `tools.mjs`         | In-process APIs (prettier, sass, terser, clean-css), atomic writes, glob helpers               |
| `makeZip.mjs`       | Staging copy → placeholder replacement → ZIP (archiver, level 9)                               |
| `makePot.mjs`       | wp-cli i18n: `make-pot` → `update-po` (→ `make-mo` for github)                                 |
| `pluginCheck.mjs`   | wporg only: Plugin Check (PCP) on the staged release (see below)                               |
| `spawnAsync.mjs`    | `child_process.spawn` wrapper with captured output                                             |
| `consoleHelpers.mjs`, `progressBar.mjs` | Logging, timing, build progress bar                                        |

Bundle names are derived from `package.json` `name` at runtime — the scaffolding
script renames the plugin and the bundler follows automatically.

## Modes (`bun run ...`)

| Command                | Description                                                                                  |
| ---------------------- | -------------------------------------------------------------------------------------------- |
| `bun run dev`          | Watcher: `.scss` → CSS pipeline, `assets/scripts/src/**` → prettier + JS pipeline (200 ms debounce); no ZIP, no i18n, no auto browser reload |
| `bun run compile`      | One-shot asset compile with source maps (old `grunt` default task)                            |
| `bun run build`        | Both flavors back-to-back: wporg + github                                                     |
| `bun run build:wporg`  | Production → `release/wporg/{name}.zip` + Plugin Check (PCP)                                 |

Excluded from the release ZIPs (copy rules in `bundler/config.mjs`):
`AGENTS.md`, `README.md`, `phpcs.xml.dist`, `.env*`, `.editorconfig`,
`.git*` files, `bundler/`, `.opencode/`, `tests/`, `release/`, `wp-cli.phar`,
`node_modules/`, source maps.
| `bun run build:github` | Production + `.mo` → `release/github/{name}.zip`                                              |
| `bun run make-pot`     | Translations only (`.pot` + `.po` update)                                                     |
| `bun run update-libs`  | `git pull` on the vendored iWorks libraries (`includes/iworks/options`, `includes/iworks/rate`) |
| `bun run install-libs` | Add iWorks libraries as git submodules: `bun run install-libs <https\|git> <option\|rate\|all>` — both args required |
| `bun run test`         | PHPUnit (`tests/php/phpunit.xml`); jshint was dropped with Grunt                              |
| (env) `DEV_EXIT_AFTER_MS` | Dev mode exits itself after N ms — headless testing (put it in `.env`, see `.env.example`) |

## Build pipeline (`bun run build:wporg`, 8 steps)

1. **clean** — remove built JS (`assets/scripts/*.js`), built CSS
   (`assets/styles/*.css`, `assets/styles/**/*.css`, `*.map`), `languages/*.mo`
   (grunt `clean:compiled` semantics; `assets/styles/` and built JS are gitignored);
2. **prettier** — format sources in place (`assets/scripts/src/**/*.js`,
   `bundler/**/*.{js,mjs}`, root `*.js`) — the build rewrites sources by design;
3. **css** — sass compiles every non-`_` entry under `assets/sass/` (parallel,
   no maps in build) → `assets/styles/{path}.css`; per-variant concat:
   `admin/*.css` → `{name}-admin.css`, `frontend/settings.css` first + rest →
   `{name}-frontend.css` (missing dir → empty bundle + warning); `# sourceMappingURL`
   comments stripped; clean-css (level 1, no shorthand merge) → `.min.css`;
4. **js** — concat `assets/scripts/src/{admin,frontend}/*.js` with the concat
   banner → `{name}-{variant}.js`; terser (mangle, `jQuery` reserved) +
   min banner → `.min.js`; missing frontend sources → banner-only bundle + warning;
5. **i18n** — wp-cli `i18n make-pot` → `languages/{name}.pot`, then
   `i18n update-po` per existing `.po`; `make-mo` only in `build:github`;
6. **zip** — staging copy → placeholder replacement → `release/{flavor}/{version}/{name}/`
   → `release/{flavor}/{name}.zip`;
7. **plugin-check** (wporg only) — see below;
8. done — progress bar + total time; any failure prints `BUILD FAILED` and exits 1.

## Plugin Check (PCP, wporg build step 7)

`bundler/pluginCheck.mjs` — copies the staged release
(`release/wporg/{version}/{name}`) into the local WordPress plugins dir
(parent of the plugin dir; override with `PLUGIN_CHECK_PLUGINS_DIR`) under
the real slug, runs `wp plugin check <slug>` via `wp-cli.phar`, then removes
the copy. **Fails the build on any ERROR** (warnings are informational; each
error line is printed with line/column/code). Skips gracefully (with a
warning) when `wp-cli.phar` or the Plugin Check plugin is missing.
`PLUGIN_CHECK_SKIP=1` skips the step for one build. Requires the
[Plugin Check](https://wordpress.org/plugins/plugin-check/) plugin installed
locally. Current state: 12 errors, all in vendored iWorks libraries (unescaped
`wpautop()` in the rate templates, unescaped `$args` in the options class,
`mt_rand()` in the rate class) — reported upstream; fix there rather than
skipping the check.

Banners (grunt parity): concat = `/*! {title} - {version}\n * {website}\n *
Copyright (c) {year}\n * Licensed {license} */`; minified JS uses the same with
`Copyright (c) {year};` (note the `;`). Minified CSS has no banner. The website
line uses `repository.website` (grunt printed an empty line because `pkg.homepage`
never existed).

## Placeholders (release copies only)

Applied to every staged text file (binary extensions and `.mo` skipped):
`AUTHOR_NAME/URI/URL`, `BUILDTIMESTAMP`, `BUILDTIME`,
`IWORKS_RATE_TEXTDOMAIN`, `IWORKS_OPTIONS_TEXTDOMAIN`, `PLUGIN_DESCRIPTION`,
`PLUGIN_GITHUB_WEBSITE`, `PLUGIN_NAME`, `PLUGIN_REQUIRES_PHP`,
`PLUGIN_REQUIRES_WORDPRESS`, `PLUGIN_TESTED_WORDPRESS`, `PLUGIN_TAGLINE`,
`PLUGIN_TAGS`, `PLUGIN_TILL_YEAR`, `PLUGIN_TITLE`, `PLUGIN_URI`, `PLUGIN_URL`,
`PLUGIN_VERSION`, and the `^Version: .*$` header line — all sourced from
`package.json` + build time (`bundler/config.mjs`).

The version lives **only** in `package.json`. `2026` is **not**
replaced at build (grunt parity) — the scaffolding script bakes it in once.

## Release ZIPs

- **WordPress.org** (`bun run build:wporg`) → `release/wporg/{version}/{name}/`. Excludes:
  repo meta (`.git*`, `.editorconfig`, `phpcs.xml.dist`, `.opencode/`, `bundler/`, lock files,
  `wp-cli.phar`, `package.json`, `README.md`, `LICENSE`, `contributing.md`,
  tests, `release/`, `.sass-cache`, `**/*.map`), asset sources
  (`assets/sass`, `assets/scripts/src`, `assets/scss`, `assets/styles/frontend`,
  legacy `**/img/src`, `**/js/src`, `**/css/{less,sass,src}`), the GitHub
  self-updater class, `includes/pro`, `languages/*.po|*.mo`. `readme.txt`
  **is** included (required by wp.org).
- **GitHub** (`bun run build:github`) → `release/github/{version}/{name}/`.
  Base excludes plus `readme.txt` and `includes/pro`; asset sources and `.po`
  **are** included, `.mo` compiled by `make-mo` when `.po` files exist. Pairs
  with the GitHub self-updater class.
- Copy rules are ordered and evaluated **first match wins** (grunt semantics) —
  the catch-all `**/*` include must stay last (`bundler/config.mjs`).
- A missing main plugin file logs a warning; staging proceeds.

## Tests

- `bun run test` → `phpunit --configuration tests/php/phpunit.xml`
  (bootstrap: `tests/php/bootstrap.php`). Needs a WordPress test environment.
- Dropped with Grunt: jshint (dead tooling) and `checktextdomain` (no bun-era
  equivalent; gettext usage is covered by `make-pot` output review).

## Runtime quirks / pitfalls (do not fix without approval)

- The build's prettier step **rewrites source files** — that is intended
  (formatting is part of the pipeline, like the theme bundler). A diff after
  `bun run build` containing only formatting is expected.
- `2026` stays literal in built ZIPs (scaffold-only replacement).
- Copy rules: negations must precede the catch-all `**/*` include — reordering
  silently leaks sources into the ZIPs.
- `install-libs` uses `git submodule add --force` on purpose: it recovers a
  half-registered state (`.gitmodules` entry without a working dir, e.g. after
  an interrupted clone) and reuses an existing local `.git` checkout.
- Terser mangles variable names differently than grunt-uglify (e.g. `t` vs `e`)
  — minified bundles are not byte-identical to old artifacts, only equivalent.
- `bun run make-pot` needs network on the first run (wp-cli.phar download) —
  `postinstall` retries it on every `bun install`.
