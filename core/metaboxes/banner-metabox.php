<?php namespace Ekko\Core\Metaboxes {

	final class BannerMetabox extends \GTO\Framework\Posts\PostMetabox {

		protected function __construct() {
			$this->id = 'coursebannerdiv';
			$this->location = self::LOC_STATIC_SIDE;
			$this->ajax = array(
				'ekko-set-course-banner' => array( &$this, 'set_banner_image' ),
			);
		}

		public function display( $course, $metabox ) {
			?><div id="coursebannerdiv" class="ekko-bootstrap container-fluid">
				<div class="row-fluid">
					<div class="ekko-item">
						<div class="navbar ekko-item-green">
							<div class="navbar-inner">
								<div class="container">
									<div class="brand">Banner</div>
									<div class="navbar-form pull-right">
										<div class="btn-group">
											<a class="btn btn-success" href="#"><i class="icon-picture icon-white"></i> Set Banner</a>
										</div>
									</div>
								</div>
							</div>
							<div>
								<div class="well">
									<?php echo $this->banner_thumbnail_html( $course ); ?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div><?php
		}

		final public function set_banner_image() {
			$post_ID = intval( $_POST[ 'post_id' ] );

			if ( ! current_user_can( 'edit_post', $post_ID ) )
				wp_die( -1 );

			$banner_id = intval( $_POST[ 'banner_id' ] );

			check_ajax_referer( "update-post_$post_ID" );

			if ( $banner_id == '-1' ) {
				if ( delete_post_thumbnail( $post_ID ) )
					wp_send_json_success( '' );
				else
					wp_die( 0 );
			}

			$course = \Ekko\Core\CoursePostType::singleton()->get_course( $post_ID );

			if ( set_post_thumbnail( $course->ID, $banner_id ) )
				wp_send_json_success( $this->banner_thumbnail_html( $course ) );

			wp_die( 0 );
		}

		private function banner_thumbnail_html( $course ) {
			$banner_id = (int) get_post_thumbnail_id( $course->ID );
			if( $banner_id > 0 ) {
				$banner_html = wp_get_attachment_image( $banner_id, 'ekko-banner-thumbnail' );
				if( $banner_html )
					return $banner_html;
			}
			return '';
		}

	}
}