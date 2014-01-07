<?php namespace Ekko\Core\Pages {

	/**
	 * Class PublishPage
	 * @package Ekko\Core\Pages
	 * @method static \Ekko\Core\Pages\PublishPage singleton()
	 */
	final class PublishPage extends \GTO\Framework\Admin\AdminPage {

		final protected function __construct() {
			add_action( 'load-ekko-course_page_ekko-publish', array( &$this, 'check_publish' ), 10, 0 );
			add_submenu_page( null, __( 'Publish Course to EKKO', \Ekko\TEXT_DOMAIN ), null, 'edit_posts', 'ekko-publish', array( &$this, 'display_page' ) );
			add_action( 'post_row_actions', array( &$this, 'post_row_actions' ), 10, 2 );
			add_action( 'redirect_post_location', array( &$this, 'redirect_post' ), 10, 2 );
			add_action( 'admin_enqueue_scripts', array( &$this, 'admin_enqueue_scripts' ), 10, 1 );
		}

		public function admin_enqueue_scripts() {
			if ( get_current_screen()->id == 'ekko-course_page_ekko-publish' )
				wp_enqueue_style( 'bootstrap' );
		}

		final public function check_publish() {
			if ( ! wp_verify_nonce( $_REQUEST[ '_wpnonce' ], 'ekko-publish_' . $_REQUEST[ 'post' ] ) )
				wp_die( __( 'Cheatin&#8217; uh?', \Ekko\TEXT_DOMAIN ) );
		}

		final private function get_nonce_url( $post_id ) {
			$course_post_type = \Ekko\Core\CoursePostType::singleton()->post_type();
			return admin_url( sprintf( 'edit.php?post=%d&post_type=%s&page=ekko-publish&_wpnonce=%s', $post_id, $course_post_type, wp_create_nonce( 'ekko-publish_' . $post_id ) ) );
		}

		/**
		 * Redirect browser to Ekko Course Publish page on publish action
		 *
		 * @param string $location
		 * @param int    $post_id
		 *
		 * @return string
		 */
		final public function redirect_post( $location, $post_id ) {
			$course_post_type = \Ekko\Core\CoursePostType::singleton()->post_type();
			if ( get_post_type( $post_id ) == $course_post_type ) {
				if ( isset( $_POST[ 'publish' ] ) ) {
					return $this->get_nonce_url( $post_id );
				}
			}
			return $location;
		}

		/**
		 * @access private
		 *
		 * @param $actions
		 * @param $post
		 *
		 * @return mixed
		 */
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
			$hub       = \Ekko\Core\Services\Hub::singleton();
			$post      = get_post( $_REQUEST[ 'post' ] );
			$course    = \Ekko\Core\CoursePostType::singleton()->get_course( $post );
			$course_id = $course->course_ID;
			?>
			<div class="wrap">
				<div id="icon-post" class="icon32"><br /></div>
				<h2>
					<?php _e( 'Publish Course', \Ekko\TEXT_DOMAIN ); ?>
					<a href="edit.php?post_type=ekko-course" class="add-new-h2">Return to Courses</a>
				</h2>

				<div class="ekko-bootstrap">
					<div><h1><?php _e( sprintf( 'Publishing Course: %1$s', $course->post_title ) ); ?></h1></div>
					<div class="well">
						<p><?php
							if ( $course_id === false )
								$course_id = $hub->create_course( $course->get_manifest() );
							else
								$hub->update_course( $course_id, $course->get_manifest() );
							$course->course_ID = $course_id;
							esc_html_e( 'Updating Course Manifest.', \Ekko\TEXT_DOMAIN );
							?>
						</p>

						<p><?php
							esc_html_e( 'Uploading Course Media:', \Ekko\TEXT_DOMAIN );
							$existing_resources = $hub->get_resources( $course_id );
							$resources = $course->resources;
							?>
						<ul><?php
							foreach ( $resources as $resource ) {
								if ( $resource->type == 'file' ) {
									echo sprintf( '<li>%1$s</li>', basename( $resource->file ) );
									if ( ! in_array( $resource->sha1, $existing_resources[ 'files' ] ) ) {
										$hub->upload_resource( $course_id, $resource->file, $resource->mimeType );
									}
								}
								elseif ( $resource->type == 'ecv' ) {
									if ( ! in_array( $resource->ecv_id, $existing_resources[ 'videos' ] ) ) {
										$hub->add_course_to_video( $resource->ecv_id, $course_id );
									}
								}
							}
							?>
						</ul>
						</p><?php $result = $hub->publish_course( $course_id ); ?>

						<p class="<?php echo $result === true ? 'text-success' : 'text-error'; ?>"><?php
							if ( $result === true ) {
								esc_html_e( 'Course successfully published!', \Ekko\TEXT_DOMAIN );
							}
							else {
								echo '<strong>' . esc_html__( 'One or more Errors occured during publish', \Ekko\TEXT_DOMAIN ) . ':</strong>';
								if ( is_array( $result ) ) {
									foreach ( $result as $error ) {
										echo '<div>' . esc_html( $error ) . '</div>';
									}
								}
							}
							?>
						</p>

					</div>
				</div>
			</div>
			<?php
			$admins = array();
			foreach ( get_users() as $user ) {
				$class = ( class_exists( '\\GlobalTechnology\\CentralAuthenticationService\\CASLogin' ) ) ?
					\GlobalTechnology\CentralAuthenticationService\CASLogin::singleton() : \WPGCXPlugin::singleton();
				$guid  = $class->get_user_guid( $user );
				if ( $user->has_cap( 'administrator' ) )
					$admins[ ] = $guid;
			}
			$hub->sync_admins( $course_id, $admins );
		}
	}
}
