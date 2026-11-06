# My Car

**My Car** is a car manager for WordPress — build a garage of your vehicles,
keep a digital passport for each one, track the mileage and record every
service visit. Everything is managed from the admin in one place and
presented on the frontend with clean, configurable data tables.

## Features

- **Cars post type** with a **Vehicle Digital Passport**: VIN, license plate,
  production year, engine capacity, engine power and, for electrified cars,
  battery capacity and electric range.
- **Taxonomies**: brand & model (hierarchical), body type, engine type.
- **Engine-aware passport** — which passport fields apply is configured per
  engine type (electric cars don't show engine capacity, petrol cars don't
  show battery range).
- **Mileage log** per car, managed from a modal window directly in the cars
  list.
- **Service Records** post type: inspections, part replacements and other
  service events with date, mileage, workshop, cost and status (done /
  planned), tagged with predefined **service types**.
- **Frontend data tables** — passport, mileage log and service history on
  every car page, with per-field visibility (owner only / everyone / hidden)
  for VIN, license plate, mileage log and service records.
- **Predefined data import** — one-click term sets: popular brands with
  models, body types, engine types and service types; language-aware presets
  (e.g. Polish) when the site runs in a supported language.

Requirements: [Bun](https://bun.sh) >= 1.3, PHP >= 8.1.

## How to prepare development environment?

1. get this repo: `git clone git@github.com:iworks/my-car.git`
2. run `bun install` (the `postinstall` script downloads `wp-cli.phar`)
3. run `bun run dev` to watch assets, or `bun run build` for a production build
   (both ZIPs: `release/wporg/`, `release/github/`)

## How to prepare test environment?

1. get `wordpress-tests-lib` repo `git clone git@github.com:ArcFramework/wordpress-tests-lib.git`
2. set environment variable `WP_TESTS_DIR` to path to `wordpress-tests-lib` repo
3. configure `wp-tests-config.php` to use `wordpress-tests-lib` repo
4. install [PHPUnit Polyfills](https://github.com/Yoast/PHPUnit-Polyfills) - `composer require --dev yoast/phpunit-polyfills:"^4.0"`
5. set environment variable `WP_TESTS_PHPUNIT_POLYFILLS_PATH` to path to `phpunit-polyfills` directory
6. add some tests
7. run `bun run test`

## Changelog

[CHANGELOG](CHANGELOG.md)
