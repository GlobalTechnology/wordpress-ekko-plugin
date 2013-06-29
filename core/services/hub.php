<?php namespace Ekko\Core\Services {

	final class Hub {

		const ENDPOINT_SERVICE         = '%(hub)sauth/service';
		const ENDPOINT_LOGIN           = '%(hub)sauth/login';
		const ENDPOINT_CREATE_COURSE   = '%(hub)s%(session)s/courses';
		const ENDPOINT_UPDATE_COURSE   = '%(hub)s%(session)s/courses/course/%(course)s/manifest';
		const ENDPOINT_RESOURCES       = '%(hub)s%(session)s/courses/course/%(course)s/resources';
		const ENDPOINT_PUBLISH_COURSE  = '%(hub)s%(session)s/courses/course/%(course)s/publish';
		const ENDPOINT_ENROLLED        = '%(hub)s%(session)s/courses/course/%(course)s/enrolled';
		const ENDPOINT_ADMINS          = '%(hub)s%(session)s/courses/course/%(course)s/admins';

		const META_SESSION = 'ekko-hub-session';

		/**
		 * Singleton instance
		 * @var Hub
		 */
		private static $instance;

		/**
		 * Returns the Hub singleton
		 * @return Hub
		 */
		public static function singleton() {
			if( !isset( self::$instance ) ) {
				$class = __CLASS__;
				self::$instance = new $class();
			}
			return self::$instance;
		}

		/**
		 * Prevent cloning of the class
		 * @internal
		 */
		private function __clone() {}

		/**
		 * Constructor
		 */
		private function __construct() {
		}

		/**
		 * Get the Hub auth service URL
		 * @return string
		 */
		public function get_service() {
			$response = wp_remote_get(
				$this->vnsprintf( self::ENDPOINT_SERVICE, array( 'hub' => \Ekko\URI_HUB ) ),
				array(
					'redirection' => 0,
					'headers' => array(
						'Content-Type' => 'application/xml'
					),
				)
			);
			return( $response[ 'body' ] );
		}

		/**
		 * Return a valid Ekko Hub session ID
		 * @return string
		 */
		public function get_session( $superuser = false ) {
			$user = ( $superuser ) ?
				\GlobalTechnology\CentralAuthenticationService\CASLogin::singleton()->get_user_by_guid( \Ekko\GUID_SUPER_ADMIN ) :
				wp_get_current_user();

			//Retrieve session from WordPress user meta
			$session = get_user_meta( $user->ID, self::META_SESSION, true );

			//Return session ID if session is valid
			if( $session && $session instanceof \Ekko\Core\Services\HubSession ) {
				if( $session->valid() )
					return $session->session;
			}

			//Session was not valid, fetch a new session ID from the Ekko Hub
			$ticket = ( $superuser ) ?
				\Ekko\Core\Services\TheKeyOAuth::singleton()->get_ticket( $this->get_service(), \Ekko\OAUTH_REFRESH_TOKEN ) :
				\GlobalTechnology\CentralAuthenticationService\CASLogin::singleton()->get_cas_client()->retrievePT( $this->get_service(), $err_code, $err_msg );
			$response = wp_remote_post(
				$this->vnsprintf( self::ENDPOINT_LOGIN, array( 'hub' => \Ekko\URI_HUB ) ),
				array( 'body' => array( 'ticket' => $ticket ) )
			);
			$session = new HubSession( $response[ 'body' ] );

			//Store the new Session into the WordPress user meta
			update_user_meta( $user->ID, self::META_SESSION, $session );

			//Return the Session ID
			return $session->session;
		}

		/**
		 * Create a Course
		 * @param string $manifest
		 */
		public function create_course( $manifest, $session = null ) {
			$params = array(
				'hub' => \Ekko\URI_HUB,
				'session' => ( $session ) ? $session : $this->get_session(),
			);
			$response = wp_remote_post(
				$this->vnsprintf( self::ENDPOINT_CREATE_COURSE, $params ),
				array(
					'redirection' => 0,
					'headers' => array(
						'Content-Type' => 'application/xml'
					),
					'body' => $manifest
				)
			);
			$dom = $this->parse_xml_to_domdoc( $response[ 'body' ] );
			if( $dom )
				return $dom->documentElement->getAttribute( 'id' );
			false;
		}

		/**
		 * Update and existing courses manifest
		 *
		 * This does not update the manifest for a published course, it begins a new update to a course
		 * which will need to be published before changes will be picke up by the app.
		 * @param int $course_id
		 * @param string $manifest XML String
		 */
		public function update_course( $course_id, $manifest, $session = null ) {
			$params = array(
				'hub' => \Ekko\URI_HUB,
				'session' => ( $session ) ? $session : $this->get_session(),
				'course' => $course_id,
			);
			$response = wp_remote_request(
				$this->vnsprintf( self::ENDPOINT_UPDATE_COURSE, $params ),
				array(
					'method' => 'PUT',
					'redirection' => 0,
					'headers' => array(
						'Content-Type' => 'application/xml'
					),
					'body' => $manifest
				)
			);
		}

		/**
		 * Upload a file to a course
		 * @param int $course_id
		 * @param file $file absolute file path
		 * @param string $type file mime type
		 */
		public function upload_resource( $course_id, $file, $type, $session = null ) {
			$params = array(
				'hub' => \Ekko\URI_HUB,
				'session' => ( $session ) ? $session : $this->get_session(),
				'course' => $course_id,
			);
			$ch = curl_init();
			$file_stream = fopen( $file, "r" );
			curl_setopt_array( $ch, array(
				CURLOPT_UPLOAD => 1,
				CURLOPT_INFILE => $file_stream,
				CURLOPT_INFILESIZE => filesize( $file ),
				CURLOPT_CUSTOMREQUEST => 'POST',
				CURLOPT_URL => $this->vnsprintf( self::ENDPOINT_RESOURCES, $params ),
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_FOLLOWLOCATION => false,
				CURLOPT_HTTPHEADER => array( 'Content-Type: ' . $type ),
			) );
			$response = curl_exec( $ch );
			fclose( $file_stream );
			curl_close( $ch );
		}

		/**
		 * Get a list of resource sha1 hashes for currently uploaded resources
		 *
		 * @param string $course_id
		 * @return array
		 */
		public function get_resources( $course_id, $session = null ) {
			$params = array(
				'hub' => \Ekko\URI_HUB,
				'session' => ( $session ) ? $session : $this->get_session(),
				'course' => $course_id,
			);
			$response = wp_remote_get(
				$this->vnsprintf( self::ENDPOINT_RESOURCES, $params ),
				array(
					'redirection' => 0,
					'headers' => array(
						'Content-Type' => 'application/xml'
					),
				)
			);
			$dom = $this->parse_xml_to_domdoc( $response[ 'body' ] );
			$resources = array();
			if( $dom ) {
				$xpath = $this->xpath_parser( $dom );
				foreach( $xpath->query( '/hub:resources/hub:resource/@sha1' ) as $sha1 )
					$resources[] = $sha1->value;
			}
			return $resources;
		}

		/**
		 * Mark the current course manifest as published
		 * @param int $course_id
		 *
		 * @return array|boolean
		 */
		public function publish_course( $course_id, $session = null ) {
			$params = array(
				'hub' => \Ekko\URI_HUB,
				'session' => ( $session ) ? $session : $this->get_session(),
				'course' => $course_id,
			);
			$response = wp_remote_post(
				$this->vnsprintf( self::ENDPOINT_PUBLISH_COURSE, $params ),
				array(
					'redirection' => 0,
					'headers' => array(
						'Content-Type' => 'application/xml'
					),
				)
			);
			if( $response[ 'response' ][ 'code' ] == 200 )
				return true;
			$dom = $this->parse_xml_to_domdoc( $response[ 'body' ] );
			$errors = array();
			if( $dom ) {
				$xpath = $this->xpath_parser( $dom );
				foreach( $xpath->query( '/hub:errors/hub:error/@message' ) as $error ) {
					$errors[] = $error->value;
				}
			}
			if( empty( $errors ) )
				$errors[] = __( 'Unknown Error', \Ekko\TEXT_DOMAIN );
			return $errors;
		}

		/**
		 * Get a list of user guid from the specified endpoint
		 * @param int $course_id
		 * @param string $endpoint
		 * @return array
		 */
		private function get_users( $course_id, $endpoint = self::ENDPOINT_ENROLLED, $session = null ) {
			$params = array(
				'hub' => \Ekko\URI_HUB,
				'session' => ( $session ) ? $session : $this->get_session(),
				'course' => $course_id,
			);
			$response = wp_remote_get(
				$this->vnsprintf( $endpoint, $params ),
				array(
					'redirection' => 0,
					'headers' => array(
						'Content-Type' => 'application/xml'
					),
				)
			);
			$users = array();
			if( $dom = $this->parse_xml_to_domdoc( $response[ 'body' ] ) ) {
				$xpath = $this->xpath_parser( $dom );
				foreach( $xpath->query( '/hub:admins/hub:user/@guid' ) as $guid )
					$users[] = strtolower( $guid->value );
			}
			return $users;
		}

		/**
		 * Update the users at the specified endpoint
		 * @param int $course_id
		 * @param array $add
		 * @param array $remove
		 * @param string $endpoint
		 * @return void
		 */
		private function update_users( $course_id, array $add = array(), array $remove = array(), $endpoint = self::ENDPOINT_ENROLLED, $session = null ) {
			$params = array(
				'hub' => \Ekko\URI_HUB,
				'session' => ( $session ) ? $session : $this->get_session(),
				'course' => $course_id,
			);

			//Add the Super Admin to all courses
			if( $endpoint == self::ENDPOINT_ADMINS )
				$add[] = \Ekko\GUID_SUPER_ADMIN;

			$users = array_merge(
				array_map( function( $guid ) {
					return 'add=' . rawurlencode( $guid );
				}, $add ),
				array_map( function( $guid ) {
					return 'remove=' . rawurlencode( $guid );
				}, $remove )
			);

			if( empty( $users ) )
				return;

			$response = wp_remote_post(
				$this->vnsprintf( $endpoint, $params ),
				array(
					'redirection' => 0,
					'headers' => array(
						'Content-Type' => 'application/x-www-form-urlencoded'
					),
					'body' => implode( '&', $users ),
				)
			);
		}

		/**
		 * Get the list of admin guids for the course
		 * @param int $course_id
		 * @return array
		 */
		public function get_admins( $course_id ) {
			return $this->get_users( $course_id, self::ENDPOINT_ADMINS );
		}

		/**
		 * Get the list of enrolled guids for the course
		 * @param int $course_id
		 * @return array
		 */
		public function get_enrolled( $course_id ) {
			return $this->get_users( $course_id, self::ENDPOINT_ENROLLED );
		}

		/**
		 * Update the admins of the course by adding and removing guids
		 * @param int $course_id
		 * @param array $add
		 * @param array $remove
		 */
		public function update_admins( $course_id, array $add = array(), array $remove = array() ) {
			$this->update_users( $course_id, $add, $remove, self::ENDPOINT_ADMINS );
		}

		/**
		 * Update the enrolled users of the course by adding and removing guids
		 * @param int $course_id
		 * @param array $add
		 * @param array $remove
		 */
		public function update_enrolled( $course_id, array $add = array(), array $remove = array() ) {
			$this->update_users( $course_id, $add, $remove, self::ENDPOINT_ENROLLED );
		}

		/**
		 * Synchronize the admins of the course
		 * @param int $course_id
		 * @param array $admins
		 */
		public function sync_admins( $course_id, array $admins = array() ) {
			$users = $this->get_users( $course_id, self::ENDPOINT_ADMINS );
			$this->update_users(
				$course_id,
				array_diff( $admins, $users ),
				array_diff( $users, $admins ),
				self::ENDPOINT_ADMINS
			);
		}

		/**
		 * Synchronize the enrolled users of the course
		 * @param int $course_id
		 * @param array $enrolled
		 */
		public function sync_enrolled( $course_id, array $enrolled = array() ) {
			$users = $this->get_users( $course_id, self::ENDPOINT_ENROLLED );
			$this->update_users(
				$course_id,
				array_diff( $enrolled, $users ),
				array_diff( $users, $enrolled ),
				self::ENDPOINT_ENROLLED
			);
		}

		/**
		 * Format ENDPOINT URLs with named parameters
		 *
		 * @param string $format
		 * @param array $args
		 * @return string
		 */
		private function vnsprintf( $format, $args ) {
			$names = preg_match_all( '/%\((.*?)\)/', $format, $matches, PREG_SET_ORDER );

			$values = array();
			foreach( $matches as $match )
				$values[] = $args[ $match[ 1 ] ];

			$format = preg_replace( '/%\((.*?)\)/', '%', $format );
			return vsprintf( $format, $values );
		}

		/**
		 * Parses an xml string into a DOMDocument
		 * @param string $xml
		 * @return DOMDocument|false DomDocument on success or false
		 */
		private function parse_xml_to_domdoc( $xml ) {
			$dom = new \DOMDocument( '1.0', 'UTF-8' );
			set_error_handler( function( $errno, $errstr ) {
				if( $errno === E_WARNING && stripos( $errstr, "DOMDocument::loadXML()" ) !== false ) {
					return true;
				}
				return false;
			} );
			$res = $dom->loadXML( $xml );
			restore_error_handler();
			if($res)
				return $dom;
			return false;
		}

		/**
		 * Returns an xpath parser for the given dom with all Ekko XML namespaces registered
		 * @param \DOMDocument $dom
		 * @return \DOMXPath
		 */
		private function xpath_parser( $dom ) {
			$xpath = new \DOMXPath( $dom );
			$xpath->registerNamespace( 'hub', \Ekko\XMLNS_HUB );
			$xpath->registerNamespace( 'ekko', \Ekko\XMLNS_MANIFEST );
			return $xpath;
		}
	}
}
