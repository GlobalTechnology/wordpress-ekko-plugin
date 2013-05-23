<?php namespace Ekko\Core {

	final class CoursePostType extends \GTO\Framework\Posts\PostType {

		protected $post_type = 'ekko-course';

		final protected function get_post( \WP_Post $post ) {
			return new \Ekko\Core\CoursePost( $post );
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
			add_action( 'admin_action_ekko-publish', array( &$this, 'publish_to_ekko' ), 10, 0 );
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
			wp_register_script( 'ekko-media-library', \Ekko\PLUGIN_URL . '/js/media-frame.js', array( 'media-editor' ) );
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
			wp_localize_script( 'ekko-app', EkkoL10N, array(
				'api_url' => admin_url( '/admin-ajax.php' ),
			) );

			if( in_array( $hook_suffix, array( 'post.php', 'post-new.php' ) ) ) {
				wp_enqueue_media();
				wp_enqueue_style(  'ekko-app' );
				wp_enqueue_script( 'ekko-app' );
				wp_enqueue_script( 'ekko-media-library' );
			}
		}

		protected function metaboxes() {
			return array(
				\Ekko\Core\Metaboxes\CourseMetaMetabox::singleton(),
				\Ekko\Core\Metaboxes\CourseCreatorMetabox::singleton(),
				\Ekko\Core\Metaboxes\SaveMetabox::singleton(),
			);
		}

		final public function post_row_actions( $actions, $post ) {
			if( get_post_type( $post ) == $this->post_type() ) {
				if( array_key_exists( 'inline hide-if-no-js', $actions ) )
					unset( $actions[ 'inline hide-if-no-js' ] );
				$post_type_object = get_post_type_object( $this->post_type() );
				$actions['ekko-publish'] = "<a class='ekko-publish' title='Publish to Ekko' href='" . wp_nonce_url( admin_url( sprintf( $post_type_object->_edit_link . '&amp;action=ekko-publish', $post->ID ) ), 'ekko-publish_' . $post->ID ) . "'>" . __( 'Publish to Ekko' ) . "</a>";
			}
			return $actions;
		}

		final public function publish_to_ekko() {
			check_admin_referer( 'ekko-publish_' . $_REQUEST[ 'post' ] );

			$post = get_post( $_REQUEST[ 'post' ] );
			$course = $this->get_post( $post );

			$hub = \Ekko\Core\Services\Hub::singleton();
			global $logger;
			$logger->debug( 'Session: ' . $hub->get_session() );
			$course_id = $course->course_ID;
			if( $course_id === false )
				$course_id = $hub->create_course( $course->get_manifest() );
			else
				$hub->update_course( $course_id, $course->get_manifest() );
			$course->course_ID = $course_id;
			$logger->debug( "Course ID: {$course_id}" );

			$existing_resources = $hub->get_resources( $course_id );
			$resources = $course->resources;
			foreach( $resources as $resource_id => $resource ) {
				if( in_array( $resource->sha1, $existing_resources ) )
					continue;
				$hub->upload_resource( $course_id, $resource->file, $resource->type );
			}

			$hub->publish_course( $course_id );

			$admins = array();
			$users = array();
			foreach( get_users() as $user ) {
				$guid = strtolower( \WPGCXPlugin::singleton()->get_user_guid( $user ) );
				if( $user->has_cap( 'administrator' ) )
					$admins[] = $guid;
				else
					$users[] = $guid;
			}
			$hub->sync_admins( $course_id, $admins );
			$hub->sync_enrolled( $course_id, $users );

			wp_redirect( admin_url('edit.php') . '?post_type=' . $this->post_type() );
			exit();
		}

	}
}