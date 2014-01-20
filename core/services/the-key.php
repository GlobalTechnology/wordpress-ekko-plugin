<?php namespace Ekko\Core\Services {

	final class TheKey {

		const ENDPOINT_ATTRIBUTES   = '/api/%s/user/attributes';
		const ENDPOINT_OAUTH_TOKEN  = '/api/oauth/token';
		const ENDPOINT_OAUTH_TICKET = '/api/oauth/ticket';

		const PARAM_GUID  = 'guid';
		const PARAM_EMAIL = 'email';

		/**
		 * Singleton instance
		 * @var TheKey
		 */
		private static $instance;

		/**
		 * Returns the Hub singleton
		 * @return TheKey
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

		/**
		 * @return \CAS_Client
		 */
		final public function cas_client() {
			if ( class_exists( '\\GlobalTechnology\\CentralAuthenticationService\\CASLogin' ) )
				return \GlobalTechnology\CentralAuthenticationService\CASLogin::singleton()->get_cas_client();
			return \WPGCXPlugin::singleton()->cas_client();
		}

		/**
		 * @param string $guid
		 *
		 * @return null|\WP_User
		 */
		final public function get_user_by_guid( $guid ) {
			if ( class_exists( '\\GlobalTechnology\\CentralAuthenticationService\\CASLogin' ) )
				return \GlobalTechnology\CentralAuthenticationService\CASLogin::singleton()->get_user_by_guid( $guid );
			return \WPGCXPlugin::singleton()->get_user_by_guid( $guid );
		}

		/**
		 * Creates a new User
		 *
		 * @param string $guid
		 * @param array  $args
		 *
		 * @return null|\WP_User
		 */
		final public function create_user( $guid, array $args ) {
			$class = class_exists( '\\GlobalTechnology\\CentralAuthenticationService\\CASLogin' ) ?
				\GlobalTechnology\CentralAuthenticationService\CASLogin::singleton() :
				\WPGCXPlugin::singleton();
			return $class->create_user( $guid, $args );
		}

		/**
		 * @param string $endpoint
		 *
		 * @return string
		 */
		final private function api_url( $endpoint ) {
			$cas = $this->cas_client();
			return rtrim( preg_replace( '/logout$/', '', $cas->getServerLogoutURL() ), '/' ) . $endpoint;
		}

		/**
		 * Get an OAuth Access Token using a Refresh Token
		 *
		 * @param string $refresh_token
		 *
		 * @return string
		 */
		public function get_access_token( $refresh_token ) {
			$response = wp_remote_post(
				$this->api_url( self::ENDPOINT_OAUTH_TOKEN ),
				array(
					'body' => array(
						'grant_type'    => 'refresh_token',
						'client_id'     => \Ekko\OAUTH_CLIENT_ID,
						'refresh_token' => $refresh_token,
					)
				)
			);

			$json = json_decode( $response[ 'body' ] );
			return $json->access_token;
		}

		/**
		 * Fetch a ticket for the given service using a refresh token
		 *
		 * @param string $service
		 * @param string $refresh_token
		 *
		 * @return string
		 */
		public function get_ticket( $service, $refresh_token ) {
			$params = array(
				'access_token' => $this->get_access_token( $refresh_token ),
				'service'      => $service,
			);
			$url    = $this->api_url( self::ENDPOINT_OAUTH_TICKET ) . '?' . http_build_query( $params, null, '&' );

			$response = wp_remote_get( $url );
			$json     = json_decode( $response[ 'body' ] );

			return $json->ticket;
		}


		/**
		 * @param string $guid
		 *
		 * @return object|false
		 */
		final public function get_user_attributes_by_guid( $guid ) {
			return $this->get_user_attributes( self::PARAM_GUID, strtoupper( $guid ) );
		}

		/**
		 * @param string $email
		 *
		 * @return object|false
		 */
		final public function get_user_attributes_by_email( $email ) {
			return $this->get_user_attributes( self::PARAM_EMAIL, $email );
		}

		/**
		 * @param string $key
		 * @param string $value
		 *
		 * @return object|false
		 */
		protected function get_user_attributes( $key, $value ) {
			$url      = $this->api_url( sprintf( self::ENDPOINT_ATTRIBUTES, \Ekko\THEYKEY_API_KEY ) );
			$response = wp_remote_get(
				add_query_arg( $key, urlencode( $value ), $url ),
				array(
					'redirection' => 0,
					'headers'     => array(
						'Accept' => 'application/xml',
					),
				)
			);

			$dom = \GTO\Framework\Util\XML::parse_xml_to_domdoc( $response[ 'body' ] );
			if ( $dom ) {
				$attributes = array();
				$xpath      = new \DOMXPath( $dom );
				foreach ( $xpath->query( '/attributes/attribute' ) as $attr ) {
					$attributes[ $attr->getAttribute( 'name' ) ] = $attr->getAttribute( 'value' );
				}
				if ( count( $attributes ) > 0 )
					return (object)$attributes;
			}
			return false;
		}
	}

}
