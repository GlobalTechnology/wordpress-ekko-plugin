<?php namespace Ekko\Core\Managers {

	final class CloudVideoManager extends \GTO\Framework\Singleton {

		const VIDEO_STATE_NEW        = 'NEW';
		const VIDEO_STATE_NEW_MASTER = 'NEW_MASTER';
		const VIDEO_STATE_ENCODING   = 'ENCODING';
		const VIDEO_STATE_CHECK      = 'CHECK';
		const VIDEO_STATE_ENCODED    = 'ENCODED';

		final protected function __construct() {
			add_action( 'admin_enqueue_scripts', array( &$this, 'register_scripts_styles' ), 5, 1 );
			add_action( 'print_media_templates', array( &$this, 'media_templates' ), 10, 0 );

			if ( defined( 'DOING_AJAX' ) && DOING_AJAX === true ) {
				//Ekko Cloud Videos
				add_action( 'wp_ajax_ecv-query-videos', array( &$this, 'query_videos' ), 10, 0 );
				add_action( 'wp_ajax_ecv-create-video', array( &$this, 'create_video' ), 10, 0 );
				add_action( 'wp_ajax_ecv-process-video', array( &$this, 'process_video' ), 10, 0 );

				//OEmbed
				add_action( 'wp_ajax_ecv-oembed-video', array( &$this, 'oembed_video' ), 10, 0 );
			}
		}

		final public function register_scripts_styles( $hook_suffix ) {
			//Ekko Media MVC
			wp_register_script( 'ecv-models', \Ekko\PLUGIN_URL . 'js/ecv-models.js', array( 'media-editor' ) );
			wp_register_script( 'ecv-views', \Ekko\PLUGIN_URL . 'js/ecv-views.js', array( 'ecv-models', 'ecv-plupload' ) );
			wp_register_script( 'ecv-editor', \Ekko\PLUGIN_URL . 'js/ecv-editor.js', array( 'ecv-views' ) );

			//Ekko Cloud Video Uploader
			wp_register_script( 'ecv-plupload', \Ekko\PLUGIN_URL . 'js/ecv-plupload.js', array( 'plupload-all', 'ecv-models' ), false, false );
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

			wp_localize_script( 'ecv-views', '_ekkoECVL10n', array(
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
			include( \Ekko\PLUGIN_DIR . 'templates/media-template.php' );
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
			$start = ( $page > 0 ? $page - 1 : $page ) * $limit;
			$group = get_current_blog_id();

			$results = \Ekko\Core\Services\Hub::singleton()->get_videos( $group, $start, $limit );
			if ( ! array_key_exists( 'videos', $results ) ) {
				$results[ 'videos' ] = array();
			}
			$data = array();
			foreach ( $results[ 'videos' ] as $video ) {
				$data[ ] = $this->normalize_video( $video );
			}
			wp_send_json_success( $data );
		}

		final public function create_video() {
			if ( ! current_user_can( 'upload_files' ) )
				wp_send_json_error();
			$filename = isset( $_REQUEST[ 'filename' ] ) ? $_REQUEST[ 'filename' ] : '';
			$group    = get_current_blog_id();

			$data = \Ekko\Core\Services\Hub::singleton()->create_video( $filename, "{$group}" );
			if ( $data ) {
				$data = wp_parse_args( $data, array(
					//S3 Object key
					'key'   => \GTO\Framework\Util\UUID::v4() . '/' . $filename,
					'title' => $filename,
				) );
				wp_send_json_success( $this->normalize_video( $data ) );
			}
			wp_send_json_error();
		}

		final public function process_video() {
			if ( ! current_user_can( 'upload_files' ) )
				wp_send_json_error();
			$id     = isset( $_REQUEST[ 'id' ] ) ? $_REQUEST[ 'id' ] : '';
			$key    = isset( $_REQUEST[ 'key' ] ) ? $_REQUEST[ 'key' ] : '';
			$bucket = isset( $_REQUEST[ 'bucket' ] ) ? $_REQUEST[ 'bucket' ] : '';

			$data = \Ekko\Core\Services\Hub::singleton()->process_video( $id, $key, $bucket );
			if ( $data ) {
				wp_send_json_success( $this->normalize_video( $data ) );
			}
			wp_send_json_error();
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
		 * Normalize ECV Video object
		 *
		 * @param array $video
		 *
		 * @return array
		 */
		protected function normalize_video( $video ) {
			$video = wp_parse_args( $video, array(
				'state' => self::VIDEO_STATE_NEW,
				'icon'  => \Ekko\PLUGIN_URL . 'images/default-video.png',
//				'filename' => array_key_exists( 'title', $video ) ? $video[ 'title' ] : '',
			) );
			return $video;
		}

	}

}
