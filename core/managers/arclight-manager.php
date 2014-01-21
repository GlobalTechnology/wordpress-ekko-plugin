<?php namespace Ekko\Core\Managers {

	/**
	 * Class ArclightManager
	 * @package Ekko\Core\Managers
	 * @method static \Ekko\Core\Managers\ArclightManager singleton()
	 */
	final class ArclightManager extends \GTO\Framework\Singleton {

		final protected function __construct() {
			add_action( 'admin_enqueue_scripts', array( &$this, 'register_scripts_styles' ), 6, 1 );
			add_action( 'print_media_templates', array( &$this, 'media_templates' ), 10, 0 );

			if ( defined( 'DOING_AJAX' ) && DOING_AJAX === true ) {
				add_action( 'wp_ajax_arclight-get-languages', array( &$this, 'get_languages' ), 10, 0 );
				add_action( 'wp_ajax_arclight-get-titles', array( &$this, 'get_titles' ), 10, 0 );
			}
		}

		final public function register_scripts_styles( $hook_suffix ) {
			wp_register_script( 'jfm-videos', \Ekko\PLUGIN_URL . 'js/jfm/arclight.js', array( 'ekko-cloud-video' ) );
		}

		final public function media_templates() {
			include( \Ekko\PLUGIN_DIR . 'templates/arclight-template.php' );
		}

		final public function get_languages() {
			$languages = \Ekko\Core\Services\Arclight::singleton()->get_languages();
			if ( $languages !== false ) {
				wp_send_json_success( $languages );
			}
			wp_send_json_error();
		}

		final public function get_titles() {
			$language = array_key_exists( 'language', $_POST ) ? stripslashes( $_POST[ 'language' ] ) : '529';
			$titles   = \Ekko\Core\Services\Arclight::singleton()->get_titles( $language );
			if ( $titles !== false ) {
				$videos = array();
				foreach( $titles as $title ) {
					if( intval( $title['groupContentCount'] ) > 0 )
						continue;
					$videos[] = $title;
				}
				wp_send_json_success( $videos );
			}
			wp_send_json_error();
		}

	}
}
