<?php namespace Ekko\Core {

	final class CoursePostType extends \GTO\Framework\Posts\PostType {

		protected $post_type = 'ekko-course';

		protected $remove_featured_image_metabox = true;

		final protected function get_post( \WP_Post $post ) {
			return new \Ekko\Core\CoursePost( $post );
		}

		final public function get_course( $post ) {
			$post = get_post( $post );
			if( $post instanceof \WP_Post && get_post_type( $post ) == $this->post_type() )
				return $this->get_post( $post );
			return null;
		}

		final public function get_courses() {
			$query = new \WP_Query( array(
				'post_type' => $this->post_type(),
				'post_status' => 'publish',
				'nopaging'    => true,
			) );
			$courses = array();
			foreach( $query->posts as $post )
				$courses[] = $this->get_course( $post );
			return $courses;
		}

		protected function post_type_args() {
			return array(
				'labels' => array(
					'name'               => __( 'Courses',                   \Ekko\TEXT_DOMAIN ),
					'singular_name'      => __( 'Course',                    \Ekko\TEXT_DOMAIN ),
					'add_new'            => _x( 'Add New Course', $this->post_type, \Ekko\TEXT_DOMAIN ),
					'add_new_item'       => __( 'Add New Course',            \Ekko\TEXT_DOMAIN ),
					'edit_item'          => __( 'Edit Course',               \Ekko\TEXT_DOMAIN ),
					'new_item'           => __( 'New Course',                \Ekko\TEXT_DOMAIN ),
					'view_item'          => __( 'View Course',               \Ekko\TEXT_DOMAIN ),
					'search_items'       => __( 'Search Courses',            \Ekko\TEXT_DOMAIN ),
					'not_found'          => __( 'No Courses Found',          \Ekko\TEXT_DOMAIN ),
					'not_found_in_trash' => __( 'No Courses found in Trash', \Ekko\TEXT_DOMAIN ),
					'parent_item_colon'  => __( 'Parent Course:',            \Ekko\TEXT_DOMAIN ),
					'all_items'          => __( 'All Courses',               \Ekko\TEXT_DOMAIN ),
					'menu_name'          => __( 'Ekko',                      \Ekko\TEXT_DOMAIN ),
				),
				'supports' => array(
					'title',
					'thumbnail',
				),
				'description'          => __( 'Ekko Courses', \Ekko\TEXT_DOMAIN ),
//				'menu_icon'            => \Ekko\PLUGIN_URL . 'images/icons/book.png',
			);
		}

		final protected function register_hooks() {
			add_action( 'post_row_actions', array( &$this, 'post_row_actions' ), 10, 2 );
			add_action( 'admin_menu', array( &$this, 'add_admin_menus' ), 10, 0 );
			add_action( 'load-ekko-course_page_ekko-publish', array( &$this, 'check_publish' ), 10, 0 );
			add_action( 'print_media_templates', array( &$this, 'media_templates' ), 10, 0 );
			add_action( 'dbx_post_sidebar', array( &$this, 'course_templates' ), 10, 0 );

			add_action( 'redirect_post_location', array( &$this, 'redirect_post' ), 10, 2 );
		}

		final protected function post_type_title() {
			return __( 'Enter course title here', \Ekko\TEXT_DOMAIN );
		}

		protected function enqueue_admin_scripts( $hook_suffix ) {
			/* Register Scripts and Styles */
			//Bootstrap
			wp_register_style(  'bootstrap', \Ekko\PLUGIN_URL . 'lib/bootstrap/css/bootstrap.css' );
			wp_register_script( 'bootstrap', \Ekko\PLUGIN_URL . 'lib/bootstrap/js/bootstrap.js', array( 'jquery' ), null, false );

			//Bootstrap Switch
			wp_register_style(  'bootstrap-switch', \Ekko\PLUGIN_URL . 'lib/bootstrapSwitch/bootstrapSwitch.css', array( 'bootstrap' ) );
			wp_register_script( 'bootstrap-switch', \Ekko\PLUGIN_URL . 'lib/bootstrapSwitch/bootstrapSwitch.js', array( 'jquery' ) );

			//AngularJS
			wp_register_script( 'angular', \Ekko\PLUGIN_URL . 'lib/angular/angular.js', array(), null, false );
			wp_register_script( 'angular-bootstrap', \Ekko\PLUGIN_URL . 'lib/angular-bootstrap/ui-bootstrap-tpls.js', array( 'angular' ), null, false );

			//CKEditor
			wp_register_script( 'ckeditor', \Ekko\PLUGIN_URL . 'lib/ckeditor/ckeditor.js', array(), null, false );

			//Angular UI
			wp_register_script( 'angular-ui-ieshiv', \Ekko\PLUGIN_URL . 'lib/angular-ui/angular-ui-ieshiv.js', array(), null, false );
			wp_register_script( 'angular-ui', \Ekko\PLUGIN_URL . 'lib/angular-ui/angular-ui.js', array( 'angular-ui-ieshiv', 'jquery-ui-sortable', 'ckeditor', 'angular' ), null, false );

			//Ekko Course Creator
			wp_register_style(  'ekko-app', \Ekko\PLUGIN_URL . 'css/course.css', array( 'bootstrap', 'bootstrap-switch' ) );
			wp_register_script( 'ekko-media', \Ekko\PLUGIN_URL . '/js/media.js', array( 'media-editor' ) );
			wp_register_script( 'ekko-app-controllers', \Ekko\PLUGIN_URL . 'js/controllers.js', array( 'angular' ) );
			wp_register_script( 'ekko-app-services', \Ekko\PLUGIN_URL . 'js/services.js', array( 'angular' ) );
			wp_register_script( 'ekko-app-directives', \Ekko\PLUGIN_URL . 'js/directives.js', array( 'angular' ) );
			wp_register_script( 'ekko-app', \Ekko\PLUGIN_URL . 'js/ekko-app.js', array(
				'bootstrap',
				'bootstrap-switch',
				'angular-ui',
				'angular-bootstrap',
				'ckeditor',
				'ekko-app-controllers',
				'ekko-app-services',
				'ekko-app-directives',
			), null, false );
			wp_localize_script( 'ekko-app', '_EkkoAppL10N', array(
				'api_url' => admin_url( '/admin-ajax.php' ),
				'l10n' => array(
					'setBanner'         => __( 'Set banner image', \Ekko\TEXT_DOMAIN ),
					'setBannerTitle'    => __( 'Set Banner Image', \Ekko\TEXT_DOMAIN ),
					'addMediaTitle'     => __( 'Add Media', \Ekko\TEXT_DOMAIN ),
					'addMedia'          => __( 'Add media', \Ekko\TEXT_DOMAIN ),
					'addThumbnailTitle' => __( 'Select a Thumbnail Image', \Ekko\TEXT_DOMAIN ),
					'youTubeTitle'      => __( 'YouTube', \Ekko\TEXT_DOMAIN ),
					'vimeoTitle'        => __( 'Vimeo', \Ekko\TEXT_DOMAIN ),
				)
			) );

			if( in_array( $hook_suffix, array( 'post.php', 'post-new.php' ) ) ) {
				wp_enqueue_media();
				wp_enqueue_style(  'ekko-app' );
				wp_enqueue_script( 'ekko-app' );
				wp_enqueue_script( 'ekko-media' );
			}
		}

		protected function metaboxes() {
			return array(
				\Ekko\Core\Metaboxes\CourseMetaMetabox::singleton(),
				\Ekko\Core\Metaboxes\CourseCreatorMetabox::singleton(),
				\Ekko\Core\Metaboxes\BannerMetabox::singleton(),
				\Ekko\Core\Metaboxes\SaveMetabox::singleton(),
				\Ekko\Core\Metaboxes\CompleteMetabox::singleton(),
			);
		}

		final public function media_templates() {
			include( \Ekko\PLUGIN_DIR . 'templates/media-template.php' );
		}

		final public function course_templates() {
			include( \Ekko\PLUGIN_DIR . 'templates/course-template.php' );
		}

		final public function redirect_post( $location, $post_id ) {
			if( get_post_type( $post_id ) == $this->post_type() ) {
				if( isset( $_POST[ 'save' ] ) ) {
					$location = remove_query_arg( 'message', $location );
					return add_query_arg( 'message', 4, $location );
				}
				elseif( isset( $_POST[ 'publish' ] ) ) {
					return admin_url( sprintf( 'edit.php?post=%d&post_type=%s&page=ekko-publish&_wpnonce=%s', $post_id, $this->post_type(), wp_create_nonce( 'ekko-publish_' . $post_id ) ) );
				}
			}
			return $location;
		}

		final public function post_row_actions( $actions, $post ) {
			if( get_post_type( $post ) == $this->post_type() ) {
				if( array_key_exists( 'inline hide-if-no-js', $actions ) )
					unset( $actions[ 'inline hide-if-no-js' ] );
				$actions['ekko-publish'] = "<a class='ekko-publish' title='" . esc_attr__( 'Publish to Ekko', \Ekko\TEXT_DOMAIN ) . "' href='" . wp_nonce_url( admin_url( sprintf( 'edit.php?post=%d&amp;post_type=%s&amp;page=ekko-publish', $post->ID, $this->post_type() ) ), 'ekko-publish_' . $post->ID ) . "'>" . __( 'Publish to Ekko', \Ekko\TEXT_DOMAIN ) . "</a>";
			}
			return $actions;
		}

		final public function add_admin_menus() {
			add_submenu_page( 'edit.php?post_type=ekko-course', __( 'Publish Course to EKKO', \Ekko\TEXT_DOMAIN ), null, 'edit_posts', 'ekko-publish', array( &$this, 'publish_to_ekko' ) );
		}

		final public function check_publish() {
			if( ! wp_verify_nonce( $_REQUEST[ '_wpnonce' ], 'ekko-publish_' . $_REQUEST[ 'post' ] ) )
				wp_die( __( 'Cheatin&#8217; uh?', \Ekko\TEXT_DOMAIN ) );
		}

		final public function publish_to_ekko() {
			echo '<div class="wrap"><div id="icon-post" class="icon32"><br></div><h2>' . esc_html__( 'Publishing Course to EKKO', \Ekko\TEXT_DOMAIN ) . '</h2></div>';
			$post = get_post( $_REQUEST[ 'post' ] );
			$course = $this->get_post( $post );
			echo sprintf( '<h4>%2$s: %1$s</h4>', $course->post_title, esc_html__( 'Publishing', \Ekko\TEXT_DOMAIN ) );

			$hub = \Ekko\Core\Services\Hub::singleton();
			$course_id = $course->course_ID;

			if( $course_id === false )
				$course_id = $hub->create_course( $course->get_manifest() );
			else
				$hub->update_course( $course_id, $course->get_manifest() );
			echo sprintf( '<p>%1$s</p>', esc_html__( 'Course manifest updated', \Ekko\TEXT_DOMAIN ) );
			$course->course_ID = $course_id;

			$existing_resources = $hub->get_resources( $course_id );
			$resources = $course->resources;
			echo '<p>' . esc_html__( 'Uploading Media', \Ekko\TEXT_DOMAIN ) . ':<ul>';
			foreach( $resources as $resource ) {
				if( $resource->type == 'file' ) {
					echo sprintf( '<li>%1$s</li>', basename( $resource->file ) );
					if( in_array( $resource->sha1, $existing_resources ) )
						continue;
					$hub->upload_resource( $course_id, $resource->file, $resource->mimeType );
				}
			}
			echo '</ul></p>';

			$result = $hub->publish_course( $course_id );
			if( $result !== true ) {
				echo '<p><strong>' . esc_html__( 'One or more Errors occured during publish', \Ekko\TEXT_DOMAIN ) . ':</strong>';
				if( is_array( $result ) )
					foreach( $result as $error )
						echo '<div>' . esc_html( $error ) . '</div>';
				echo '</p>';
			}
			else
				echo '<p><strong>' . esc_html__( 'Course successfully published!', \Ekko\TEXT_DOMAIN ) . '</strong></p>';

			$admins = array();
			$users = array();
			foreach( get_users() as $user ) {
				$class = ( class_exists( '\\GlobalTechnology\\CentralAuthenticationService\\CASLogin' ) ) ?
					\GlobalTechnology\CentralAuthenticationService\CASLogin::singleton() : \WPGCXPlugin::singleton();
				$guid = $class->get_user_guid( $user );
				if( $user->has_cap( 'administrator' ) )
					$admins[] = $guid;
				else
					$users[] = $guid;
			}
			$hub->sync_admins( $course_id, $admins );
			$hub->sync_enrolled( $course_id, $users );

			?><a href="<?php echo admin_url( 'edit.php?post_type=ekko-course' ); ?>"><?php esc_html__( 'Return to Courses page', \Ekko\TEXT_DOMAIN ); ?></a><?php
		}

	}
}