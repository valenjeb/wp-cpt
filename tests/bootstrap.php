<?php

declare(strict_types=1);

/**
 * PHPUnit bootstrap file.
 *
 * phpcs:disable Squiz.Functions.GlobalFunction.Found
 * phpcs:disable Squiz.NamingConventions.ValidVariableName.NotCamelCaps
 */

$_tests_dir = getenv('WP_TESTS_DIR');

if (! $_tests_dir) {
    $_tests_dir = rtrim(sys_get_temp_dir(), '/\\') . '/wordpress-tests-lib';
}

// Forward custom PHPUnit Polyfills configuration to PHPUnit bootstrap file.
$_phpunit_polyfills_path = getenv('WP_TESTS_PHPUNIT_POLYFILLS_PATH');
if ($_phpunit_polyfills_path !== false) {
    define('WP_TESTS_PHPUNIT_POLYFILLS_PATH', $_phpunit_polyfills_path);
}

if (! file_exists($_tests_dir . '/includes/functions.php')) {
    $message = sprintf(
        'Could not find %s/includes/functions.php, have you run bin/install-wp-tests.sh ?' . PHP_EOL,
        $_tests_dir
    );

    exit(1);
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin(): void
{
    require dirname(__FILE__, 2) . '/vendor/autoload.php';
}

tests_add_filter('muplugins_loaded', '_manually_load_plugin');

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';
