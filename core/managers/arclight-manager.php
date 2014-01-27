<?php namespace Ekko\Core\Managers {

	/**
	 * Class ArclightManager
	 * @package Ekko\Core\Managers
	 * @method static \Ekko\Core\Managers\ArclightManager singleton()
	 */
	final class ArclightManager extends \GTO\Framework\Singleton {

		private static $box_art_order = array(
			'Mobile cinematic high' => 5,
			'HD'                    => 4,
			'Large'                 => 3,
			'Medium'                => 2,
			'Mobile cinematic low'  => 1,
			'Small'                 => 0,
		);

		/**
		 * Constructor
		 *
		 * Registers actions and filters
		 */
		final protected function __construct() {
			add_action( 'admin_enqueue_scripts', array( &$this, 'register_scripts_styles' ), 6, 1 );
			add_action( 'print_media_templates', array( &$this, 'media_templates' ), 10, 0 );

			if ( defined( 'DOING_AJAX' ) && DOING_AJAX === true ) {
				add_action( 'wp_ajax_arclight-get-languages', array( &$this, 'get_languages' ), 10, 0 );
				add_action( 'wp_ajax_arclight-get-titles', array( &$this, 'get_titles' ), 10, 0 );
				add_action( 'wp_ajax_arclight-get-categories', array( &$this, 'get_categories' ), 10, 0 );
				add_action( 'wp_ajax_arclight-has-associated', array( &$this, 'has_associated_content' ), 10, 0 );
				add_action( 'wp_ajax_arclight-get-thumbnail', array( &$this, 'get_thumbnail' ), 10, 0 );
			}
		}

		/**
		 * Register JS and CSS
		 *
		 * @param string $hook_suffix
		 */
		final public function register_scripts_styles( $hook_suffix ) {
			wp_register_script( 'jfm-videos', \Ekko\PLUGIN_URL . 'js/jfm/arclight.js', array( 'ekko-cloud-video' ) );
		}

		/**
		 * Render Backbone.js templates
		 */
		final public function media_templates() {
			include( \Ekko\PLUGIN_DIR . 'templates/arclight-template.php' );
		}

		/**
		 * AJAX - Get list of languages
		 */
		final public function get_languages() {
			$languages = \Ekko\Core\Services\Arclight::singleton()->get_languages();
			if ( $languages !== false ) {
				wp_send_json_success( $languages );
			}
			wp_send_json_error();
		}

		/**
		 * AJAX - Get a list of categories
		 */
		final public function get_categories() {
			$categories = \Ekko\Core\Services\Arclight::singleton()->get_categories();
			if ( false !== $categories ) {
				wp_send_json_success( $categories );
			}
			wp_send_json_error();
		}

		/**
		 * AJAX - Get a list of titles
		 *
		 * If refId is included, then the list of titles will be the associated content for the refId
		 */
		final public function get_titles() {
			$language = array_key_exists( 'language', $_POST ) ? stripslashes( $_POST[ 'language' ] ) : '529';
			$category = array_key_exists( 'category', $_POST ) ? stripslashes( $_POST[ 'category' ] ) : '';
			$refId    = array_key_exists( 'refId', $_POST ) ? stripslashes( $_POST[ 'refId' ] ) : false;
			if ( $refId ) {
				$titles = \Ekko\Core\Services\Arclight::singleton()->get_associated_content( $refId );
			}
			else {
				$titles = \Ekko\Core\Services\Arclight::singleton()->get_titles( $language, $category );
			}
			if ( $titles !== false ) {
				wp_send_json_success( $titles );
			}
			wp_send_json_error();
		}

		/**
		 * AJAX - Does a Title have associated content
		 */
		final public function has_associated_content() {
			$refId = isset( $_REQUEST[ 'refId' ] ) ? stripslashes( $_REQUEST[ 'refId' ] ) : false;
			if ( $refId ) {
				if ( $details = \Ekko\Core\Services\Arclight::singleton()->get_details( $refId ) ) {
					if ( array_key_exists( 'elementCount', $details ) ) {
						if ( intval( $details[ 'elementCount' ] ) > 0 )
							wp_send_json_success();
					}
				}
			}
			wp_send_json_error();
		}

		/**
		 * AJAX - Redirects to the thumbnail for the refId, or the default thumbnail.
		 */
		final public function get_thumbnail() {
			$refId     = isset( $_REQUEST[ 'refId' ] ) ? stripslashes( $_REQUEST[ 'refId' ] ) : false;
			$thumbnail = \Ekko\PLUGIN_URL . 'images/default-video.png';

			$details = \Ekko\Core\Services\Arclight::singleton()->get_details( $refId );
			if ( $details ) {
				if ( array_key_exists( 'videoStillUrl', $details ) && ! empty( $details[ 'videoStillUrl' ] ) ) {
					$thumbnail = $details[ 'videoStillUrl' ];
				}
				elseif ( array_key_exists( 'boxArtUrls', $details ) ) {
					$boxArt = $details[ 'boxArtUrls' ];
					if ( count( $boxArt ) > 0 ) {
						uasort( $boxArt, array( &$this, '_box_art_sort' ) );
						$item      = array_pop( $boxArt );
						$thumbnail = $item[ 'url' ][ 'uri' ];
					}
				}
			}

			wp_redirect( $thumbnail );
			wp_die();
		}

		/**
		 * Sort Box Art by size
		 * @internal
		 * @param $a
		 * @param $b
		 *
		 * @return int
		 */
		public function _box_art_sort( $a, $b ) {
			$a_index = array_key_exists( $a[ 'url' ][ 'type' ], self::$box_art_order ) ? self::$box_art_order[ $a[ 'url' ][ 'type' ] ] : - 1;
			$b_index = array_key_exists( $b[ 'url' ][ 'type' ], self::$box_art_order ) ? self::$box_art_order[ $b[ 'url' ][ 'type' ] ] : - 1;
			if ( $a_index > $b_index )
				return 1;
			elseif ( $b_index > $a_index )
				return - 1;
			return 0;
		}

	}
}
