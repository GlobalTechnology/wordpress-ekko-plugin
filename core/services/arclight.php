<?php namespace Ekko\Core\Services {

	/**
	 * Class Arclight
	 * @package Ekko\Core\Services
	 * @method static \Ekko\Core\Services\Arclight singleton()
	 */
	final class Arclight extends \GTO\Framework\Singleton {

		const ENDPOINT_GET_CATEGORIES = '%(arclight)s/getCategories';
		const ENDPOINT_GET_LANGUAGES  = '%(arclight)s/getLanguages';
		const ENDPOINT_GET_TITLES     = '%(arclight)s/getTitles';

		protected function __construct() {
		}

		/**
		 * Get a list of Languages
		 *
		 * @return array|false
		 */
		final public function get_languages() {
			$response = wp_remote_get(
				$this->url( self::ENDPOINT_GET_LANGUAGES ),
				array(
					'redirection' => 0,
					'headers'     => array(
						'Accept' => 'application/json',
					),
				)
			);
			$json     = $this->parse_response( $response, 'languages' );
			if ( false !== $json ) {
				$languages = array();
				foreach ( $json as $object ) {
					$languages[ ] = $object[ 'language' ];
				}
				return $languages;
			}
			return array();
		}

		/**
		 * Get a list of Categories
		 * @return array|false
		 */
		final public function get_categories() {
			$response = wp_remote_get(
				$this->url( self::ENDPOINT_GET_CATEGORIES ),
				array(
					'redirection' => 0,
					'headers'     => array(
						'Accept' => 'application/json',
					),
				)
			);
			$json     = $this->parse_response( $response, 'categories' );
			if ( false !== $json ) {
				$categories = array();
				foreach ( $json as $object ) {
					$categories[ ] = $object[ 'category' ];
				}
				return $categories;
			}
			return array();
		}

		final public function get_titles( $language_id = '529', $category_name = '' ) {
			$response = wp_remote_get(
				$this->url( self::ENDPOINT_GET_TITLES, array(
					'languageId'   => $language_id,
					'categoryName' => $category_name,
				) ),
				array(
					'redirection' => 0,
					'headers'     => array(
						'Accept' => 'application/json',
					),
				)
			);
			$json     = $this->parse_response( $response, 'titles' );
			if ( false !== $json ) {
				$titles = array();
				foreach ( $json as $object ) {
					$titles[ ] = $object[ 'title' ];
				}
				return $titles;
			}
			return array();
		}

		/**
		 * Build an Arclight url
		 *
		 * @param string $endpoint
		 * @param array  $params
		 *
		 * @return string
		 */
		final private function url( $endpoint, $params = array() ) {
			$params = wp_parse_args( $params, array(
				'responseType' => 'json',
				'apiKey'       => \Ekko\JFM_ARCLIGHT_KEY,
			) );
			return add_query_arg(
				$params,
				\GTO\Framework\Util\String::vnsprintf( $endpoint, array(
					'arclight' => rtrim( \Ekko\JFM_ARCLIGHT_URI, '/' )
				) )
			);
		}

		/**
		 * Parse Arclight response to JSON
		 *
		 * returns false if unable to parse response, json, or missing $name as key
		 *
		 * @param $response
		 * @param $name
		 *
		 * @return false|array
		 */
		final private function parse_response( $response, $name ) {
			if ( $response && ! is_wp_error( $response ) ) {
				$code = (int)$response[ 'response' ][ 'code' ];
				if ( 200 <= $code || 300 > $code ) {
					$json = json_decode( $response[ 'body' ], true );
					switch( json_last_error() ) {
						case JSON_ERROR_NONE:
							break;
						case JSON_ERROR_UTF8:
							$json = json_decode( utf8_encode( $response[ 'body' ] ), true );
							if( $json === null )
								return false;
							break;
						default:
							return false;
					}
					if ( $json !== null && array_key_exists( $name, $json ) ) {
						if ( empty( $json[ $name ] ) )
							return array();
						return $json[ $name ];
					}
				}
			}
			return false;
		}
	}
}
