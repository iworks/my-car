# Project mechanics — My Car

> Loading flow, assets, translations, options framework, dependencies, general conventions.
> Read only when the task involves these areas.

## Overview

A WordPress plugin boilerplate ("stub") in the iWorks style. It is a template:
new plugins are created from it with `assets/bin/wp-plugin-create.sh`, which
renames the slug, class names, prefixes and placeholders. Source files contain
**literal placeholders** (`PLUGIN_VERSION`, `PLUGIN_TITLE`, `2026`,
`BUILDTIMESTAMP`, …) — they are replaced by the bun bundler at build time (see
`build.md`), never in the source.

The version lives only in `package.json` (`"version"`); the main file header
and the version constant both use the `PLUGIN_VERSION` placeholder.

## Directory structure

```
/
├── my-car.php   # Plugin bootstrap (header, constants, load filters)
├── etc/
│   └── options.php             # Options page configuration (iworks_options format)
├── includes/
│   └── iworks/
│       ├── class-iworks-my-car-base.php      # Base class
│       ├── class-iworks-my-car.php           # Main plugin class
│       ├── my-car/                           # Feature classes
│       │   ├── class-iworks-my-car-posttypes.php
│       │   ├── class-iworks-my-car-wp-admin.php
│       │   ├── class-iworks-my-car-cron.php
│       │   ├── class-iworks-my-car-db.php
│       │   ├── class-iworks-my-car-github.php
│       │   └── posttypes/        # Abstract post type + one class per type
│       ├── options/              # Vendored iworks options framework (own git repo)
│       └── rate/                 # Vendored iworks rate/love notices (own git repo)
├── assets/
│   ├── sass/admin/              # SCSS sources (_mixin, media, wp-list-table)
│   ├── scripts/src/admin/       # Admin JS sources (frontend sources dir may not exist yet)
│   ├── styles/                  # Compiled CSS (gitignored)
│   └── bin/wp-plugin-create.sh  # Scaffolding script for new plugins
├── languages/                   # my-car.pot (+ .po/.mo built)
├── tests/php/                   # PHPUnit (phpunit.xml, bootstrap.php, test-sample.php)
├── bundler/                     # Bun build system — see build.md
├── package.json                 # Version + release metadata (single source of truth)
└── readme.txt                   # WordPress.org readme
```

## Loading flow

1. WordPress loads `my-car.php` (textdomain `my-car`):
   - defines `IWORKS_MY_CAR_VERSION` (`'PLUGIN_VERSION'`) and
     `IWORKS_MY_CAR_PREFIX` (`'iworks_my-car_'`);
   - requires the main class from `includes/iworks/` (guarded by `class_exists`),
     `etc/options.php`, and the vendored `iworks_options` class;
   - registers the post-type load filters (all `__return_false` — off by default);
   - defines `iworks_my_car_get_options()` and instantiates
     `iworks_my_car`, registering activation/deactivation hooks.
2. `iworks_my_car::__construct()`:
   - registers `init` → `action_init_settings()` (frontend CSS enqueue, non-admin only);
   - loads feature classes from `includes/iworks/my-car/` with the
     `is_file()` + `include_once` + `new` pattern: `posttypes`, `github`, `cron`, `db`;
     `wp-admin` only when `is_admin()`;
   - adds the filter `my-car/is_active` (`__return_true`).
3. `iworks_my_car_posttypes` globs `posttypes/class*.php`, extracts
   the type name from the filename and loads each post type class **only when the
   filter `my-car/load/post-type/{name}` returns `true`**
   (name comes from the *filename*, see Known pitfalls).

## Constants

- `IWORKS_MY_CAR_VERSION` — plugin version (`'PLUGIN_VERSION'` placeholder).
- `IWORKS_MY_CAR_PREFIX` — prefix for options (`'iworks_my-car_'`).
- `IWORKS_DEV_MODE` (optional, `wp-config.php`) — dev mode: non-minified asset
  handling in `get_version()` (md5 of file / `time()`), see below.
- Global: `$iworks_my_car_options` (options object),
  `$iworks_my_car` (main instance).

## Post types

- Post types are disabled by default; a theme/plugin must enable them via filters:
  `add_filter( 'my-car/load/post-type/{name}', '__return_true' )`.
- The bootstrap file stubs these filters with `__return_true` for exactly the
  existing types: `car` and `service-record`. The boilerplate types (faq,
  hero, opinion, …) were removed together with `assets/templates/`.

## Options layer (etc/options.php + iworks_options)

- `etc/options.php` returns the config array from `iworks_my_car_options()`.
- Single page entry `$options['index']`: `page_title`, `menu` (placement:
  `options`, `submenu`, `management`, `theme`, `posts`, `pages`, `users`,
  `plugins`, `comments`, `dashboard`, `settings`, `media`, `custom`), `parent`
  (required for `submenu`), `use_tabs`, `version`.
- Field arrays: `options` (filter `my-car/etc/config/options`),
  `metaboxes` (filter `.../metaboxes`), `pages` subpages (filter `.../pages`).
- Field shape: `name`, `type` (e.g. `heading`, `text`, `textarea`), `th` (label),
  `description`, `classes`, `sanitize_callback`, `since`.
- The option object is created by `iworks_my_car_get_options()`
  (option function name + `IWORKS_MY_CAR_PREFIX` prefix); classes
  access it via `$this->check_option_object()` (base class).

