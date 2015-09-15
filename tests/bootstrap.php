<?php

$_tests_dir = getenv('WP_TESTS_DIR');
if ( !$_tests_dir ) $_tests_dir = '/tmp/wordpress-tests-lib';

require_once $_tests_dir . '/includes/functions.php';

// Activates this plugin in WordPress so it can be tested.
$GLOBALS['wp_tests_options'] = array(
    'active_plugins' => array('avh-em-permalinks/avh-em-permalinks.php'),
);

require $_tests_dir . '/includes/bootstrap.php';

class avh_em_permalinks_TestCase extends WP_UnitTestCase {
	function plugin() {
		return avh_em_permalinks_Plugin::get_instance();
	}

	function set_post( $key, $value ) {
		$_POST[$key] = $_REQUEST[$key] = addslashes( $value );
	}

	function unset_post( $key ) {
		unset( $_POST[$key], $_REQUEST[$key] );
	}
}
