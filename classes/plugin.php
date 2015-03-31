<?php
defined( 'WPINC' ) or die;

class avh_em_permalinks_Plugin extends WP_Stack_Plugin2 {

	/**
	 * Constructs the object, hooks in to 'plugins_loaded'
	 */
	protected function __construct() {
		$this->hook( 'plugins_loaded', 'add_hooks' );
	}

	/**
	 * Adds hooks
	 */
	public function add_hooks() {
		$this->hook( 'init' );
		// Add your hooks here
	}

	/**
	 * Initializes the plugin, registers textdomain, etc
	 */
	public function init() {
		$this->load_textdomain( 'avh-em-permalinks', '/languages' );
	}
}
