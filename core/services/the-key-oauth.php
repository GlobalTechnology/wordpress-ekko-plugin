<?php namespace Ekko\Core\Services {

	final class TheKeyOAuth {

		const CAS_URI = 'https://thekey.me/cas';

		/**
		 * Singleton instance
		 * @var TheKeyOAuth
		 */
		private static $instance;

		/**
		 * Returns the TheKeyOAuth singleton
		 * @return TheKeyOAuth
		 */
		public static function singleton() {
			if ( ! isset( self::$instance ) ) {
				$class          = __CLASS__;
				self::$instance = new $class();
			}
			return self::$instance;
		}

		/**
		 * Prevent cloning of the class
		 * @internal
		 */
		private function __clone() {
		}

		/**
		 * Constructor
		 */
		private function __construct() {
		}


		public function get_access_token( $refresh_token ) {
			$url = self::CAS_URI . '/api/oauth/token';

			$response = wp_remote_post( $url, array(
				'body' => array(
					'grant_type'    => 'refresh_token',
					'client_id'     => \Ekko\OAUTH_CLIENT_ID,
					'refresh_token' => $refresh_token,
				)
			) );

			$json = json_decode( $response[ 'body' ] );
			return $json->access_token;
		}

		public function get_ticket( $service, $refresh_token ) {
			$params = array(
				'access_token' => $this->get_access_token( $refresh_token ),
				'service'      => $service,
			);
			$url    = self::CAS_URI . '/api/oauth/ticket?' . http_build_query( $params, null, '&' );

			$response = wp_remote_get( $url );
			$json     = json_decode( $response[ 'body' ] );

			return $json->ticket;
		}

	}
}
