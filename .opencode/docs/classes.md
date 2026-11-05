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
  `assets/templates{$dir}/{$filename}.php` (template include helper; the
  templates directory was removed with the boilerplate — it returns `false`
  for car / service-record, so list rendering skips the includes).
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
  iworks/my-car/load/post-type/{name}` is `true`. Instances are kept
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
      `car` → `iworks_car`, `faq` → `iworks_faq`, `featured` → `iworks_featured`, `hero` → `iworks_hero`,
      `opinion` → `iworks_opinion`, `page` → `page`, `person` → `iworks_person`,
      `post` → `post`, `project` → `iworks_project`,
      `publication` → `iworks_publication`, `testimonial` → `iworks_testimonial`;
    - `$taxonomy_name` map — `car_body_type` → `iworks_car_body_type`,
      `car_brand` → `iworks_car_brand`, `car_engine_type` → `iworks_car_engine_type`,
      `faq_group` → `iworks_faq_group`,
      `person_role` → `iworks_person_role`;
    - `$meta_boxes` (per-type metabox definitions), `$option_name_media`,
      `$load_plugin_admin_assets` flag.
- Concrete classes: `class-iworks-my-car-posttype-{name}.php`
  — one file per type (car, service-record). Class name:
  `iworks_my_car_posttype_{name}`.
- **Enabling**: `add_filter( 'iworks/my-car/load/post-type/{name}', '__return_true' )`
  — `{name}` is derived from the filename (see pitfalls in `project.md`); the
  bootstrap stubs exactly the two filters above.

- **Car post type** (`posttype-car.php`) — registers `iworks_car` with taxonomies
  `iworks_car_brand` (hierarchical "Brands & Models" — models are child terms
  of their brand), `iworks_car_body_type`, `iworks_car_engine_type`
  (all defined in the base `$post_type_name` / `$taxonomy_name` maps),
  a "Vehicle Digital Passport" metabox, an archive
  toggle driven by the `car_enable_archive` option, an admin-list "Show" column
  opening a modal (`<dialog>` + `admin-ajax.php?action=my-car-get-car`, JS in
  `assets/scripts/src/admin/car-modal.js`, styles in `assets/styles/admin/car-modal.css`),
  and a settings-page "Import" tab (see below) to import predefined terms.
  Text metabox fields support an integer `maxlength` option (rendered as an
  input attribute and enforced again server-side on save); the VIN field
  uses `maxlength: 17`.
  Passport fields in the modal are engine-type aware: fields
  `engine_capacity`, `battery_capacity` and `electric_range` are shown only
  when the car's engine type terms enable them. The modal has three tabs:
  Car Data, Mileage Log and Service Records (the latter reads the join
  table via `get_car_service_records()`: date, mileage, service types,
  workshop, translated status). Which fields apply is
  configured per term on the Engine Types edit screen (checkbox group
  "Passport Fields", stored in term meta `_iw_engine_type_fields` via
  `render_engine_type_*_fields()` / `save_engine_type_fields()`); terms
  without a saved config fall back to defaults per slug
  (`get_engine_type_fields_defaults()`, filter
  iworks/my-car/engine-type-default-fields`). The field set is also filterable via
  iworks/my-car/passport-fields`. On the frontend, single car pages get a
  "Vehicle Digital Passport" data table (passport fields + taxonomy links)
  and a "Mileage Log" table appended to the content via
  `filter_the_content()` (filter `iworks/my-car/frontend-table`); styles live in
  `assets/styles/frontend/car.css` and are compiled into
  `assets/styles/my-car-frontend.css`. Frontend visibility of VIN, the
  license plate and the mileage log is configured per car in the side
  metabox "Frontend Visibility" (`render_meta_box_frontend_visibility()` /
  `save_frontend_visibility()`, meta `_iw_frontend_visibility`): show for
  owner (default — users who can edit the car), show for all, or hide;
  checked by `is_visible_on_frontend()`. The admin modal always shows
  everything. Visibility keys: `vin`, `license_plate`, `mileage_log`,
  `service_records` (the frontend "Service Records" table: date, mileage,
  service types — same data source as the modal tab).
- **Predefined data** — `etc/data/car-brands.json` (brands, each with nested
  models), `etc/data/car-body-types.json`, `etc/data/car-engine-types.json`.
  The settings page ("Cars → Settings") has a side metabox with a nonce-protected
  `admin-post.php?action=my-car-import-predefined` handler
  (`handle_import_predefined()` in the car posttype class) that inserts missing
  terms — brands at the top level, models as children of their brand —
  (skip-existing) and reports counts via a transient admin notice.

## Service Records (posttype-service-record.php)

- **Post type** `iworks_car_service` (source key `service_record`; WP limit
  is 20 chars so "service_record" would not fit) — non-public, UI under the
  Cars menu, supports title + editor (editor = work description).
- **"Service Event" metabox**: Car (custom `render_meta_car()` select of
  cars), Date, Mileage, Workshop, Cost, Status (Done/Planned).
- **Join table** `wp_my_car_car_records` (car_id, record_id, event_date,
  event_mileage, unique key car_record) — managed by
  `includes/iworks/my-car/class-iworks-my-car-db.php` (aqualog-style:
  versioned `db_install()` on activation + shutdown, `drop_tables()` on
  uninstall hook, tables registered on `$wpdb`).
  `link_record()` / `unlink_record()` / `unlink_car()` / `get_car_records()`
  / `get_latest_car_record()` are its API.
- **Sync**: on `save_post_{post_type}` (priority 20) the record links to its
  car in the join table (empty car/date unlinks); record trash/delete and
  car delete remove rows. Empty titles are auto-filled "Car — Date".
- **Taxonomy** `iworks_car_service_type` (flat, tags-style) — "Service
  Types": Inspection, Engine Oil, filters, brake parts, timing, spark/glow
  plugs, fluids. Importable via the Import tab (`import_service_types`
  option, `etc/data/car-service-types.json`); shown as an admin-list column.
  Registered with `show_in_rest => true` (rest_base `car_service_types`) —
  required for term editing in the editor. Its screen is added under the
  Cars menu manually via `action_admin_menu()` (WP auto-adds taxonomy
  submenus only for top-level post type menus). NOTE: the shared
  `register_taxonomy()` wrapper in the base posttype class had a broken
  `apply_filters()` arg order (filter name leaked into the object type);
  fixed — use it or register directly like the car class does.
- **Admin list**: Car, Service Types, Date, Mileage, Workshop, Status
  columns; Date/Mileage sortable (via `pre_get_posts` meta sort).
- **Locale presets**: `get_data_file_path()` resolves data files
  locale-aware — `etc/data/{locale}/{file}` (locale lowercased from
  `get_locale()`, e.g. `pl_pl`) first, falling back to the base
  `etc/data/{file}` when the preset dir/file doesn't exist. Polish
  translations live in `etc/data/pl_pl/` (same schema and slugs; brands
  untranslated as proper nouns). `insert_term()` skips existing slugs, so
  re-importing never duplicates or renames already-imported terms.
- Enabled with `add_filter( 'iworks/my-car/load/post-type/service-record', '__return_true' )`
  in `my-car.php`. The posttypes loader accepts hyphenated filenames and
  maps them to underscored class names.

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
  iworks/my-car/etc/config/options` filter.
