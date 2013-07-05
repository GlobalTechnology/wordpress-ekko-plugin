<?php namespace Ekko\Core\Metaboxes {

	final class CompleteMetabox extends \GTO\Framework\Posts\PostMetabox {

		const EKKO_COMPLETE = 'ekko-complete';

		final protected function __construct() {
			$this->id = 'coursecompletediv';
			$this->location = self::LOC_STATIC_SIDE;
		}

		/**
		 * (non-PHPdoc)
		 * @see \GTO\Framework\Posts\PostMetabox::display()
		 * @param \Ekko\Core\CoursePost $course
		 */
		final public function display( $course, $metabox ) {
			$complete = json_encode( $course->complete );
			?>
			<input type="hidden" name="<?php echo self::EKKO_COMPLETE; ?>" value="<?php echo _wp_specialchars( $complete, ENT_QUOTES, 'UTF8', true ); ?>" />
			<div class="ekko-bootstrap container-fluid">

				<div ng-controller="CourseCompleteController">
				<!--
					<div class="row-fluid course-nav">
						<div class="span12">
							<h1 class="pull-left"><?php esc_html_e( 'Course Completed', \Ekko\TEXT_DOMAIN ); ?></h1>
						</div>
					</div>
				-->
					<div class="ekko-item">
						<div class="navbar ekko-item-yellow">
							<div class="navbar-inner">
								<div class="container">
									<div class="pull-left section-toggle" title="<?php esc_attr_e( 'click to toggle', \Ekko\TEXT_DOMAIN ); ?>" ng-click="complete.active = !complete.active">
										<span ng-class="{'icon-chevron-right':!complete.active, 'icon-chevron-down':complete.active}"></span>
									</div>
									<div class="brand"><?php esc_html_e( 'Course Complete', \Ekko\TEXT_DOMAIN ); ?></div>
								</div>
							</div>
							<div collapse="!complete.active" ng-class="{in:icomplete.active}">
								<div class="well">
									<textarea ck-editor="ckeditor" ng-model="complete.message"></textarea>
								</div>
							</div>
						</div>
					</div>

				</div>
			</div>
			<?php

		}

		final public function save( $course ) {
			if( array_key_exists( self::EKKO_COMPLETE, $_POST ) )
				$course->complete = json_decode( stripslashes( $_POST[ self::EKKO_COMPLETE ] ) );
		}

	}
}