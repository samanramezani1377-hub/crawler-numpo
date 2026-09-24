<?php
if (!defined('TESTS_PLUGIN_DIR')) define('TESTS_PLUGIN_DIR', dirname(__DIR__));
$tests_dir = getenv('WP_TESTS_DIR');
if (!$tests_dir) $tests_dir = '/tmp/wordpress-tests-lib';
if (!file_exists($tests_dir . '/includes/functions.php')) {
    fwrite(STDERR, "WordPress test library not found at {$tests_dir}.\n");
    exit(1);
}
require_once $tests_dir . '/includes/functions.php';
tests_add_filter('muplugins_loaded', function () {
    require TESTS_PLUGIN_DIR . '/numpo.php';
});
require $tests_dir . '/includes/bootstrap.php';