## Assets layer

- **CSS**: `assets/sass/admin/*.scss` → compiled to `assets/styles/admin/*.css`
  → concatenated to `assets/styles/my-car-admin.css` (+ `.min.css`).
  There is no frontend SCSS entry yet.
- **JS**: `assets/scripts/src/admin/*.js` → concatenated to
  `assets/scripts/my-car-admin.js` → uglified to `.min.js`.
  The `assets/scripts/src/frontend/` directory does not exist yet (the bundler expects
  it for the frontend bundle — create it when needed).
- **Enqueue**: `action_init_settings()` enqueues
  `assets/styles/my-car{dev}.css` on non-admin requests, where
  `dev` is empty in debug mode and `.min` otherwise.
- **Debug/dev mode**: `WP_DEBUG` or `IWORKS_DEV_MODE` → `$this->dev = ''`
  (unminified files) and non-empty `$this->eol`; with `IWORKS_DEV_MODE`,
  `get_version( $file )` returns the file's md5 (or `time()`) for cache busting,
  otherwise the plugin version.
- Compiled files are **gitignored** (`assets/styles/`, `assets/scripts/my-car-*.js`,
  `languages/*.mo`, `release/`) — never edit them, edit the sources and rebuild.

## Translations (i18n)

- Textdomain: `my-car`; `languages/my-car.pot`.
- `bun run make-pot` runs wp-cli `i18n make-pot` (creates/updates the `.pot`)
  and `i18n update-po` for every existing `.po` file.
- `.mo` files are compiled (`i18n make-mo`) only in `bun run build:github`.
- The GitHub updater class loads the textdomain on `init`.

## GitHub self-updater

`class-iworks-my-car-github.php` — updates the plugin from the
GitHub repository (`iworks/my-car`): `plugins_api` → plugin info
popup, `pre_set_site_transient_update_plugins` → update check,
`upgrader_post_install` → install fix-ups. This class is **excluded from the
WordPress.org ZIP** (wporg releases must not self-update from GitHub).

## PHP dependencies

- No Composer. Requires (from `package.json`, replaced into headers at build):
  WordPress ≥ 6.0, PHP ≥ 8.1, tested up to 7.1.
- Vendored frameworks live in `includes/iworks/`: `options/` (settings pages)
  and `rate/` (rate-it / "love" admin notices). Both are separate git repos —
  treat them as vendor code: do not refactor their internals.

## Coding standards (WPCS)

- Config: `phpcs.xml.dist` (WordPress standard). Run:
  `~/.config/composer/vendor/bin/phpcs` / `phpcbf` (global composer install).
- Scope: plugin's own PHP only — vendored `includes/iworks/options/` +
  `includes/iworks/rate/`, `tests/`, `release/`, `node_modules/` are excluded.
- Waived sniffs (project conventions, do not "fix"):
  `PEAR.NamingConventions.ValidClassName` (iWorks lowercase `iworks_*` names),
  `WordPress.WP.CapitalPDangit` ("wordpress" in text domain/option names),
  `WordPress.NamingConventions.ValidHookName` (hyphenated iWorks hook API),
  part of `Squiz.Commenting` (see ruleset).
- Hard requirements (never regress): `WordPress.Security.EscapeOutput`,
  `WordPress.Security.NonceVerification`, `WordPress.WP.I18n` translators
  comments, strict comparisons.
- Known remaining debt (~250 violations): Squiz.Commenting style items
  (missing `@param` tags, inline comment endings, doc capitalization) —
  tracked, low priority.

## Tests

- PHPUnit in `tests/php/` (`phpunit.xml`, `bootstrap.php`, `test-sample.php`),
  run via `bun run test` (see `build.md`).

## General conventions

- Formatting: tabs for PHP/JS/SCSS, 4 spaces for YAML, LF, final newline
  (`.editorconfig`); WordPress-style docblocks everywhere.
- Every PHP file: `defined( 'ABSPATH' ) || exit;` plus a
  `if ( class_exists( ... ) ) { return; }` guard.
- File naming: kebab-case, `class-iworks-my-car-{name}.php`;
  class names: `iworks_my_car_{name}` (snake_case, lowercase).
- Hooks and options: `my-car/...` or `iworks_my_car_...`.
- Never read the vendored frameworks' internals from feature classes — use the
  base-class helpers (`check_option_object()`, `get_meta_name()`, `get_post_meta()`).
- Scratch files (test harnesses, debug scripts) live in `.opencode/tmp/` — never
  outside the project. Gitignored; delete them when the task is done.

## Known pitfalls (do not fix without approval)

- Source placeholders (`PLUGIN_VERSION`, `2026`, `BUILDTIMESTAMP`, …)
  are intentional — do not replace them with real values in the repo.
- The bootstrap disables a `promo` post type (`.../load/post-type/promo`) but no
  `posttype-promo.php` exists in `posttypes/`.
- Post-type filter names come from the **filename**: the file
  `posttype-testimonials.php` produces the filter
  `.../load/post-type/testimonials`, while the abstract class map uses the key
  `testimonial` — check both when wiring filters.
- `base::get_post_type()` reads an undefined `$post_type` property (calling it
  fails under strict types) — the property is meant to be set by post type classes.
- The abstract post type map also contains `featured`/`testimonial` entries;
  their files exist but no enable filters are stubbed in the bootstrap.
