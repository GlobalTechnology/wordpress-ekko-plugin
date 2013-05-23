<?php namespace GTO\Framework\Posts {

	/**
	 * WordPress WP_Post composite class
	 * @author Brian Zoetewey <brian.zoetewey@ccci.org>
	 *
	 * @property int $ID
	 * @property int $post_author
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
	 * @property int $post_parent
	 * @property string $guid
	 * @property int $menu_order
	 * @property string $post_type
	 * @property string $post_mime_type
	 * @property int $comment_count
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

	}

}