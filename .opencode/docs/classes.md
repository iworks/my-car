# Classes — My Car

> Reference for work on PHP classes: base class, main class, feature classes,
> post types, and the vendored iworks frameworks.
> Read only when the task involves these areas.

## Class hierarchy

```
iworks_my_car_base                         (abstract-ish foundation)
├── iworks_my_car                          (main class, instantiated in bootstrap)
├── iworks_my_car_posttype                 (abstract, posttypes/)
│   └── iworks_my_car_posttype_{name}      (one per post type)
├── iworks_my_car_wp_admin                 (admin only, is_admin())
├── iworks_my_car_cron
└── iworks_my_car_db
iworks_my_car_posttypes                    (loader, standalone)
iworks_my_car_github                       (standalone self-updater)
```

## Base class (class-iworks-my-car-base.php)

`iworks_my_car_base` — shared properties and helpers; all plugin
classes extend it (except the `posttypes` loader and `github` updater).

Properties (constructor-filled):

- `$debug` — `WP_DEBUG` or `IWORKS_DEV_MODE`; `$dev` — `''` in debug, otherwise `'.min'`.
- `$eol` — `PHP_EOL` in debug, otherwise `''` (for building inline output).
- `$base` (`__DIR__` of includes/iworks), `$dir` (plugin directory name),
  `$url` (plugin URL), `$plugin_file`, `$plugin_file_path`.
- `$includes_directory` — `includes/iworks/my-car/` (feature classes dir).
- `$version` — literal `'PLUGIN_VERSION.BUILDTIMESTAMP'` (replaced at build).
- `$meta_prefix` — `'_iw'`; `$capability` — `manage_options`; `$options` (lazy `iworks_options`).

Methods:

- `get_version( $file = null )` — plugin version; with `IWORKS_DEV_MODE` returns
  `md5_file()` of `$file` (or `time()`) for cache busting.
- `get_meta_name( $name )` / `get_post_meta( $post_id, $meta_key )` — prefixed
  meta access (`_iw_{name}`).
- `check_option_object()` — lazily creates `$this->options` via
  `iworks_my_car_get_options()`.
- `print_table_body( $post_id, $fields )` — renders admin meta-field table rows
  (`number`, `date` types; extra attrs: `placeholder`, `style`, `class`, `id`).
- `get_module_file( $filename, $dir = '' )` — resolves
  `assets/templates{$dir}/{$filename}.php` (template include helper).
- `html_title( $text )` — `<h1 class="wp-heading-inline">` for admin pages.
- `get_stub_data()` — stub metadata (published date, version, GitHub URL).
- `simple_history_logger_helper( $message, $data, $level )` — logs via the
  Simple History plugin when active (adds current user data; levels:
  `debug` / `warning` / `notice`).
- `get_media_html( $post_ID )` — renders links for attachments stored in the
  `_iw…media` meta (`get_attachment_data()` helper).

## Main class (class-iworks-my-car.php)

`iworks_my_car extends iworks_my_car_base` —
instantiated once in `my-car.php`.

- `__construct()` — parent setup, sets `$version = 'PLUGIN_VERSION'`, registers
  `init` → `action_init_settings()`, then loads feature classes with the
  **canonical loading pattern**:

  ```php
  $filename = $this->includes_directory . '/class-iworks-my-car-{name}.php';
  if ( is_file( $filename ) ) {
      include_once $filename;
      new iworks_my_car_{name}();
  }
  ```

  Loads: `posttypes`, `github`, `cron`, `db`; `wp-admin` only inside `is_admin()`.
  Finally adds the filter `my-car/is_active` → `__return_true`.
- `action_init_settings()` — on `init`: non-admin only, enqueues
  `assets/styles/my-car{dev}.css` (version via `get_version()`).
- `register_activation_hook()` — `db_install()` (empty stub) + `check_option_object()`
  + `$this->options->activate()` + `do_action( 'iworks/my-car/register_activation_hook' )`.
- `register_deactivation_hook()` — options `deactivate()` +
  `do_action( 'iworks/my-car/register_deactivation_hook' )`.

## Feature classes (includes/iworks/my-car/)

Every file: `defined( 'ABSPATH' ) || exit;` + `class_exists` guard + own
`require_once` of the base class (they are included directly, not autoloaded).

