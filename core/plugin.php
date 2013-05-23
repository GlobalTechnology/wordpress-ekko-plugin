<?php namespace Ekko\Core {

	final class Plugin {
		/**
		 * Singleton instance
		 * @var Plugin
		 */
		private static $instance;

		/**
		 * Returns the Plugin singleton
		 * @return Plugin
		 */
		public static function singleton() {
			if( !isset( self::$instance ) ) {
				$class = __CLASS__;
				self::$instance = new $class();
			}
			return self::$instance;
		}

		/**
		 * Prevent cloning of the class
		 * @internal
		 */
		private function __clone() {}

		/**
		 * Constructor
		 */
		private function __construct() {
			$this->register_hooks();
		}

		/**
		 * Registers WordPress Actions and Filters
		 */
		private function register_hooks() {
			add_action( 'plugins_loaded', array( &$this, 'load_textdomain' ), 10, 0 );

			add_action( 'init', array( '\Ekko\Core\CoursePostType', 'singleton' ), 0, 0 );
			add_action( 'init', array( &$this, 'add_ekko_image_sizes' ), 10, 0 );
		}

		public function add_ekko_image_sizes() {
			add_image_size( 'ekko-thumbnail', 150, 84, true );
			add_image_size( 'ekko-banner', 1280, 720, true );
			add_image_size( 'ekko-image', 1280, 720, true );
		}

		/**
		 * Load the plugin localizations
		 */
		public function load_textdomain() {
			load_plugin_textdomain( \Ekko\TEXT_DOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		}
	}
}