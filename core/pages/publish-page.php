<?php namespace Ekko\Core\Pages {

	final class PublishPage extends \GTO\Framework\Admin\AdminPage {

		final protected function __construct() {
			add_action( 'load-ekko-course_page_ekko-publish', array( &$this, 'check_publish' ), 10, 0 );
			add_submenu_page( 'edit.php?post_type=ekko-course', __( 'Publish Course to EKKO', \Ekko\TEXT_DOMAIN ), null, 'edit_posts', 'ekko-publish', array( &$this, 'display_page' ) );
			add_action( 'post_row_actions', array( &$this, 'post_row_actions' ), 10, 2 );
			add_action( 'redirect_post_location', array( &$this, 'redirect_post' ), 10, 2 );
		}

		final public function check_publish() {
			if ( ! wp_verify_nonce( $_REQUEST[ '_wpnonce' ], 'ekko-publish_' . $_REQUEST[ 'post' ] ) )
				wp_die( __( 'Cheatin&#8217; uh?', \Ekko\TEXT_DOMAIN ) );
		}

		final private function get_nonce_url( $post_id ) {
			$course_post_type = \Ekko\Core\CoursePostType::singleton()->post_type();
			return admin_url( sprintf( 'edit.php?post=%d&post_type=%s&page=ekko-publish&_wpnonce=%s', $post_id, $course_post_type, wp_create_nonce( 'ekko-publish_' . $post_id ) ) );
		}

		final public function redirect_post( $location, $post_id ) {
			$course_post_type = \Ekko\Core\CoursePostType::singleton()->post_type();
			if ( get_post_type( $post_id ) == $course_post_type ) {
				if ( isset( $_POST[ 'publish' ] ) ) {
					return $this->get_nonce_url( $post_id );
				}
			}
			return $location;
		}

		final public function post_row_actions( $actions, $post ) {
			$course_post_type = \Ekko\Core\CoursePostType::singleton()->post_type();
			if ( get_post_type( $post ) == $course_post_type ) {
				if ( array_key_exists( 'inline hide-if-no-js', $actions ) )
					unset( $actions[ 'inline hide-if-no-js' ] );
				$actions[ 'ekko-publish' ] =
					"<a class='ekko-publish' title='" . esc_attr__( 'Publish to Ekko', \Ekko\TEXT_DOMAIN ) .
					"' href='" . esc_attr( $this->get_nonce_url( $post->ID ) ) . "'>" .
					__( 'Publish to Ekko', \Ekko\TEXT_DOMAIN ) . "</a>";
			}
			return $actions;
		}

		final public function display_page() {
			echo '<div class="wrap"><div id="icon-post" class="icon32"><br></div><h2>' . esc_html__( 'Publishing Course to EKKO', \Ekko\TEXT_DOMAIN ) . '</h2></div>';
			$post   = get_post( $_REQUEST[ 'post' ] );
			$course = \Ekko\Core\CoursePostType::singleton()->get_course( $post );
			echo sprintf( '<h4>%2$s: %1$s</h4>', $course->post_title, esc_html__( 'Publishing', \Ekko\TEXT_DOMAIN ) );

			$hub       = \Ekko\Core\Services\Hub::singleton();
			$course_id = $course->course_ID;

			if ( $course_id === false )
				$course_id = $hub->create_course( $course->get_manifest() );
			else
				$hub->update_course( $course_id, $course->get_manifest() );
			echo sprintf( '<p>%1$s</p>', esc_html__( 'Course manifest updated', \Ekko\TEXT_DOMAIN ) );
			$course->course_ID = $course_id;

			$existing_resources = $hub->get_resources( $course_id );
			$resources          = $course->resources;
			echo '<p>' . esc_html__( 'Uploading Media', \Ekko\TEXT_DOMAIN ) . ':<ul>';
			foreach ( $resources as $resource ) {
				if ( $resource->type == 'file' ) {
					echo sprintf( '<li>%1$s</li>', basename( $resource->file ) );
					if ( in_array( $resource->sha1, $existing_resources ) )
						continue;
					$hub->upload_resource( $course_id, $resource->file, $resource->mimeType );
				}
			}
			echo '</ul></p>';

			$result = $hub->publish_course( $course_id );
			if ( $result !== true ) {
				echo '<p><strong>' . esc_html__( 'One or more Errors occured during publish', \Ekko\TEXT_DOMAIN ) . ':</strong>';
				if ( is_array( $result ) )
					foreach ( $result as $error ) {
						echo '<div>' . esc_html( $error ) . '</div>';
					}
				echo '</p>';
			}
			else
				echo '<p><strong>' . esc_html__( 'Course successfully published!', \Ekko\TEXT_DOMAIN ) . '</strong></p>';

			$settings = array( 'public' => 'true', 'enrollmentType' => '' );
			switch ( $course->enrollment->type ) {
				case $course::ENROLLMENT_PUBLIC:
					$settings[ 'enrollmentType' ] = 'disabled';
					break;
				case $course::ENROLLMENT_OPEN:
					$settings[ 'enrollmentType' ] = 'open';
					break;
				case $course::ENROLLMENT_APPROVAL:
					$settings[ 'enrollmentType' ] = 'approval';
					break;
				case $course::ENROLLMENT_MANAGED:
					$settings[ 'public' ]         = 'false';
					$settings[ 'enrollmentType' ] = 'approval';
					break;
			}
			$hub->update_settings( $course_id, $settings );

			$admins = array();
			foreach ( get_users() as $user ) {
				$class = ( class_exists( '\\GlobalTechnology\\CentralAuthenticationService\\CASLogin' ) ) ?
					\GlobalTechnology\CentralAuthenticationService\CASLogin::singleton() : \WPGCXPlugin::singleton();
				$guid  = $class->get_user_guid( $user );
				if ( $user->has_cap( 'administrator' ) )
					$admins[ ] = $guid;
			}
			$hub->sync_admins( $course_id, $admins );

			?>
			<a href="<?php echo admin_url( 'edit.php?post_type=ekko-course' ); ?>"><?php esc_html__( 'Return to Courses page', \Ekko\TEXT_DOMAIN ); ?></a><?php
		}
	}
}
