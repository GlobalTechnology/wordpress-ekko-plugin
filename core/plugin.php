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

			add_action( 'set_user_role', array( &$this, 'user_role_changed' ), 5, 2 );
			add_action( 'remove_user_from_blog', array( &$this, 'user_removed_from_blog' ), 10, 2);
		}

		function user_role_changed($user_id, $role) {
			error_log( "User ({$user_id}) Role Changed: {$role}" );

			//Fetch users GUID and bail if GUEST
			$guid = \GlobalTechnology\CentralAuthenticationService\CASLogin::singleton()->get_user_guid( $user_id );
			if( $guid == 'GUEST' )
				return;

			//Fetch all EKKO courses
			$courses = CoursePostType::singleton()->get_courses();
			if( empty( $courses ) )
				return;

			$session = Services\Hub::singleton()->get_session( true );
			$admins = user_can( $user_id, 'administrator' ) ? array( $guid ) : array();
			foreach( $courses as $course ) {
				if( $course_ID = $course->course_ID ) {
					Services\Hub::singleton()->update_users( $course_ID, array( $guid ), array(), Services\Hub::ENDPOINT_ENROLLED, $session );
					Services\Hub::singleton()->update_users( $course_ID, $admins, array( $guid ), Services\Hub::ENDPOINT_ADMINS, $session );
				}
			}
		}

		function user_removed_from_blog( $user_id, $blog_id ) {
			error_log( "User ({$user_id}) Removed from Blog: {$blog_id}" );

			//Fetch users GUID and bail if GUEST
			$guid = \GlobalTechnology\CentralAuthenticationService\CASLogin::singleton()->get_user_guid( $user_id );
			if( $guid == 'GUEST' )
				return;

			//Fetch all EKKO courses
			$courses = CoursePostType::singleton()->get_courses();
			if( empty( $courses ) )
				return;

			$session = Services\Hub::singleton()->get_session( true );
			foreach( $courses as $course ) {
				if( $course_ID = $course->course_ID ) {
					Services\Hub::singleton()->update_users( $course_ID, array(), array( $guid ), Services\Hub::ENDPOINT_ENROLLED, $session );
					Services\Hub::singleton()->update_users( $course_ID, array(), array( $guid ), Services\Hub::ENDPOINT_ADMINS, $session );
				}
			}
		}

		public function add_ekko_image_sizes() {
			add_image_size( 'ekko-thumbnail', 150, 84, true );
			add_image_size( 'ekko-banner-thumbnail', 240, 135, true );
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