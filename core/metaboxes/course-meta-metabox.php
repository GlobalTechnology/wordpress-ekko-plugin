<?php namespace Ekko\Core\Metaboxes {

	final class CourseMetaMetabox extends \GTO\Framework\Posts\PostMetabox {

		final protected function __construct() {
			$this->id = 'ekkocoursemetadiv';
			$this->location = self::LOC_STATIC_AFTER_TITLE;
		}

		/**
		 * (non-PHPdoc)
		 * @see \GTO\Framework\Posts\PostMetabox::display()
		 *
		 * @param \Ekko\Core\CoursePost $course
		 * @param array|null $metabox
		 */
		final public function display( $course, $metabox ) {
			?><div class="ekko-bootstrap container-fluid">
				<div class="row-fluid">
					<div class="ekko-item">
						<div class="navbar">
							<div class="navbar-inner">
								<div class="container">
									<div class="pull-left section-toggle" title="click to toggle" data-toggle="collapse" data-target="#course-metadata">
										<span class="icon-chevron-down"></span>
									</div>
									<div class="brand">Description</div>
								</div>
							</div>
							<div id="course-metadata" class="collapse <?php if( $course->show_metadata ) echo 'in'; ?>">
								<input type="hidden" name="show-course-metadata" id="show-course-metadata" value="<?php echo $course->show_metadata ? '1' : '0'; ?>" />
								<div class="well ekko-item-assets">
									<input name="description" type="text" placeholder="Description" class="input-block-level" value="<?php echo esc_attr( $course->description ); ?>" />
									<div class="navbar">
										<div class="navbar-inner">
											<div class="container">
												<div class="brand">Author</div>
												<div class="navbar-form">
													<input name="author_name" type="text" placeholder="Name" class="span3" value="<?php echo esc_attr( $course->author_name ); ?>" />
													<input name="author_email" type="text" placeholder="Email" class="span3" value="<?php echo esc_attr( $course->author_email ); ?>" />
													<input name="author_url" type="text" placeholder="URL" class="span3" value="<?php echo esc_attr( $course->author_url ); ?>" />
												</div>
											</div>
										</div>
									</div>
									<div class="navbar">
										<div class="navbar-inner">
											<div class="container">
												<div class="brand">Copyright</div>
												<div class="navbar-form">
													<div class="input-prepend span9">
														<span class="add-on">&copy;</span>
														<input name="copyright" type="text" placeholder="Copyright" class="input-block-level" value="<?php echo esc_attr( $course->copyright ); ?>" />
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<hr/>
			</div><?php
		}

		/**
		 * (non-PHPdoc)
		 * @see \GTO\Framework\Posts\PostMetabox::save()
		 * @param \Ekko\Core\CoursePost $course
		 */
		final public function save( $course ) {
			if( array_key_exists( 'show-course-metadata', $_POST ) )
				$course->show_metadata = ! $_POST[ 'show-course-metadata' ];
			if( array_key_exists( 'description', $_POST ) )
				$course->description = stripslashes( $_POST[ 'description' ] );
			if( array_key_exists( 'author_name', $_POST ) )
				$course->author_name = stripslashes( $_POST[ 'author_name' ] );
			if( array_key_exists( 'author_email', $_POST ) )
				$course->author_email = stripslashes( $_POST[ 'author_email' ] );
			if( array_key_exists( 'author_url', $_POST ) )
				$course->author_url = stripslashes( $_POST[ 'author_url' ] );
			if( array_key_exists( 'copyright', $_POST ) )
				$course->copyright = stripslashes( $_POST[ 'copyright' ] );
		}
	}
}