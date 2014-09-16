<?php namespace Ekko\Core\Pages {

	/**
	 * Class EnrollmentPage
	 * @package Ekko\Core\Pages
	 *
	 * @method static \Ekko\Core\Pages\EnrollmentPage singleton()
	 */
	final class EnrollmentPage extends \GTO\Framework\Admin\AdminPage {

		const ENROLLMENT_COLUMN = 'enrollment';

		public static $enrollment_list;

		final protected function __construct() {
			$course_post_type = \Ekko\Core\CoursePostType::singleton()->post_type();

			// Students Column on Courses post type
			add_filter( "manage_{$course_post_type}_posts_columns", array( &$this, 'manage_posts_columns' ), 9, 1 );
			add_action( "manage_{$course_post_type}_posts_custom_column", array( &$this, 'manage_posts_custom_column' ), 10, 2 );

			add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_scripts' ), 10, 1 );

			add_submenu_page( null, __( 'Manage Course Enrollment', \Ekko\TEXT_DOMAIN ), 'Enrollment', 'edit_posts', 'ekko-enrollment', array( &$this, 'display_page' ) );

			add_action( 'load-ekko-course_page_ekko-enrollment', array( &$this, 'init_students_list' ), 0, 0 );
			add_action( 'load-ekko-course_page_ekko-enrollment', array( &$this, 'save_changes' ), 10, 0 );
		}

		final public function init_students_list() {
			$this->enrollment_list = new \Ekko\Core\Tables\EnrollmentListTable();

		}

		final public function enqueue_scripts( $hook_suffix ) {
			wp_register_style( 'ekko-enrollment', \Ekko\PLUGIN_URL . 'css/enrollment.css' );
			if ( 'ekko-course_page_ekko-enrollment' == $hook_suffix ) {
				wp_enqueue_style( 'ekko-enrollment' );
			}
		}

		/**
		 * @param array $posts_columns
		 *
		 * @return array
		 */
		final public function manage_posts_columns( array $posts_columns ) {
			$columns = array();
			foreach ( $posts_columns as $name => $value ) {
				if ( $name == 'date' )
					$columns[ self::ENROLLMENT_COLUMN ] = __( 'Enrollment', \Ekko\TEXT_DOMAIN );
				$columns[ $name ] = $value;
			}
			return $columns;
		}

		public function enrollment_page_url( $post_id ) {
			$course_post_type = \Ekko\Core\CoursePostType::singleton()->post_type();
			return admin_url( sprintf( 'edit.php?post=%d&post_type=%s&page=ekko-enrollment', $post_id, $course_post_type ) );
		}

		/**
		 * @param string $column_name
		 * @param int    $post_id
		 */
		final public function manage_posts_custom_column( $column_name, $post_id ) {
			if ( $column_name == self::ENROLLMENT_COLUMN ) {
				$course         = \Ekko\Core\CoursePostType::singleton()->get_course( $post_id );
				$enrollmentType = '';
				switch ( $course->enrollment_type ) {
					case $course::ENROLLMENT_PUBLIC:
						$enrollmentType = __( 'Public', \Ekko\TEXT_DOMAIN );
						break;
					case $course::ENROLLMENT_OPEN:
						$enrollmentType = __( 'Open Enrollment', \Ekko\TEXT_DOMAIN );
						break;
					case $course::ENROLLMENT_APPROVAL:
						$enrollmentType = __( 'Approval Needed', \Ekko\TEXT_DOMAIN );
						break;
					case $course::ENROLLMENT_MANAGED:
						$enrollmentType = __( 'Instructor Managed', \Ekko\TEXT_DOMAIN );
						break;
				}
				echo sprintf( '<div><a href="%s">%s</a></div>', $this->enrollment_page_url( $post_id ), $enrollmentType );
				if ( $course->is_published() && $course->enrollment_type != $course::ENROLLMENT_PUBLIC ) {
					$students = $course->enrolled_users;
					echo sprintf( '<div>%s: <em><a href="%s">%d</a></em></div>', __( 'Students', \Ekko\TEXT_DOMAIN ), $this->enrollment_page_url( $post_id ), count( $students ) );
				}
			}
		}

		final public function save_changes() {
			$post   = get_post( $_REQUEST[ 'post' ] );
			$course = \Ekko\Core\CoursePostType::singleton()->get_course( $post );
			switch ( $this->enrollment_list->current_action() ) {
				case 'enrollment-type':
					check_admin_referer( 'enrollment-type', '_wpnonce_enrollment-type' );
					$enrollment_type = stripslashes( $_POST[ 'enrollment-type' ] );

					//Save changes to Hub and update course with response
					$settings                = \Ekko\Core\Services\Hub::singleton()->update_settings( $course->course_ID, $course::enrollment_type_to_settings( $enrollment_type ) );
					$enrollment_type         = $course::settings_to_enrollment_type( $settings );
					$course->enrollment_type = $enrollment_type;

					wp_redirect( wp_get_referer() );
					exit;
					break;
			}
		}

		final public function display_page() {
			$this->enrollment_list->prepare_items();
			$post   = get_post( $_REQUEST[ 'post' ] );
			$course = \Ekko\Core\CoursePostType::singleton()->get_course( $post );
			?>
			<div class="wrap">

				<div id="icon-users" class="icon32"><br /></div>
				<h2>
					<?php _e( 'Enrollment', \Ekko\TEXT_DOMAIN ); ?>: <?php esc_html_e( $course->post_title ); ?>
					<a href="edit.php?post_type=ekko-course" class="add-new-h2">Return to Courses</a>
				</h2>

				<br class="clear" />

				<div id="col-container">

					<div id="col-right">
						<div class="col-wrap">
							<h3>Students</h3>

							<form id="posts-filter" action="" method="post">
								<input type="hidden" name="page" value="<?php echo $_REQUEST[ 'page' ] ?>" />
								<?php $this->enrollment_list->display(); ?>
								<br class="clear" />
							</form>
						</div>
					</div>

					<div id="col-left">
						<div class="col-wrap">
							<div class="form-wrap">
								<?php $enrollment_type = $course->enrollment_type; ?>
								<h3><?php _e( 'Enrollment Type', \Ekko\TEXT_DOMAIN ); ?></h3>

								<form id="enrollmenttype" method="post" action="edit.php?post_type=ekko-course&page=ekko-enrollment" class="validate">
									<input type="hidden" name="action" value="enrollment-type" />
									<input type="hidden" name="post" value="<?php echo $course->ID; ?>" />
									<?php wp_nonce_field( 'enrollment-type', '_wpnonce_enrollment-type' ); ?>

									<div class="form-field">
										<label>
											<input type="radio" name="enrollment-type" value="<?php echo $course::ENROLLMENT_PUBLIC; ?>" disabled<?php checked( $course::ENROLLMENT_PUBLIC, $enrollment_type ); ?>>
											<strong>&nbsp;<?php echo esc_html_x( 'Public', 'enrollment type', \Ekko\TEXT_DOMAIN ); ?></strong>

											<p><?php esc_html_e( 'Anyone may take this course without enrolling.', \Ekko\TEXT_DOMAIN ); ?></p>
										</label>
									</div>

									<div class="form-field">
										<label>
											<input type="radio" name="enrollment-type" value="<?php echo $course::ENROLLMENT_OPEN; ?>"<?php checked( $course::ENROLLMENT_OPEN, $enrollment_type ); ?>>
											<strong>&nbsp;<?php echo esc_html_x( 'Open Enrollment', 'enrollment type', \Ekko\TEXT_DOMAIN ); ?></strong>

											<p><?php esc_html_e( 'Students may enroll in this course.', \Ekko\TEXT_DOMAIN ); ?></p>
										</label>
									</div>

									<div class="form-field">
										<label>
											<input type="radio" name="enrollment-type" value="<?php echo $course::ENROLLMENT_APPROVAL; ?>"<?php checked( $course::ENROLLMENT_APPROVAL, $enrollment_type ); ?>>
											<strong>&nbsp;<?php echo esc_html_x( 'Approval Needed', 'enrollment type', \Ekko\TEXT_DOMAIN ); ?></strong>

											<p><?php esc_html_e( 'Students must request enrollment and be approved by instructors.', \Ekko\TEXT_DOMAIN ); ?></p>
										</label>
									</div>

									<div class="form-field">
										<label>
											<input type="radio" name="enrollment-type" value="<?php echo $course::ENROLLMENT_MANAGED; ?>"<?php checked( $course::ENROLLMENT_MANAGED, $enrollment_type ); ?>>
											<strong>&nbsp;<?php echo esc_html_x( 'Instructor Managed', 'enrollment type', \Ekko\TEXT_DOMAIN ); ?></strong>

											<p><?php esc_html_e( 'Instructors will manage enrollment of students.', \Ekko\TEXT_DOMAIN ); ?></p>
										</label>
									</div>
									<?php submit_button( __( 'Update Enrollment Type', \Ekko\TEXT_DOMAIN ) ); ?>
								</form>
							</div>
						</div>
					</div>

				</div>

			</div>
		<?php
		}
	}
}
