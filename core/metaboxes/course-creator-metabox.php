<?php namespace Ekko\Core\Metaboxes {

	final class CourseCreatorMetabox extends \GTO\Framework\Posts\PostMetabox {

		const EKKO_LESSONS = 'ekko-lessons';

		final protected function __construct() {
			$this->id = 'ekkocousecreatordiv';
			$this->location = self::LOC_STATIC_AFTER_EDITOR;
			$this->ajax = array(
				'ekko-thumbnail' => array( &$this, 'get_ekko_thumbnail' ),
				'ekko-oembed' => array( &$this, 'fetch_oembed_response' ),
			);
		}

		/**
		 * (non-PHPdoc)
		 * @see \GTO\Framework\Posts\PostMetabox::display()
		 *
		 * @param \Ekko\Core\CoursePost $course
		 * @param array|null $metabox
		 */
		final public function display( $course, $metabox ) {
			$lessons = json_encode( $course->lessons );
			?>
			<input type="hidden" name="<?php echo self::EKKO_LESSONS; ?>" value="<?php echo _wp_specialchars( $lessons, ENT_QUOTES, 'UTF8', true ); ?>" />
			<div class="ekko-bootstrap container-fluid">
				<div ng-controller="CourseController">

					<div class="row-fluid course-nav">
						<div class="span12">
							<h1 class="pull-left">Course Content</h1>
							<div class="pull-right ekko-content-buttons">
								<a class="btn btn-ekko" href ng-click="lessons.push( $ekko.lesson() )"><i class="icon-plus icon-white"></i> Lesson</a>
								<a class="btn btn-pimp" href ng-click="lessons.push( $ekko.quiz() )"><i class="icon-plus icon-white"></i> Quiz</a>
							</div>
						</div>
					</div>

					<div class="course-items" ui-sortable="sortableOpts" ng-model="lessons">
						<div ng-repeat="item in lessons" ng-include="'ekko-' + item.type"></div>
					</div>

				</div>
			</div>
			<?php
		}

		/**
		 * (non-PHPdoc)
		 * @see \GTO\Framework\Posts\PostMetabox::save()
		 * @param \Ekko\Core\CoursePost $course
		 */
		final public function save( $course ) {
			if( array_key_exists( self::EKKO_LESSONS, $_POST ) )
				$course->lessons = json_decode( stripslashes( $_POST[ self::EKKO_LESSONS ] ) );
		}

		final public function get_ekko_thumbnail() {
			$id = array_key_exists( 'id', $_REQUEST ) ? (int) stripslashes( $_REQUEST[ 'id'] ) : null;
			if( $id ) {
				$image = image_get_intermediate_size( $id, 'ekko-thumbnail' );
				$src = wp_get_attachment_image_src( $id, 'ekko-thumbnail', true );
				wp_redirect( $src[0] );
				exit();
			}
			wp_die(-1);
		}

		final public function fetch_oembed_response() {
			global $logger;
			$url = array_key_exists( 'url', $_REQUEST ) ? $_REQUEST[ 'url' ] : null;
			if( $url ) {
				$data = null;
				add_filter( 'oembed_dataparse', function( $output, $oembed_data, $url ) use ( &$data ) { $data = $oembed_data; return $output; }, 10, 3 );
				$response = wp_oembed_get( $url );
				if( $data )
					wp_send_json_success( $data );
			}
			wp_send_json_error();
		}

	}
}