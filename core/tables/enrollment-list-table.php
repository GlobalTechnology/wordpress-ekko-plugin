<?php namespace Ekko\Core\Tables {

	if ( ! class_exists( '\\WP_List_Table' ) )
		require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

	final class EnrollmentListTable extends \WP_List_Table {

		function __construct() {
			parent::__construct( array(
				'singular' => 'movie',
				'plural'   => 'movies',
				'ajax'     => false
			) );
		}

		function column_cb( \WP_User $item ) {
			return sprintf(
				'<input type="checkbox" name="students[]" value="%2$s" />',
				/*%1$s*/
				'user',
				/*%2$s*/
				$item->ID
			);
		}

		public function column_name( $item ) {
			echo $item->display_name;
		}

		public function column_email( $item ) {
			echo $item->user_email;
		}

		function get_columns() {
			$columns = array(
//				'cb'    => '<input type="checkbox" />',
				'name'  => 'Name',
				'email' => 'E-mail',
			);
			return $columns;
		}

		function prepare_items() {
			$this->_column_headers = array( $this->get_columns(), array(), array() );

			$post   = get_post( $_REQUEST[ 'post' ] );
			$course = \Ekko\Core\CoursePostType::singleton()->get_course( $post );
			$hub    = \Ekko\Core\Services\Hub::singleton();
			$thekey = \Ekko\Core\Services\TheKey::singleton();

			$this->items = array();
			$enrolled    = $hub->get_enrolled( $course->course_ID );
			$course->set_transient( 'enrolled_users', $enrolled, 15 * MINUTE_IN_SECONDS );
			foreach ( $enrolled as $guid ) {
				$user = $thekey->get_user_by_guid( $guid );
				if ( $user instanceof \WP_User ) {
					$this->items[ ] = $user;
				}
				else {
					if ( $attributes = $thekey->get_user_attributes_by_guid( $guid ) ) {
						$user = $thekey->create_user( $attributes->guid, array(
							'user_login'   => $attributes->email,
							'user_email'   => $attributes->email,
							'first_name'   => $attributes->firstName,
							'last_name'    => $attributes->lastName,
							'display_name' => "{$attributes->firstName} {$attributes->lastName}",
							'nickname'     => '',
						) );
						if ( $user instanceof \WP_User )
							$this->items[ ] = $user;
					};
				}
			}

			$this->set_pagination_args( array(
				'total_items' => count( $this->items ),
				'per_page'    => 10,
				'total_pages' => ceil( count( $this->items ) / 10 )
			) );
		}

	}

}
