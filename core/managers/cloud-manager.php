<?php namespace Ekko\Core\Managers {

	/**
	 * Class CloudManager
	 * @package Ekko\Core\Managers
	 * @method static \Ekko\Core\Managers\CloudManager singleton()
	 */
	final class CloudManager extends \GTO\Framework\Singleton {

		const VIDEO_STATE_NEW        = 'NEW';
		const VIDEO_STATE_NEW_MASTER = 'NEW_MASTER';
		const VIDEO_STATE_ENCODING   = 'ENCODING';
		const VIDEO_STATE_CHECK      = 'CHECK';
		const VIDEO_STATE_ENCODED    = 'ENCODED';
		const VIDEO_STATE_PENDING    = 'PENDING';

		final protected function __construct() {
			add_action( 'admin_enqueue_scripts', array( &$this, 'register_scripts_styles' ), 5, 1 );
			add_action( 'print_media_templates', array( &$this, 'media_templates' ), 10, 0 );

			if ( defined( 'DOING_AJAX' ) && DOING_AJAX === true ) {
				//Ekko Cloud Videos
				add_action( 'wp_ajax_ecv-query-videos', array( &$this, 'query_videos' ), 10, 0 );
				add_action( 'wp_ajax_ecv-create-video', array( &$this, 'create_video' ), 10, 0 );
				add_action( 'wp_ajax_ecv-process-video', array( &$this, 'process_video' ), 10, 0 );
				add_action( 'wp_ajax_ecv-get-video', array( &$this, 'get_video' ), 10, 0 );
				add_action( 'wp_ajax_ecv-video-thumbnail', array( &$this, 'video_thumbnail' ), 10, 0 );

				//OEmbed
				add_action( 'wp_ajax_ecv-oembed-video', array( &$this, 'oembed_video' ), 10, 0 );
			}
		}

		final public function register_scripts_styles( $hook_suffix ) {
			//Ekko Cloud Video
			wp_register_script( 'ekko-cloud-video', \Ekko\PLUGIN_URL . 'js/cloud/ecv.js', array( 'media-editor', 'ecv-plupload' ) );

			//Ekko Cloud Video Uploader
			wp_register_script( 'ecv-plupload', \Ekko\PLUGIN_URL . 'js/cloud/plupload.js', array( 'plupload-all' ), false, false );
			$s3_bucket    = 'ecv-uploads';
			$s3_policy    = base64_encode( json_encode( array(
				'expiration' => date( 'Y-m-d\TH:i:s.000\Z', strtotime( '+1 day' ) ),
				'conditions' => array(
					array( 'bucket' => $s3_bucket ),
					array( 'acl' => 'public-read' ),
					array( 'starts-with', '$key', '' ),
					array( 'starts-with', '$Content-Type', '' ),
					array( 'starts-with', '$name', '' ),
					array( 'starts-with', '$Filename', '' ),
					array( 'success_action_status' => '201' ),
				),
			) ) );
			$s3_signature = base64_encode( hash_hmac( 'sha1', $s3_policy, \Ekko\AWS_ECV_SECRET_KEY, true ) );
			wp_localize_script( 'ecv-plupload', '_ecvPluploadSettings', array(
				'defaults'      => array(
					'runtimes'            => 'html5,silverlight,flash',
					'url'                 => sprintf( 'https://%1$s.s3.amazonaws.com:443/', $s3_bucket ),
					'file_data_name'      => 'file',
					'flash_swf_url'       => includes_url( 'js/plupload/plupload.flash.swf' ),
					'silverlight_xap_url' => includes_url( 'js/plupload/plupload.silverlight.xap' ),
					'filters'             => array(
						array( 'title' => 'Video files', 'extensions' => 'avi,mov,mp4,m4v,mkv,mpg,mpeg,3gp,flv' ),
					),
					'max_file_size'       => '1gb',
					'multipart_params'    => array(
						'Filename'              => '${filename}',
						'acl'                   => 'public-read',
						'Content-Type'          => '',
						'success_action_status' => '201',
						'AWSAccessKeyId'        => \Ekko\AWS_ECV_ACCESS_ID,
						'policy'                => $s3_policy,
						'signature'             => $s3_signature,
					),
				),
				'browser'       => array(
					'mobile'    => false,
					'supported' => true,
				),
				'limitExceeded' => false,
			) );

			wp_localize_script( 'ekko-cloud-video', '_ekkoECVL10n', array(
				'setBanner'         => __( 'Set banner image', \Ekko\TEXT_DOMAIN ),
				'setBannerTitle'    => __( 'Set Banner Image', \Ekko\TEXT_DOMAIN ),
				'addMediaTitle'     => __( 'Add Media', \Ekko\TEXT_DOMAIN ),
				'addMedia'          => __( 'Add media', \Ekko\TEXT_DOMAIN ),
				'addThumbnailTitle' => __( 'Select a Thumbnail Image', \Ekko\TEXT_DOMAIN ),
				'youTubeTitle'      => __( 'YouTube', \Ekko\TEXT_DOMAIN ),
				'vimeoTitle'        => __( 'Vimeo', \Ekko\TEXT_DOMAIN ),
			) );
		}

		final public function media_templates() {
			include( \Ekko\PLUGIN_DIR . 'templates/ecv-template.php' );
		}

		final public function query_videos() {
			if ( ! current_user_can( 'upload_files' ) )
				wp_send_json_error();

			$query = isset( $_REQUEST[ 'query' ] ) ? (array)$_REQUEST[ 'query' ] : array();
			$args  = wp_parse_args( $query, array(
				'posts_per_page' => 40,
				'paged'          => 1,
			) );

			$limit = (int)$args[ 'posts_per_page' ];
			$page  = (int)$args[ 'paged' ];

			$results = \Ekko\Core\Services\Hub::singleton()->get_videos( array(
				'group' => get_current_blog_id(),
				'start' => ( $page > 0 ? $page - 1 : $page ) * $limit,
				'limit' => $limit,
			) );
			$videos  = array();
			foreach ( $results[ 'videos' ] as $video ) {
				$videos[ ] = $this->normalize_video( $video );
			}
			wp_send_json_success( $videos );
		}

		final public function create_video() {
			if ( ! current_user_can( 'upload_files' ) )
				wp_send_json_error();
			$filename = isset( $_REQUEST[ 'filename' ] ) ? $_REQUEST[ 'filename' ] : '';

			$video = \Ekko\Core\Services\Hub::singleton()->create_video( $filename, get_current_blog_id() );
			if ( $video ) {
				//Add S3 object key for upload to S3
				$video[ 'key' ] = \GTO\Framework\Util\UUID::v4() . '/' . $filename;
				wp_send_json_success( $this->normalize_video( $video ) );
			}
			wp_send_json_error();
		}

		final public function process_video() {
			if ( ! current_user_can( 'upload_files' ) )
				wp_send_json_error();
			$id     = isset( $_REQUEST[ 'id' ] ) ? $_REQUEST[ 'id' ] : '';
			$key    = isset( $_REQUEST[ 'key' ] ) ? $_REQUEST[ 'key' ] : '';
			$bucket = isset( $_REQUEST[ 'bucket' ] ) ? $_REQUEST[ 'bucket' ] : '';

			$video = \Ekko\Core\Services\Hub::singleton()->process_video( $id, $key, $bucket );
			if ( $video ) {
				//State may not be immediately changed, set to CHECK so UI shows as processing
				$video[ 'state' ] = self::VIDEO_STATE_CHECK;
				wp_send_json_success( $this->normalize_video( $video ) );
			}
			wp_send_json_error();
		}

		final public function get_video() {
			$id    = isset( $_REQUEST[ 'id' ] ) ? $_REQUEST[ 'id' ] : false;
			$video = \Ekko\Core\Services\Hub::singleton()->get_video( $id, get_current_blog_id() );
			if ( $video ) {
				wp_send_json_success( $this->normalize_video( $video ) );
			}
			wp_send_json_error();
		}

		final public function video_thumbnail() {
			$id        = isset( $_REQUEST[ 'id' ] ) ? $_REQUEST[ 'id' ] : false;
			$thumbnail = \Ekko\PLUGIN_URL . 'images/default-video.png';

			$video = \Ekko\Core\Services\Hub::singleton()->get_video( $id, get_current_blog_id() );
			if ( $video ) {
				if ( array_key_exists( 'thumbnail', $video ) ) {
					$thumbnail = $video[ 'thumbnail' ];
				}
			}

			wp_redirect( $thumbnail );
			wp_die();
		}

		final public function oembed_video() {
			$url = array_key_exists( 'url', $_REQUEST ) ? $_REQUEST[ 'url' ] : null;
			if ( $url ) {
				$data = null;
				add_filter( 'oembed_dataparse', function ( $output, $oembed_data, $url ) use ( &$data ) {
					$data = $oembed_data;
					return $output;
				}, 10, 3 );
				wp_oembed_get( $url );
				if ( $data )
					wp_send_json_success( $data );
			}
			wp_send_json_error();
		}

		/**
		 * Normalize video object for display in UI
		 *
		 * @param array $video
		 *
		 * @return array
		 */
		protected function normalize_video( $video ) {
			$video = wp_parse_args( $video, array(
				'state' => self::VIDEO_STATE_NEW,
				'icon'  => \Ekko\PLUGIN_URL . 'images/default-video.png',
			) );

			//normalize video state for UI purposes
			if ( in_array( $video[ 'state' ], array( self::VIDEO_STATE_NEW_MASTER, self::VIDEO_STATE_ENCODING, self::VIDEO_STATE_CHECK ) ) ) {
				$video[ 'state' ] = self::VIDEO_STATE_PENDING;
			}

			if ( array_key_exists( 'thumbnail', $video ) ) {
				$video[ 'icon' ] = $video[ 'thumbnail' ];
				unset( $video[ 'thumbnail' ] );
			}

			return $video;
		}

	}

}
