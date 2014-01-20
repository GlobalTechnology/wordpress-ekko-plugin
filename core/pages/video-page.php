<?php namespace Ekko\Core\Pages {

	final class VideoPage extends \GTO\Framework\Admin\AdminPage {

		private $video_list;

		final protected function __construct() {
			$course_post_type = \Ekko\Core\CoursePostType::singleton()->post_type();

			add_submenu_page(
				"edit.php?post_type={$course_post_type}",
				__( 'Ekko Cloud Videos', \Ekko\TEXT_DOMAIN ),
				__( 'Cloud Videos', \Ekko\TEXT_DOMAIN ),
				'edit_posts',
				'ekko-cloud-videos',
				array( &$this, 'display_page' )
			);

			add_action( 'load-ekko-course_page_ekko-cloud-videos', array( &$this, 'init_videos_list' ), 0, 0 );
			add_action( 'load-ekko-course_page_ekko-cloud-videos', array( &$this, 'save_changes' ), 10, 0 );
		}

		final public function init_videos_list() {
			$this->video_list = new \Ekko\Core\Tables\VideoListTable();
		}

		final public function save_changes() {
			switch ( $this->video_list->current_action() ) {
				case 'delete':
					if ( array_key_exists( 'videos', $_POST ) && is_array( $_POST[ 'videos' ] ) ) {
						$videos = $_POST[ 'videos' ];
						foreach ( $videos as $video_id ) {
							\Ekko\Core\Services\Hub::singleton()->delete_video( $video_id, get_current_blog_id() );
						}
						if ( count( $videos ) > 0 ) {
							wp_redirect( wp_unslash( $_REQUEST['_wp_http_referer'] ) );
							exit;
						}
					}
					break;
				default:
					break;
			}
		}

		final public function display_page() {
			$this->video_list->prepare_items();
			?>
			<div class="wrap">

				<div id="icon-post" class="icon32"><br /></div>
				<h2>
					<?php _e( 'Ekko Cloud Videos', \Ekko\TEXT_DOMAIN ); ?>
					<a href="edit.php?post_type=ekko-course" class="add-new-h2">Return to Courses</a>
				</h2>

				<form action="" method="post">
					<?php $this->video_list->display(); ?>
				</form>
				<br class="clear" />
			</div>
		<?php
		}

	}
}
