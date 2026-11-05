# My Car

A boilerplate for WordPress plugins (iWorks style): main file + PHP classes
(`/includes/iworks`) + options config (`/etc/options.php`) + assets built with
a bun bundler. New plugins are scaffolded from this stub — see below.

Requirements: [Bun](https://bun.sh) >= 1.3, PHP >= 8.1.

## How to prepare development environment?

1. get this repo: `git clone git@github.com:iworks/my-car.git`
2. run `bun install` (the `postinstall` script downloads `wp-cli.phar`)
3. run `bun run dev` to watch assets, or `bun run build` for a production build
   (both ZIPs: `release/wporg/`, `release/github/`)

## How to create a new plugin from this stub?

1. run `assets/bin/wp-plugin-create.sh 'Plugin Name' [slug]` — clones the repo
   and renames everything (slug, class names, prefixes, placeholders)
2. inside the new plugin run `bun install && bun run build`

## How to prepare test environment?

1. get `wordpress-tests-lib` repo `git clone git@github.com:ArcFramework/wordpress-tests-lib.git`
2. set environment variable `WP_TESTS_DIR` to path to `wordpress-tests-lib` repo
3. configure `wp-tests-config.php` to use `wordpress-tests-lib` repo
4. install [PHPUnit Polyfills](https://github.com/Yoast/PHPUnit-Polyfills) - `composer require --dev yoast/phpunit-polyfills:"^4.0"`
5. set environment variable `WP_TESTS_PHPUNIT_POLYFILLS_PATH` to path to `phpunit-polyfills` directory
6. add some tests
7. run `bun run test`

## Changelog

[CHANGELOG](CHANGELOG)
