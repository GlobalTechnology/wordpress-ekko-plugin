<?php namespace GTO\Framework\Posts {

	abstract class PostType extends \GTO\Framework\Singleton {

		/****************************
		 * Properties
		****************************/

		/**
		 * Custom Post Type name
		 * @var string
		 */
		protected $post_type = '';

		/**
		 * Hide the Permlink on the edit post screen
		 * @var bool
		 */
		protected $hide_permalink = true;

		/**
		 * Remove the default Publish metabox (submitdiv)
		 *
		 * If this is removed another "submit" needs to be provided
		 * @var bool
		 */
		protected $remove_publish_metabox = true;

		/**
		 * Remove the Featured Image metabox (postimagediv)
		 *
		 * @var bool
		 */
		protected $remove_featured_image_metabox = false;

		/****************************
		 * Internal Methods
		****************************/
		/**
		 * Constructor
		 *
		 * This class uses a Singleton Pattern, use CLASS::singleton() to get the instance.
		 * @return void
		 */
		final protected function __construct() {
			$this->_register_hooks();
		}

		/**
		 * Registers WordPress actions and filters
		 * @internal
		 */
		private function _register_hooks() {
			//Action to register the custom post type
			add_action( 'init', array( &$this, '_register_post_type' ), 10, 0 );

			if( is_admin() ) {
				//Register admin scripts and css
				add_action( 'admin_enqueue_scripts', array( &$this, '_admin_enqueue_scripts' ), 10, 1 );

				//Hide the sample permalink on the edit page
				if( $this->hide_permalink )
					add_filter( 'get_sample_permalink_html', array( &$this, '_hide_sample_permalink'), 10, 2 );

				//Modify the 'Enter title here' text
				add_filter( 'enter_title_here', array( &$this, '_enter_title_here' ), 10, 2 );

				//Modify Post before it is saved
				add_filter( 'wp_insert_post_data', array( &$this, '_pre_save_post' ), 10, 2 );

				//Save the custom post and post meta
				add_action( 'save_post', array( &$this, '_save_post' ), 10, 2 );

				if( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
					add_action( 'admin_init', array( &$this, '_admin_init' ), 10, 0 );
				}
			}

			//Allow subclasses to register additional actions and filters
			$this->register_hooks();
		}

		/**
		 * Register the custom post type with WordPress
		 * @internal
		 */
		final public function _register_post_type() {
			//Post Type defaults
			$defaults = array(
				'register_meta_box_cb' => array( &$this, '_register_metaboxes' ),
				'public'               => false,
				'show_ui'              => true,
				'show_in_menu'         => true,
				'show_in_admin_bar'    => false,
				'menu_position'        => 30,
				'can_export'           => false,
				'query_var'            => false,
			);
			//Merge custom post type arguments with the defaults
			$args = wp_parse_args( $this->post_type_args(), $defaults );
			//Register the custom post type with WordPress
			register_post_type( $this->post_type, $args );
		}

		/**
		 * Register and enqueue scripts and styles
		 * @param string $hook_suffix
		 */
		final public function _admin_enqueue_scripts( $hook_suffix ) {
			if( get_current_screen()->post_type == $this->post_type )
				$this->enqueue_admin_scripts( $hook_suffix );
		}

		/**
		 * Registers Metaboxes for the custom post type edit screens
		 * @internal
		 * @param \WP_Post $post
		 */
		final public function _register_metaboxes( \WP_Post $post ) {
			//Remove the default Publish metabox (submitdiv)
			if( $this->remove_publish_metabox )
				remove_meta_box( 'submitdiv', null, 'side' );

			//Remove the featured image metabox
			if( $this->remove_featured_image_metabox )
				remove_meta_box( 'postimagediv', null, 'side' );

			//Register custom metaboxes
			foreach( $this->metaboxes() as $metabox ) {
				if( $metabox instanceof \GTO\Framework\Posts\PostMetabox ) {
					if( $metabox->location == \GTO\Framework\Posts\PostMetabox::LOC_METABOX ) {
						add_meta_box( $metabox->id, $metabox->title, array( &$this, '_do_meta_box' ), null, $metabox->context, $metabox->priority );
						add_filter( "postbox_classes_{$this->post_type}_{$metabox->id}", array( &$this, '_add_metabox_class' ), 10, 1 );
					}
					elseif( array_key_exists( $metabox->location, \GTO\Framework\Posts\PostMetabox::$LOCATION_ACTION_MAP ) ) {
						add_action( \GTO\Framework\Posts\PostMetabox::$LOCATION_ACTION_MAP[ $metabox->location ], array( &$this, '_do_static_meta_box' ), 10, 0 );
					}
				}
			}
		}

		/**
		 * Display the metabox
		 * @internal
		 * @param \WP_Post $post
		 * @param array $metabox
		 * @return void
		 */
		final public function _do_meta_box( \WP_Post $post, array $metaboxarr ) {
			$post = $this->get_post( $post );
			foreach( $this->metaboxes() as $metabox ) {
				if( $metabox->id == $metaboxarr[ 'id' ] ) {
					wp_nonce_field( "{$metabox->id}-{$post->ID}", "{$metabox->id}-nonce", false );
					$metabox->display( $post, $metaboxarr );
					break;
				}
			}
		}

		final public function _do_static_meta_box() {
			$location = array_search( current_filter(), \GTO\Framework\Posts\PostMetabox::$LOCATION_ACTION_MAP, true );
			if( $location !== false ) {
				$post = $this->get_post( get_post( null ) );
				foreach( $this->metaboxes() as $metabox ) {
					if( $metabox->location == $location ) {
						wp_nonce_field( "{$metabox->id}-{$post->ID}", "{$metabox->id}-nonce", false );
						$metabox->display( $post, null );
					}
				}
			}
		}

		/**
		 * Modify Post data before it is saved.
		 * @internal
		 * @param array $data
		 * @param array $postarr
		 * @return array
		 */
		final public function _pre_save_post( array $data, array $postarr ) {
			if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return $data;
			if( get_post_type( $post ) != $this->post_type ) return $data;

			$data = $this->pre_save_post( $data, $postarr );
			return $data;
		}

		/**
		 * Save the post metabox data
		 * @internal
		 * @param int $post_id
		 * @param \WP_Post $post
		 * @return void
		 */
		final public function _save_post( $post_id, \WP_Post $post ) {
			if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
			if( get_post_type( $post ) != $this->post_type ) return;

			//Wrap the WP_Post object in a composite class for this custom post type
			$post = $this->get_post( $post );

			$this->save_post( $post );

			foreach( $this->metaboxes() as $metabox ) {
				if( array_key_exists( "{$metabox->id}-nonce", $_POST ) && wp_verify_nonce( $_POST[ "{$metabox->id}-nonce" ], "{$metabox->id}-{$post->ID}" ) ) {
					$metabox->save( $post );
				}
			}
		}

		final public function _admin_init() {
			foreach( $this->metaboxes() as $metabox ) {
				foreach( $metabox->ajax as $action => $callback ) {
					add_action( "wp_ajax_{$action}", $callback, 10, 0 );
				}
			}
		}

		/**
		 * Hides the sample permalink on the edit page
		 * @internal
		 * @param string $html
		 * @param int $post_id
		 * @return string
		 */
		final public function _hide_sample_permalink( $html, $post_id ) {
			if( $this->hide_permalink && get_post_type( $post_id ) == $this->post_type )
				return '<span />';
			return $html;
		}

		/**
		 * Modify the 'Enter title here' text
		 * @param string $title
		 * @param \WP_Post $post
		 * @return string
		 */
		final public function _enter_title_here( $title, \WP_Post $post ) {
			if( get_post_type( $post ) == $this->post_type )
				if( $custom_title = $this->post_type_title() )
					return $custom_title;
			return $title;
		}

		/**
		 * Add additional css classes to post metaboxes
		 * @param array $classes
		 * @return array
		 */
		final public function _add_metabox_class( array $classes ) {
			return array_merge( $classes, $this->metabox_classes() );
		}

		/****************************
		 * Subclass Methods
		****************************/
		/**
		 * Register WordPress actions and filters
		 */
		protected function register_hooks() {}

		/**
		 * Custom Post Type name
		 * @return string
		 */
		final public function post_type() {
			return $this->post_type;
		}

		/**
		 * Custom Post Type arguments
		 * @return array
		 */
		protected function post_type_args() { return array(); }

		/**
		 * Custom Post 'Enter title here' Text
		 * @return string
		 */
		protected function post_type_title() {}

		/**
		 * Modify post data before it is saved
		 * @param array $data
		 * @param array $postarr
		 * @return array
		 */
		protected function pre_save_post( array $data, array $postarr ) { return $data; }

		/**
		 * Post Saved
		 * @param \WP_Post $post
		 */
		protected function save_post( \GTO\Framework\Posts\Post $post ) {}

		/**
		 * Returns a Compostite Post of the given WordPress post
		 * @param \WP_Post $post
		 * @return \GTO\Framework\Posts\Post
		 */
		protected function get_post( \WP_Post $post ) { return new \GTO\Framework\Posts\Post( $post ); }

		/**
		 * Metaboxes for the custom post type
		 * @return \GTO\Framework\Posts\PostMetabox[]
		 */
		protected function metaboxes() { return array(); }

		/**
		 * Register and Enqueue javascript and css for the admin
		 * @param string $hook_suffix
		 */
		protected function enqueue_admin_scripts( $hook_suffix ) {}

		/**
		 * Additional classes for post metaboxes
		 * @return array
		 */
		protected function metabox_classes() { return array(); }
	}
}