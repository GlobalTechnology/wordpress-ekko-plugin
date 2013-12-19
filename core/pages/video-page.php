<?php namespace Ekko\Core\Pages {

	final class VideoPage extends \GTO\Framework\Admin\AdminPage {

		private $videos_list;

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
		}

		final public function init_videos_list() {
			$this->videos_list = new \Ekko\Core\Tables\EkkoCloudVideosTable();
		}

		final public function display_page() {
			$this->videos_list->prepare_items();
			?>
			<div class="wrap">

				<div id="icon-users" class="icon32"><br /></div>
				<h2>
					<?php _e( 'Ekko Cloud Videos', \Ekko\TEXT_DOMAIN ); ?>
					<a href="edit.php?post_type=ekko-course" class="add-new-h2">Return to Courses</a>
				</h2>

				<form action="" method="get">
					<?php $this->videos_list->display(); ?>
				</form>
				<br class="clear" />
			</div>
		<?php
		}

	}
}
