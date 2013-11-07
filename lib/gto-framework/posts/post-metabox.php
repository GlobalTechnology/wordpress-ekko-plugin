<?php namespace GTO\Framework\Posts {

	abstract class PostMetabox extends \GTO\Framework\Singleton {

		/**
		 * Metabox Locations - Default Post Metabox
		 * @var string
		 */
		const LOC_METABOX = 'metabox';

		/**
		 * Metabox Locations - After Post Title
		 * @var string
		 */
		const LOC_STATIC_AFTER_TITLE = 'after-title';

		/**
		 * Metabox Locations - After Post Editor
		 * @var string
		 */
		const LOC_STATIC_AFTER_EDITOR = 'after-editor';

		/**
		 * Metabox Locations - Sidebar
		 * @var string
		 */
		const LOC_STATIC_SIDE = 'static-side';

		/**
		 * Metabox Locations - Advanced
		 * @var string
		 */
		const LOC_STATIC_ADVANCED = 'static-advanced';

		/**
		 * Metabox Locations - DBX Post Area
		 * @var string
		 */
		const LOC_STATIC_DBX = 'static-dbx';

		/**
		 * Metabox static locations to WordPress action/filter mapping
		 * @var array<string>
		 */
		public static $LOCATION_ACTION_MAP = array(
			self::LOC_STATIC_ADVANCED     => 'edit_form_advanced',
			self::LOC_STATIC_AFTER_EDITOR => 'edit_form_after_editor',
			self::LOC_STATIC_AFTER_TITLE  => 'edit_form_after_title',
			self::LOC_STATIC_DBX          => 'dbx_post_sidebar',
			self::LOC_STATIC_SIDE         => 'submitpost_box',
		);

		/**
		 * Metabox ID
		 * @var string
		 */
		public $id = '';

		/**
		 * Metabox Location
		 *
		 * @var string
		 */
		public $location = 'metabox';

		/**
		 * Metabox Title
		 * @var string
		 */
		public $title = '';

		/**
		 * Screen Context
		 *
		 * Valid values are 'normal', 'advanced', or 'side', defaults to 'normal'.
		 * @var string
		 */
		public $context = 'normal';

		/**
		 * Metabox priority
		 *
		 * Valid values are 'high', 'core', 'default' or 'low', defaults to 'low'.
		 * @var string
		 */
		public $priority = 'low';

		/**
		 * AJAX Callback handlers
		 *
		 * @var unknown
		 */
		public $ajax = array();

		/**
		 * @param \GTO\Framework\Posts\Post $post
		 * @param array                     $metabox
		 */
		abstract public function display( $post, $metabox );

		/**
		 * @param \GTO\Framework\Posts\Post $post
		 */
		public function save( $post ) {
		}
	}

}
