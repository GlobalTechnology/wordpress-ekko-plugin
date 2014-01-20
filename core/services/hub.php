<?php namespace Ekko\Core\Services {

	final class Hub {

		const ENDPOINT_SERVICE        = '%(hub)sauth/service';
		const ENDPOINT_LOGIN          = '%(hub)sauth/login';
		const ENDPOINT_CREATE_COURSE  = '%(hub)s%(session)s/courses';
		const ENDPOINT_UPDATE_COURSE  = '%(hub)s%(session)s/courses/course/%(course)s/manifest';
		const ENDPOINT_DELETE_COURSE  = '%(hub)s%(session)s/courses/course/%(course)s';
		const ENDPOINT_RESOURCES      = '%(hub)s%(session)s/courses/course/%(course)s/resources';
		const ENDPOINT_PUBLISH_COURSE = '%(hub)s%(session)s/courses/course/%(course)s/publish';
		const ENDPOINT_ENROLLED       = '%(hub)s%(session)s/courses/course/%(course)s/enrolled';
		const ENDPOINT_ADMINS         = '%(hub)s%(session)s/courses/course/%(course)s/admins';
		const ENDPOINT_SETTINGS       = '%(hub)s%(session)s/courses/course/%(course)s/settings.json';
		const ENDPOINT_CREATE_VIDEO   = '%(hub)s%(apikey)s/videos';
		const ENDPOINT_GET_VIDEOS     = '%(hub)s%(apikey)s/videos';
		const ENDPOINT_GET_VIDEO      = '%(hub)s%(apikey)s/videos/video/%(video)s';
		const ENDPOINT_PROCESS_VIDEO  = '%(hub)s%(apikey)s/videos/video/%(video)s/storeS3';
		const ENDPOINT_DELETE_VIDEO   = '%(hub)s%(apikey)s/videos/video/%(video)s';
		const ENDPOINT_VIDEO_COURSES  = '%(hub)s%(apikey)s/videos/video/%(video)s/courses';

		const TRANSIENT_SESSION = 'hub-session';

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
		 * Get the Hub auth service URL
		 * @return string
		 */
		public function get_service() {
			$response = wp_remote_get(
				\GTO\Framework\Util\String::vnsprintf( self::ENDPOINT_SERVICE, array( 'hub' => \Ekko\URI_HUB ) ),
				array(
					'redirection' => 0,
					'headers'     => array(
						'Accept' => 'text/plain'
					),
				)
			);
			return ( $response[ 'body' ] );
		}

		/**
		 * Return a valid Ekko Hub session ID
		 *
		 * @param bool $superuser
		 *
		 * @return string
		 */
		public function get_session( $superuser = false ) {
			$user = ( $superuser ) ?
				\Ekko\Core\Services\TheKey::singleton()->get_user_by_guid( \Ekko\GUID_SUPER_ADMIN ) :
				wp_get_current_user();

			//Retrieve session from WordPress user meta
			$session = \GTO\Framework\Util\User::get_user_transient( $user->ID, self::TRANSIENT_SESSION );

			//Return session ID if session is valid
			if ( $session && $session !== false )
				return $session;

			//Session was not valid, fetch a new session ID from the Ekko Hub
			$err_code = null;
			$err_msg  = null;
			$ticket   = ( $superuser ) ?
				\Ekko\Core\Services\TheKey::singleton()->get_ticket( $this->get_service(), \Ekko\OAUTH_REFRESH_TOKEN ) :
				\Ekko\Core\Services\TheKey::singleton()->cas_client()->retrievePT( $this->get_service(), $err_code, $err_msg );

			$response = wp_remote_post(
				\GTO\Framework\Util\String::vnsprintf( self::ENDPOINT_LOGIN, array( 'hub' => \Ekko\URI_HUB ) ),
				array(
					'redirection' => 0,
					'headers'     => array(
						'Accept' => 'application/json',
					),
					'body'        => array( 'ticket' => $ticket )
				)
			);
			if ( $this->is_response_OK( $response ) && $json = json_decode( $response[ 'body' ], true ) ) {
				//Store the new Session into the WordPress user meta
				\GTO\Framework\Util\User::set_user_transient(
					$user->ID,
					self::TRANSIENT_SESSION,
					$json[ 'id' ],
					5 * \HOUR_IN_SECONDS
				);

				//Return the Session ID
				return $session;
			}
			return false;
		}

		/**
		 * Create a Course
		 *
		 * @param string      $manifest
		 * @param string|null $session
		 *
		 * @return string|false Course ID
		 */
		public function create_course( $manifest, $session = null ) {
			$params   = array(
				'hub'     => \Ekko\URI_HUB,
				'session' => ( $session ) ? $session : $this->get_session(),
			);
			$response = wp_remote_post(
				\GTO\Framework\Util\String::vnsprintf( self::ENDPOINT_CREATE_COURSE, $params ),
				array(
					'redirection' => 0,
					'headers'     => array(
						'Content-Type' => 'application/xml',
						'Accept'       => 'application/xml',
					),
					'body'        => $manifest
				)
			);
			$dom      = \GTO\Framework\Util\XML::parse_xml_to_domdoc( $response[ 'body' ] );
			if ( $dom )
				return $dom->documentElement->getAttribute( 'id' );
			return false;
		}

		/**
		 * Update an existing courses manifest
		 *
		 * This does not update the manifest for a published course, it begins a new update to a course
		 * which will need to be published before changes will be picke up by the app.
		 *
		 * @param int         $course_id
		 * @param string      $manifest XML String
		 * @param string|null $session
		 */
		public function update_course( $course_id, $manifest, $session = null ) {
			$params = array(
				'hub'     => \Ekko\URI_HUB,
				'session' => ( $session ) ? $session : $this->get_session(),
				'course'  => $course_id,
			);
			wp_remote_request(
				\GTO\Framework\Util\String::vnsprintf( self::ENDPOINT_UPDATE_COURSE, $params ),
				array(
					'method'      => 'PUT',
					'redirection' => 0,
					'headers'     => array(
						'Content-Type' => 'application/xml',
						'Accept'       => 'application/xml',
					),
					'body'        => $manifest
				)
			);
		}

		/**
		 * Delete a course
		 *
		 * @param int         $course_id
		 * @param string|null $session
		 */
		public function delete_course( $course_id, $session = null ) {
			$params = array(
				'hub'     => \Ekko\URI_HUB,
				'session' => ( $session ) ? $session : $this->get_session(),
				'course'  => $course_id,
			);
			wp_remote_request(
				\GTO\Framework\Util\String::vnsprintf( self::ENDPOINT_DELETE_COURSE, $params ),
				array(
					'method'      => 'DELETE',
					'redirection' => 0,
					'headers'     => array(
						'Accept' => 'application/xml'
					),
				)
			);
		}

		/**
		 * Upload a file to a course
		 *
		 * @param int         $course_id
		 * @param string      $file absolute file path
		 * @param string      $type file mime type
		 * @param string|null $session
		 */
		public function upload_resource( $course_id, $file, $type, $session = null ) {
			$params      = array(
				'hub'     => \Ekko\URI_HUB,
				'session' => ( $session ) ? $session : $this->get_session(),
				'course'  => $course_id,
			);
			$ch          = curl_init();
			$file_stream = fopen( $file, "r" );
			curl_setopt_array( $ch, array(
				CURLOPT_UPLOAD         => 1,
				CURLOPT_INFILE         => $file_stream,
				CURLOPT_INFILESIZE     => filesize( $file ),
				CURLOPT_CUSTOMREQUEST  => 'POST',
				CURLOPT_URL            => \GTO\Framework\Util\String::vnsprintf( self::ENDPOINT_RESOURCES, $params ),
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_FOLLOWLOCATION => false,
				CURLOPT_HTTPHEADER     => array( 'Content-Type: ' . $type ),
			) );
			curl_exec( $ch );
			fclose( $file_stream );
			curl_close( $ch );
		}

		/**
		 * Get a list of resources currently in use for the course
		 *
		 * @param string      $course_id
		 * @param string|null $session
		 *
		 * @return array $resources {
		 * @type array        $files  file sha1 hashes
		 * @type array        $videos video ids
		 * }
		 */
		public function get_resources( $course_id, $session = null ) {
			$params    = array(
				'hub'     => \Ekko\URI_HUB,
				'session' => ( $session ) ? $session : $this->get_session(),
				'course'  => $course_id,
			);
			$response  = wp_remote_get(
				\GTO\Framework\Util\String::vnsprintf( self::ENDPOINT_RESOURCES, $params ),
				array(
					'redirection' => 0,
					'headers'     => array(
						'Accept' => 'application/xml'
					),
				)
			);
			$dom       = \GTO\Framework\Util\XML::parse_xml_to_domdoc( $response[ 'body' ] );
			$resources = array( 'files' => array(), 'videos' => array() );
			if ( $dom ) {
				$xpath = $this->ekko_xpath_parser( $dom );
				foreach ( $xpath->query( '/hub:resources/hub:file/@sha1' ) as $sha1 ) {
					$resources[ 'files' ][ ] = $sha1->value;
				}
				foreach ( $xpath->query( '/hub:resources/hub:video/@id' ) as $video_id ) {
					$resources[ 'videos' ][ ] = "{$video_id->value}";
				}
			}
			return $resources;
		}

		/**
		 * Mark the current course manifest as published
		 *
		 * @param int         $course_id
		 * @param string|null $session
		 *
		 * @return array|boolean
		 */
		public function publish_course( $course_id, $session = null ) {
			$params   = array(
				'hub'     => \Ekko\URI_HUB,
				'session' => ( $session ) ? $session : $this->get_session(),
				'course'  => $course_id,
			);
			$response = wp_remote_post(
				\GTO\Framework\Util\String::vnsprintf( self::ENDPOINT_PUBLISH_COURSE, $params ),
				array(
					'redirection' => 0,
					'headers'     => array(
						'Accept' => 'application/xml'
					),
				)
			);
			if ( $response[ 'response' ][ 'code' ] == 200 )
				return true;
			$dom    = \GTO\Framework\Util\XML::parse_xml_to_domdoc( $response[ 'body' ] );
			$errors = array();
			if ( $dom ) {
				$xpath = $this->ekko_xpath_parser( $dom );
				foreach ( $xpath->query( '/hub:errors/hub:error/@message' ) as $error ) {
					$errors[ ] = $error->value;
				}
			}
			if ( empty( $errors ) )
				$errors[ ] = __( 'Unknown Error', \Ekko\TEXT_DOMAIN );
			return $errors;
		}

		/**
		 * Get Course Settings
		 *
		 * @param int         $course_id
		 * @param string|null $session
		 *
		 * @return object|false
		 */
		public function get_settings( $course_id, $session = null ) {
			$params   = array(
				'hub'     => \Ekko\URI_HUB,
				'session' => ( $session ) ? $session : $this->get_session(),
				'course'  => $course_id,
			);
			$response = wp_remote_get(
				\GTO\Framework\Util\String::vnsprintf( self::ENDPOINT_SETTINGS, $params ),
				array(
					'redirection' => 0,
					'headers'     => array(
						'Content-Type' => 'application/json',
						'Accept'       => 'application/json',
					),
				)
			);
			if ( $response && array_key_exists( 'body', $response ) ) {
				return json_decode( $response[ 'body' ] );
			}
			return false;
		}

		/**
		 * Update Course Settings
		 *
		 * @param int         $course_id
		 * @param object      $settings
		 * @param string|null $session
		 *
		 * @return object|false
		 */
		public function update_settings( $course_id, $settings, $session = null ) {
			$params   = array(
				'hub'     => \Ekko\URI_HUB,
				'session' => ( $session ) ? $session : $this->get_session(),
				'course'  => $course_id,
			);
			$response = wp_remote_post(
				\GTO\Framework\Util\String::vnsprintf( self::ENDPOINT_SETTINGS, $params ),
				array(
					'redirection' => 0,
					'headers'     => array(
						'Content-Type' => 'application/x-www-form-urlencoded',
						'Accept'       => 'application/json',
					),
					'body'        => $settings,
				)
			);
			if ( $response && array_key_exists( 'body', $response ) ) {
				return json_decode( $response[ 'body' ] );
			}
			return false;
		}

		/**
		 * Get a list of GUID from the specified endpoint
		 *
		 * @param int         $course_id
		 * @param string      $endpoint
		 * @param string|null $session
		 *
		 * @return array
		 */
		public function get_users( $course_id, $endpoint = self::ENDPOINT_ENROLLED, $session = null ) {
			$params   = array(
				'hub'     => \Ekko\URI_HUB,
				'session' => ( $session ) ? $session : $this->get_session(),
				'course'  => $course_id,
			);
			$response = wp_remote_get(
				\GTO\Framework\Util\String::vnsprintf( $endpoint, $params ),
				array(
					'redirection' => 0,
					'headers'     => array(
						'Accept' => 'application/xml',
					),
				)
			);
			$users    = array();
			if ( $dom = \GTO\Framework\Util\XML::parse_xml_to_domdoc( $response[ 'body' ] ) ) {
				$xpath = $this->ekko_xpath_parser( $dom );
				foreach ( $xpath->query( '/hub:users/hub:user/@guid' ) as $guid ) {
					$users[ ] = strtolower( $guid->value );
				}
			}
			return $users;
		}

		/**
		 * Update the users at the specified endpoint
		 *
		 * @param int         $course_id
		 * @param array       $add
		 * @param array       $remove
		 * @param string      $endpoint
		 * @param string|null $session
		 *
		 * @return void
		 */
		public function update_users( $course_id, array $add = array(), array $remove = array(), $endpoint = self::ENDPOINT_ENROLLED, $session = null ) {
			$params = array(
				'hub'     => \Ekko\URI_HUB,
				'session' => ( $session ) ? $session : $this->get_session(),
				'course'  => $course_id,
			);

			//Add the Super Admin to all courses
			if ( $endpoint == self::ENDPOINT_ADMINS )
				$add[ ] = \Ekko\GUID_SUPER_ADMIN;

			$users = array_merge(
				array_map( function ( $guid ) {
					return 'add=' . rawurlencode( $guid );
				}, $add ),
				array_map( function ( $guid ) {
					return 'remove=' . rawurlencode( $guid );
				}, $remove )
			);

			if ( empty( $users ) )
				return;

			wp_remote_post(
				\GTO\Framework\Util\String::vnsprintf( $endpoint, $params ),
				array(
					'redirection' => 0,
					'headers'     => array(
						'Content-Type' => 'application/x-www-form-urlencoded',
					),
					'body'        => implode( '&', $users ),
				)
			);
		}

		/**
		 * Get the list of admin guids for the course
		 *
		 * @param int $course_id
		 *
		 * @return array
		 */
		public function get_admins( $course_id ) {
			return $this->get_users( $course_id, self::ENDPOINT_ADMINS );
		}

		/**
		 * Get the list of enrolled guids for the course
		 *
		 * @param int $course_id
		 *
		 * @return array
		 */
		public function get_enrolled( $course_id ) {
			return $this->get_users( $course_id, self::ENDPOINT_ENROLLED );
		}

		/**
		 * Update the admins of the course by adding and removing guids
		 *
		 * @param int   $course_id
		 * @param array $add
		 * @param array $remove
		 */
		public function update_admins( $course_id, array $add = array(), array $remove = array() ) {
			$this->update_users( $course_id, $add, $remove, self::ENDPOINT_ADMINS );
		}

		/**
		 * Update the enrolled users of the course by adding and removing guids
		 *
		 * @param int   $course_id
		 * @param array $add
		 * @param array $remove
		 */
		public function update_enrolled( $course_id, array $add = array(), array $remove = array() ) {
			$this->update_users( $course_id, $add, $remove, self::ENDPOINT_ENROLLED );
		}

		/**
		 * Synchronize the admins of the course
		 *
		 * @param int   $course_id
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
		 *
		 * @param int   $course_id
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
		 * Get Ekko Cloud Videos list
		 *
		 * @param $options
		 *
		 * @return array
		 */
		public function get_videos( $options = array() ) {
			$url      = add_query_arg(
				$options,
				\GTO\Framework\Util\String::vnsprintf( self::ENDPOINT_GET_VIDEOS, array(
					'hub'    => \Ekko\URI_HUB,
					'apikey' => \Ekko\HUB_API_KEY,
				) )
			);
			$response = wp_remote_get( $url,
				array(
					'redirection' => 0,
					'headers'     => array(
						'Accept' => 'application/json',
					),
				)
			);
			if ( $this->is_response_OK( $response ) && array_key_exists( 'body', $response ) ) {
				$data = json_decode( $response[ 'body' ], true );
				if ( null !== $data ) {
					if ( ! array_key_exists( 'videos', $data ) )
						$data[ 'videos' ] = array();
					return $data;
				}
			}
			return array();
		}

		/**
		 * Create a new Ekko Cloud Video
		 *
		 * Returns the ID of the new video record
		 *
		 * @param string $title
		 * @param string $group
		 *
		 * @return array|false
		 */
		public function create_video( $title, $group ) {
			$params   = array(
				'hub'    => \Ekko\URI_HUB,
				'apikey' => \Ekko\HUB_API_KEY,
			);
			$response = wp_remote_post(
				\GTO\Framework\Util\String::vnsprintf( self::ENDPOINT_CREATE_VIDEO, $params ),
				array(
					'redirection' => 0,
					'headers'     => array(
						'Content-Type' => 'application/x-www-form-urlencoded',
						'Accept'       => 'application/json',
					),
					'body'        => array(
						'title' => $title,
						'group' => $group,
					),
				)
			);
			if ( $this->is_response_OK( $response ) && array_key_exists( 'body', $response ) ) {
				return json_decode( $response[ 'body' ], true );
			}
			return false;
		}

		/**
		 * Inform Ekko Cloud Video system that the video has been uploaded and processing can begin
		 *
		 * @param string $video_id
		 * @param string $key
		 * @param string $bucket
		 *
		 * @return array|false
		 */
		public function process_video( $video_id, $key, $bucket ) {
			$params   = array(
				'hub'    => \Ekko\URI_HUB,
				'apikey' => \Ekko\HUB_API_KEY,
				'video'  => "{$video_id}",
			);
			$response = wp_remote_post(
				\GTO\Framework\Util\String::vnsprintf( self::ENDPOINT_PROCESS_VIDEO, $params ),
				array(
					'redirection' => 0,
					'headers'     => array(
						'Content-Type' => 'application/x-www-form-urlencoded',
						'Accept'       => 'application/json',
					),
					'body'        => array(
						's3_bucket'        => $bucket,
						's3_key'           => $key,
						's3_delete_source' => 'true',
					),
				)
			);
			if ( $this->is_response_OK( $response ) && array_key_exists( 'body', $response ) ) {
				return json_decode( $response[ 'body' ], true );
			}
			return false;
		}

		/**
		 * Get a video by ID
		 *
		 * @param $video_id
		 * @param $group
		 *
		 * @return array|false
		 */
		public function get_video( $video_id, $group ) {
			$url      = add_query_arg(
				array( 'group' => $group ),
				\GTO\Framework\Util\String::vnsprintf( self::ENDPOINT_GET_VIDEO, array(
					'hub'    => \Ekko\URI_HUB,
					'apikey' => \Ekko\HUB_API_KEY,
					'video'  => "{$video_id}",
				) )
			);
			$response = wp_remote_get( $url,
				array(
					'redirection' => 0,
					'headers'     => array(
						'Accept' => 'application/json',
					),
				)
			);
			if ( $this->is_response_OK( $response ) && array_key_exists( 'body', $response ) ) {
				return (array)json_decode( $response[ 'body' ], true );
			}
			return false;
		}

		/**
		 * @param $video_id
		 * @param $group
		 *
		 * @return array|false
		 */
		public function delete_video( $video_id, $group ) {
			$url      = add_query_arg(
				array( 'group' => $group ),
				\GTO\Framework\Util\String::vnsprintf( self::ENDPOINT_DELETE_VIDEO, array(
					'hub'    => \Ekko\URI_HUB,
					'apikey' => \Ekko\HUB_API_KEY,
					'video'  => "{$video_id}",
				) )
			);
			$response = wp_remote_request(
				$url,
				array(
					'method'  => 'DELETE',
					'headers' => array(
						'Accept' => 'application/json',
					),
				)
			);
			if ( $response && $response[ 'code' ] == 200 )
				return true;
			return false;
		}

		/**
		 * Add or remove authorized Courses for a Video
		 *
		 * @param int   $video_id
		 * @param array $add
		 * @param array $remove
		 */
		public function update_video_courses( $video_id, array $add = array(), array $remove = array() ) {
			$params = array(
				'hub'    => \Ekko\URI_HUB,
				'apikey' => \Ekko\HUB_API_KEY,
				'video'  => "{$video_id}",
			);

			$courses = array_merge(
				array_map( function ( $course_id ) {
					return 'add=' . rawurlencode( $course_id );
				}, $add ),
				array_map( function ( $course_id ) {
					return 'remove=' . rawurlencode( $course_id );
				}, $remove )
			);

			if ( empty( $courses ) )
				return;

			wp_remote_post(
				\GTO\Framework\Util\String::vnsprintf( self::ENDPOINT_VIDEO_COURSES, $params ),
				array(
					'redirection' => 0,
					'headers'     => array(
						'Content-Type' => 'application/x-www-form-urlencoded',
					),
					'body'        => implode( '&', $courses ),
				)
			);
		}

		/**
		 * Authorize a course to use a video
		 *
		 * @param int $video_id
		 * @param int $course_id
		 */
		public function add_course_to_video( $video_id, $course_id ) {
			$this->update_video_courses( $video_id, array( "{$course_id}" ) );
		}

		/**
		 * De-authorize a courses use of a video
		 *
		 * @param int $video_id
		 * @param int $course_id
		 */
		public function remove_course_from_video( $video_id, $course_id ) {
			$this->update_video_courses( $video_id, array(), array( "{$course_id}" ) );
		}

		/**
		 * Is the HTTP response successful
		 *
		 * @param array $response
		 *
		 * @return bool
		 */
		private function is_response_OK( $response ) {
			if ( $response && ! is_wp_error( $response ) ) {
				$code = (int)$response[ 'response' ][ 'code' ];
				if ( 200 <= $code || 300 > $code )
					return true;
			}
			return false;
		}

		/**
		 * Returns an xpath parser for the given dom with all Ekko XML namespaces registered
		 *
		 * @param \DOMDocument $dom
		 *
		 * @return \DOMXPath
		 */
		private function ekko_xpath_parser( $dom ) {
			return \GTO\Framework\Util\XML::xpath_parser( $dom, array(
				'hub'  => \Ekko\XMLNS_HUB,
				'ekko' => \Ekko\XMLNS_MANIFEST,
			) );
		}
	}
}
