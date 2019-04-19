<?php
/**
 * PHPUnit bootstrap file
 *
 * @package multiple-domain
 */

define('PROJECT_DIR', dirname(dirname(__FILE__)));

require PROJECT_DIR . '/vendor/autoload.php';

$_testsDir = getenv('WP_TESTS_DIR');

if (!$_testsDir) {
    $_testsDir = rtrim(sys_get_temp_dir(), '/\\') . '/wordpress-tests-lib';
}

if (!file_exists($_testsDir . '/includes/functions.php')) {
    printf('Could not find %s/includes/functions.php, have you run bin/install-wp-tests.sh ?%s', $_testsDir, PHP_EOL);
    exit(1);
}

/*
 * Give access to tests_add_filter() function.
 */
require_once $_testsDir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin()
{
    require PROJECT_DIR . '/multiple-domain/multiple-domain.php';
}

tests_add_filter('muplugins_loaded', '_manually_load_plugin');

/*
 * Start up the WP testing environment.
 */
require $_testsDir . '/includes/bootstrap.php';
