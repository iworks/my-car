# My Car

## How prepare development environment?

1. get this repo: `git clone git@github.com:iworks/my-car.git`
2. run `npm install` if needed `npm install --force` 
3. run `grunt build` to build assets

## How to preapre test environment?

1. get `wordpress-tests-lib` repo `git clone git@github.com:ArcFramework/wordpress-tests-lib.git`
2. set environment variable `WP_TESTS_DIR` to path to `wordpress-tests-lib` repo
3. configure `wp-tests-config.php` to use `wordpress-tests-lib` repo
4. install [PHPUnit Polyfills](https://github.com/Yoast/PHPUnit-Polyfills) - `composer require --dev yoast/phpunit-polyfills:"^4.0"`
5. set environment variable `WP_TESTS_PHPUNIT_POLYFILLS_PATH` to path to `phpunit-polyfills` directory
6. add some tests
7. run `grunt phpunit`


## Changelog

[CHANGELOG](CHANGELOG)
