<?php namespace Ekko\Core\Tables {

	if ( ! class_exists( '\\WP_List_Table' ) )
		require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

	final class EkkoCloudVideosTable extends \WP_List_Table {

		public function __construct( $args = array() ) {
			parent::__construct( array(
				'singular' => 'video',
				'plural'   => 'videos',
				'screen'   => isset( $args[ 'screen' ] ) ? $args[ 'screen' ] : null,
				'ajax'     => false,
			) );
		}

		public function prepare_items() {
			$videos_per_page = 20;
			$paged           = $this->get_pagenum();
			$start           = ( $paged - 1 ) * $videos_per_page;

			$results = \Ekko\Core\Services\Hub::singleton()->get_videos(
				get_current_blog_id(),
				$start,
				$videos_per_page
			);

			$this->items = $results[ 'videos' ];

			$this->set_pagination_args( array(
				'total_items' => (int) $results[ 'total' ],
				'per_page'    => $videos_per_page,
			) );
		}

		public function get_columns() {
			$columns = array(
				'cb'        => '<input type="checkbox" />',
				'thumbnail' => 'Thumbnail',
				'state'     => 'State',
				'title'     => 'Title',
			);
			return $columns;
		}

		function column_cb( $item ) {
			return sprintf(
				'<input type="checkbox" name="videos[]" value="%2$s" />',
				'user',
				$item[ 'id' ]
			);
		}

		public function column_state( $item ) {
			echo $item[ 'state' ];
		}

		public function column_title( $item ) {
			echo $item[ 'title' ];
		}

		public function column_thumbnail( $item ) {
			?><img width="52" height="44" src="<?php echo \Ekko\PLUGIN_URL . 'images/default-video.png'; ?>" class="attachment-80x60" /><?php
		}

	}
}
