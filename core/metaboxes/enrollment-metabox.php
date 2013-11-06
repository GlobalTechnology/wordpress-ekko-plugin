<?php namespace Ekko\Core\Metaboxes {

	final class EnrollmentMetabox extends \GTO\Framework\Posts\PostMetabox {

		const EKKO_ENROLLMENT = 'ekko-enrollment';

		final protected function __construct() {
			$this->id       = 'enrollmentdiv';
			$this->location = self::LOC_STATIC_SIDE;
		}

		/**
		 * @param \Ekko\Core\CoursePost $course
		 * @param array                 $metabox
		 */
		public function display( $course, $metabox ) {
			$enrollment = $course->enrollment;
			?>
			<input type="hidden" name="<?php echo self::EKKO_ENROLLMENT; ?>" value="<?php echo _wp_specialchars( json_encode( $enrollment ), ENT_QUOTES, 'UTF8', true ); ?>" />
			<div id="enrollmentdiv" class="ekko-bootstrap container-fluid">
			<div class="row-fluid">
				<div class="ekko-item" ng-controller="EnrollmentController">
					<div class="navbar ekko-item-purple">
						<div class="navbar-inner">
							<div class="container">
								<div class="pull-left section-toggle" title="<?php esc_attr_e( 'click to toggle', \Ekko\TEXT_DOMAIN ); ?>" ng-click="enrollment.active = !enrollment.active">
									<span ng-class="{'icon-chevron-right':!enrollment.active, 'icon-chevron-down':enrollment.active}"></span>
								</div>
								<div class="brand"><?php esc_html_e( 'Enrollment', \Ekko\TEXT_DOMAIN ); ?></div>
							</div>
						</div>
						<div collapse="!enrollment.active" ng-class="{in:enrollment.active}">
							<div class="well">
								<label class="radio">
									<input type="radio" ng-model="enrollment.type" value="<?php echo $course::ENROLLMENT_PUBLIC; ?>" disabled>
									<?php echo esc_html_x( 'Public', 'enrollment type', \Ekko\TEXT_DOMAIN ); ?>
									<span class="help-block">
										<small>
											<?php esc_html_e( 'Anyone may take this course without enrolling.', \Ekko\TEXT_DOMAIN ); ?>
										</small>
									</span>
								</label>

								<div class="clear"></div>
								<label class="radio">
									<input type="radio" ng-model="enrollment.type" value="<?php echo $course::ENROLLMENT_OPEN; ?>">
									<?php echo esc_html_x( 'Open Enrollment', 'enrollment type', \Ekko\TEXT_DOMAIN ); ?>
									<span class="help-block">
										<small>
											<?php esc_html_e( 'Students may enroll in this course.', \Ekko\TEXT_DOMAIN ); ?>
										</small>
									</span>
								</label>
								<label class="radio">
									<input type="radio" ng-model="enrollment.type" value="<?php echo $course::ENROLLMENT_APPROVAL; ?>">
									<?php echo esc_html_x( 'Approval Needed', 'enrollment type', \Ekko\TEXT_DOMAIN ); ?>
									<span class="help-block">
										<small>
											<?php esc_html_e( 'Students must request enrollment and be approved by instructors.', \Ekko\TEXT_DOMAIN ); ?>
										</small>
									</span>
								</label>
								<label class="radio">
									<input type="radio" ng-model="enrollment.type" value="<?php echo $course::ENROLLMENT_MANAGED; ?>">
									<?php echo esc_html_x( 'Instructor Managed', 'enrollment type', \Ekko\TEXT_DOMAIN ); ?>
									<span class="help-block">
										<small>
											<?php esc_html_e( 'Instructors will manage enrollment of students.', \Ekko\TEXT_DOMAIN ); ?>
										</small>
									</span>
								</label>
							</div>
						</div>
					</div>
				</div>
			</div>
			</div><?php
		}

		/**
		 * @param \Ekko\Core\CoursePost $course
		 */
		final public function save( $course ) {
			if ( array_key_exists( self::EKKO_ENROLLMENT, $_POST ) )
				$course->enrollment = json_decode( stripslashes( $_POST[ self::EKKO_ENROLLMENT ] ) );
		}
	}
}