- **`class-iworks-my-car-posttypes.php`** — post-type loader
  (standalone class, not a base child). Globs `posttypes/class*.php`, extracts
  the type from `class-iworks-my-car-posttype-([a-z]+).php`,
  and instantiates the class only when the filter
  `my-car/load/post-type/{name}` is `true`. Instances are kept
  in `$posttype_objects`.
- **`class-iworks-my-car-wp-admin.php`** — admin layer
  (`manage_options`, filter `iworks_my_car_capability`):
  `admin_enqueue_scripts` (priority 0) → admin assets, `wp_loaded` → options
  init (`check_option_object()`), `plugin_row_meta` → meta links, and dashboard
  metaboxes "love" / "need assistance" that defer to the vendored `rate`
  framework via filters `iworks_rate_love` / `iworks_rate_assistance`.
- **`class-iworks-my-car-cron.php`** — cron scaffolding:
  private `$hooks` map and last-sync option names (`last_sync_get_polls`,
  `last_sync_send_answers`); extend with real scheduled tasks.
- **`class-iworks-my-car-db.php`** — DB layer: `$_db_version`
  option (`my_car_db_version`), `$table_names` array (names get
  the `$wpdb` prefix), schema versioning and uninstall cleanup; tables are
  registered with `$wpdb`.
- **`class-iworks-my-car-github.php`** — GitHub self-updater
  (standalone): repo `iworks/my-car`, hooks `plugins_api`
  (plugin popup), `pre_set_site_transient_update_plugins` (update check),
  `upgrader_post_install` (install fix-ups), textdomain on `init`.
  Excluded from the WordPress.org release ZIP.

## Post types (my-car/posttypes/)

- **`class-iworks-my-car-posttype.php`** — abstract
  `iworks_my_car_posttype extends …_base`:
    - `$post_type_name` map — key (filename slug) → registered post type:
      `faq` → `iworks_faq`, `featured` → `iworks_featured`, `hero` → `iworks_hero`,
      `opinion` → `iworks_opinion`, `page` → `page`, `person` → `iworks_person`,
      `post` → `post`, `project` → `iworks_project`,
      `publication` → `iworks_publication`, `testimonial` → `iworks_testimonial`;
    - `$taxonomy_name` map — `faq_group` → `iworks_faq_group`,
      `person_role` → `iworks_person_role`;
    - `$meta_boxes` (per-type metabox definitions), `$option_name_media`,
      `$load_plugin_admin_assets` flag.
- Concrete classes: `class-iworks-my-car-posttype-{name}.php`
  — one file per type (faq, featured, hero, opinion, page, person, post,
  project, publication, testimonials). Class name:
  `iworks_my_car_posttype_{name}`.
- Templates rendered by the types live in `assets/templates/{featured,hero,opinion,project,testimonial}/`.
- **Enabling**: `add_filter( 'my-car/load/post-type/{name}', '__return_true' )`
  — `{name}` is derived from the filename (see pitfalls in `project.md`).

## Vendored frameworks (includes/iworks/)

- `options/` — `iworks_options`: settings pages, option storage and upgrade
  routines. Configured from `etc/options.php`; option names get the
  `IWORKS_MY_CAR_PREFIX` prefix. Separate git repo with its own
  README — treat as vendor, do not refactor.
- `rate/` — rate-it / "enjoying this plugin?" admin notices and dashboard
  metaboxes, wired via `iworks_rate_love` / `iworks_rate_assistance` filters
  and the `IWORKS_RATE_TEXTDOMAIN` placeholder. Separate git repo — vendor.

## Adding a new class

- **Feature class**: create
  `includes/iworks/my-car/class-iworks-my-car-{name}.php`
  with the ABSPATH guard, `class_exists` guard, base-class `require_once`, and
  the class extending `iworks_my_car_base`; instantiate it in the
  main constructor following the `is_file()` pattern (admin-only classes go
  inside the `is_admin()` branch).
- **Post type**: copy an existing `posttype-{name}.php`, rename class/file
  (kebab-case filename → `{name}` must be `[a-z]+`), register the post type in
  the constructor, and add an enable filter in the consuming theme/plugin.
- **Options field**: add the field array in `etc/options.php`
  (`name`, `type`, `th`, `sanitize_callback`, `since`) or extend via the
  `my-car/etc/config/options` filter.
