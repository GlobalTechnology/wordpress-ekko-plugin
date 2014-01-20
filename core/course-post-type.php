<?php namespace Ekko\Core {

	/**
	 * Class CoursePostType
	 * @package Ekko\Core
	 * @method static \Ekko\Core\CoursePostType singleton()
	 */
	final class CoursePostType extends \GTO\Framework\Posts\PostType {

		protected $post_type = 'ekko-course';

		protected $remove_featured_image_metabox = true;

		/**
		 * @param \WP_Post $post
		 *
		 * @return CoursePost|\GTO\Framework\Posts\Post
		 */
		final protected function get_post( \WP_Post $post ) {
			return new \Ekko\Core\CoursePost( $post );
		}

		/**
		 * @param $post
		 *
		 * @return CoursePost|null
		 */
		final public function get_course( $post ) {
			$post = get_post( $post );
			if ( $post instanceof \WP_Post && get_post_type( $post ) == $this->post_type() )
				return $this->get_post( $post );
			return null;
		}

		final public function get_courses() {
			$query   = new \WP_Query( array(
				'post_type'   => $this->post_type(),
				'post_status' => 'publish',
				'nopaging'    => true,
			) );
			$courses = array();
			foreach ( $query->posts as $post ) {
				$courses[ ] = $this->get_course( $post );
			}
			return $courses;
		}

		protected function post_type_args() {
			return array(
				'labels'      => array(
					'name'               => __( 'Courses', \Ekko\TEXT_DOMAIN ),
					'singular_name'      => __( 'Course', \Ekko\TEXT_DOMAIN ),
					'add_new'            => _x( 'Add New Course', $this->post_type, \Ekko\TEXT_DOMAIN ),
					'add_new_item'       => __( 'Add New Course', \Ekko\TEXT_DOMAIN ),
					'edit_item'          => __( 'Edit Course', \Ekko\TEXT_DOMAIN ),
					'new_item'           => __( 'New Course', \Ekko\TEXT_DOMAIN ),
					'view_item'          => __( 'View Course', \Ekko\TEXT_DOMAIN ),
					'search_items'       => __( 'Search Courses', \Ekko\TEXT_DOMAIN ),
					'not_found'          => __( 'No Courses Found', \Ekko\TEXT_DOMAIN ),
					'not_found_in_trash' => __( 'No Courses found in Trash', \Ekko\TEXT_DOMAIN ),
					'parent_item_colon'  => __( 'Parent Course:', \Ekko\TEXT_DOMAIN ),
					'all_items'          => __( 'All Courses', \Ekko\TEXT_DOMAIN ),
					'menu_name'          => __( 'Ekko', \Ekko\TEXT_DOMAIN ),
				),
				'supports'    => array(
					'title',
					'thumbnail',
				),
				'description' => __( 'Ekko Courses', \Ekko\TEXT_DOMAIN ),
//				'menu_icon'            => \Ekko\PLUGIN_URL . 'images/icons/book.png',
			);
		}

		final protected function register_hooks() {
			add_action( 'admin_menu', array( '\Ekko\Core\Pages\PublishPage', 'singleton' ), 10, 0 );
			add_action( 'admin_menu', array( '\Ekko\Core\Pages\EnrollmentPage', 'singleton' ), 10, 0 );
			add_action( 'admin_menu', array( '\Ekko\Core\Pages\VideoPage', 'singleton' ), 10, 0 );

			add_action( 'admin_init', array( '\Ekko\Core\Managers\CloudManager', 'singleton' ), 0, 0 );
			add_action( 'admin_init', array( '\Ekko\Core\Managers\ArclightManager', 'singleton' ), 0, 0 );

			add_action( 'dbx_post_sidebar', array( &$this, 'course_templates' ), 10, 0 );
			add_action( 'before_delete_post', array( &$this, 'delete_post' ), 10, 1 );

			add_filter( "manage_{$this->post_type()}_posts_columns", array( &$this, 'manage_posts_columns' ), 10, 1 );
			add_action( "manage_{$this->post_type()}_posts_custom_column", array( &$this, 'manage_posts_custom_column' ), 10, 2 );

			add_action( 'redirect_post_location', array( &$this, 'redirect_post' ), 10, 2 );
		}

		final protected function post_type_title() {
			return __( 'Enter course title here', \Ekko\TEXT_DOMAIN );
		}

		protected function enqueue_admin_scripts( $hook_suffix ) {
			/* Register Scripts and Styles */
			//Bootstrap
			wp_register_style( 'bootstrap', \Ekko\PLUGIN_URL . 'lib/bootstrap/css/bootstrap.css' );
			wp_register_script( 'bootstrap', \Ekko\PLUGIN_URL . 'lib/bootstrap/js/bootstrap.js', array( 'jquery' ), null, false );

			//Bootstrap Switch
			wp_register_style( 'bootstrap-switch', \Ekko\PLUGIN_URL . 'lib/bootstrapSwitch/bootstrap-switch.css', array( 'bootstrap' ) );
			wp_register_script( 'bootstrap-switch', \Ekko\PLUGIN_URL . 'lib/bootstrapSwitch/bootstrap-switch.js', array( 'jquery' ) );

			//AngularJS
			wp_register_script( 'angular', \Ekko\PLUGIN_URL . 'lib/angular/angular.js', array(), null, false );
			wp_register_script( 'angular-bootstrap', \Ekko\PLUGIN_URL . 'lib/angular-bootstrap/ui-bootstrap-tpls.js', array( 'angular' ), null, false );

			//CKEditor
			wp_register_script( 'ckeditor', \Ekko\PLUGIN_URL . 'lib/ckeditor/ckeditor.js', array(), null, false );

			//Angular UI
			wp_register_script( 'angular-ui-ieshiv', \Ekko\PLUGIN_URL . 'lib/angular-ui/angular-ui-ieshiv.js', array(), null, false );
			wp_register_script( 'angular-ui', \Ekko\PLUGIN_URL . 'lib/angular-ui/angular-ui.js', array( 'angular-ui-ieshiv', 'jquery-ui-sortable', 'ckeditor', 'angular' ), null, false );

			//Ekko Course Creator
			wp_register_style( 'ekko-app', \Ekko\PLUGIN_URL . 'css/course.css', array( 'bootstrap', 'bootstrap-switch' ) );
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
				'api_url' => admin_url( '/admin-ajax.php' )
			) );

			if ( in_array( $hook_suffix, array( 'post.php', 'post-new.php' ) ) ) {
				wp_enqueue_media();
				wp_enqueue_style( 'ekko-app' );
				wp_enqueue_script( 'ekko-app' );
				wp_enqueue_script( 'ecv-editor' );
				//TODO Add Setting
				wp_enqueue_script( 'jfm-videos' );
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
				if ( $name == 'title' ) {
					$columns[ $name ]     = $value;
					$columns[ 'lessons' ] = __( 'Lessons', \Ekko\TEXT_DOMAIN );
					$columns[ 'quizzes' ] = __( 'Quizzes', \Ekko\TEXT_DOMAIN );
				}
				else
					$columns[ $name ] = $value;
			}
			return $columns;
		}

		/**
		 * @param string $column_name
		 * @param int    $post_id
		 */
		final public function manage_posts_custom_column( $column_name, $post_id ) {
			$course = $this->get_course( $post_id );
			if ( $column_name == 'lessons' ) {
				$count = 0;
				foreach ( $course->lessons as $item ) {
					if ( $item->type == 'lesson' )
						$count ++;
				}
				echo $count;
			}
			elseif ( $column_name == 'quizzes' ) {
				$count = 0;
				foreach ( $course->lessons as $item ) {
					if ( $item->type == 'quiz' )
						$count ++;
				}
				echo $count;
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

		final public function course_templates() {
			include( \Ekko\PLUGIN_DIR . 'templates/course-template.php' );
		}

		final public function redirect_post( $location, $post_id ) {
			if ( get_post_type( $post_id ) == $this->post_type() ) {
				if ( isset( $_POST[ 'save' ] ) ) {
					$location = remove_query_arg( 'message', $location );
					return add_query_arg( 'message', 4, $location );
				}
			}
			return $location;
		}

		final public function delete_post( $post_id ) {
			if ( get_post_type( $post_id ) == $this->post_type() ) {
				$post      = get_post( $post_id );
				$course    = $this->get_post( $post );
				$course_id = $course->course_ID;
				if ( $course_id !== false ) {
					$hub = \Ekko\Core\Services\Hub::singleton();
					$hub->delete_course( $course_id );
				}
			}
		}

	}
}
