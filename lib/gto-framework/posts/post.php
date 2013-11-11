<?php namespace GTO\Framework\Posts {

	/**
	 * WordPress WP_Post composite class
	 * @author Brian Zoetewey <brian.zoetewey@ccci.org>
	 *
	 * @property int    $ID
	 * @property int    $post_author
	 * @property string $post_date
	 * @property string $post_date_gmt
	 * @property string $post_content
	 * @property string $post_title
	 * @property string $post_excerpt
	 * @property string $post_status
	 * @property string $comment_status
	 * @property string $ping_status
	 * @property string $post_password
	 * @property string $post_name
	 * @property string $to_ping
	 * @property string $pinged
	 * @property string $post_modified
	 * @property string $post_modified_gmt
	 * @property string $post_content_filtered
	 * @property int    $post_parent
	 * @property string $guid
	 * @property int    $menu_order
	 * @property string $post_type
	 * @property string $post_mime_type
	 * @property int    $comment_count
	 */
	class Post {
		/**
		 * WordPress Post
		 * @var \WP_Post
		 */
		private $post;

		public function __construct( \WP_Post $post ) {
			$this->post = $post;
		}

		public function __isset( $key ) {
			return isset( $this->post->$key );
		}

		public function __get( $key ) {
			return $this->post->$key;
		}

		/**
		 * @return \WP_Post
		 */
		public function get_post() {
			return $this->post;
		}

		public function save() {
			wp_insert_post( $this->post->to_array() );
		}

		/**
		 * Get the value of a post transient.
		 *
		 * @param string $transient
		 *
		 * @return mixed
		 */
		final public function get_transient( $transient ) {
			$pre = apply_filters( 'pre_post_transient_' . $transient, false, $this->ID );
			if ( false !== $pre )
				return $pre;

			if ( wp_using_ext_object_cache() ) {
				$value = wp_cache_get( "{$transient}-{$this->ID}", 'post_transient' );
			}
			else {
				$transient_option = "_transient_{$transient}";
				if ( ! defined( 'WP_INSTALLING' ) ) {
					$transient_timeout = "_transient_timeout_{$transient}";
					$timeout           = get_post_meta( $this->ID, $transient_timeout, true );
					if ( $timeout != '' && $timeout < time() ) {
						delete_post_meta( $this->ID, $transient_timeout );
						delete_post_meta( $this->ID, $transient_option );
						$value = false;
					}
				}

				if ( ! isset( $value ) )
					$value = get_post_meta( $this->ID, $transient_option, true );
			}

			return apply_filters( 'post_transient_' . $transient, $value, $this->ID );
		}

		/**
		 * Set/update the value of a post transient.
		 *
		 * @param string $transient
		 * @param mixed  $value
		 * @param int    $expiration
		 *
		 * @return bool
		 */
		final public function set_transient( $transient, $value, $expiration = 0 ) {
			$value      = apply_filters( 'pre_set_post_transient_' . $transient, $value, $this->ID );
			$expiration = (int)$expiration;

			if ( wp_using_ext_object_cache() ) {
				$result = wp_cache_set( "{$transient}-{$this->ID}", $value, 'post_transient', $expiration );
			}
			else {
				$result = update_post_meta( $this->ID, "_transient_{$transient}", $value );
				if ( $expiration ) {
					update_post_meta( $this->ID, "_transient_timeout_{$transient}", time() + $expiration );
				}
			}
			if ( $result ) {
				do_action( 'set_post_transient_' . $transient, $value, $this->ID, $expiration );
			}
			return $result;
		}

		/**
		 * Delete a post transient
		 *
		 * @param string $transient
		 *
		 * @return bool
		 */
		final public function delete_transient( $transient ) {
			do_action( 'delete_post_transient_' . $transient, $transient, $this->ID );

			if ( wp_using_ext_object_cache() ) {
				$result = wp_cache_delete( "{$transient}-{$this->ID}", 'post_transient' );
			}
			else {
				$result = delete_post_meta( $this->ID, "_transient_{$transient}" );
				if ( $result )
					delete_post_meta( $this->ID, "_transient_timeout_{$transient}" );
			}

			if ( $result )
				do_action( 'deleted_post_transient', $transient );
			return $result;
		}
	}
}
