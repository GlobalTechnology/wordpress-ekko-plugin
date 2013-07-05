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
					<div class="ekko-item" ng-controller="MetaController" ng-init="metadata_active = <?php echo $course->show_metadata ? 'true' : 'false'; ?>" ng-include="'ekko-metadata'"></div>
				</div>
				<hr/>
				<scrip<?php ?>t type="text/ng-template" id="ekko-metadata">
					<div class="navbar">
						<div class="navbar-inner">
							<div class="container">
								<div class="pull-left section-toggle" title="<?php esc_attr_e( 'click to toggle', \Ekko\TEXT_DOMAIN ); ?>" ng-click="metadata_active = !metadata_active">
									<span ng-class="{'icon-chevron-right':!metadata_active, 'icon-chevron-down':metadata_active}"></span>
								</div>
								<div class="brand"><?php echo esc_html_x( 'Description', 'input label', \Ekko\TEXT_DOMAIN ); ?></div>
							</div>
						</div>
						<div collapse="!metadata_active" ng-class="{in:metadata_active}">
							<input type="checkbox" ng-model="metadata_active" name="show-course-metadata" value="1" style="display: none;" />
							<div class="well ekko-item-assets">
								<input name="description" type="text" placeholder="<?php echo esc_attr_x( 'Description', 'input placeholder', \Ekko\TEXT_DOMAIN ); ?>" class="input-block-level" value="<?php echo esc_attr( $course->description ); ?>" />
								<div class="navbar">
									<div class="navbar-inner">
										<div class="container">
											<div class="brand"><?php echo esc_html_x( 'Author', 'input label', \Ekko\TEXT_DOMAIN ); ?></div>
											<div class="navbar-form">
												<input name="author_name" type="text" placeholder="<?php echo esc_attr_x( 'Name', 'input placeholder', \Ekko\TEXT_DOMAIN ); ?>" class="span3" value="<?php echo esc_attr( $course->author_name ); ?>" />
												<input name="author_email" type="text" placeholder="<?php echo esc_attr_x( 'Email', 'input placeholder', \Ekko\TEXT_DOMAIN ); ?>" class="span3" value="<?php echo esc_attr( $course->author_email ); ?>" />
												<input name="author_url" type="text" placeholder="<?php echo esc_attr_x( 'URL', 'input placeholder', \Ekko\TEXT_DOMAIN ); ?>" class="span3" value="<?php echo esc_attr( $course->author_url ); ?>" />
											</div>
										</div>
									</div>
								</div>
								<div class="navbar">
									<div class="navbar-inner">
										<div class="container">
											<div class="brand"><?php echo esc_html_x( 'Copyright', 'input label', \Ekko\TEXT_DOMAIN ); ?></div>
											<div class="navbar-form">
												<div class="input-prepend span9">
													<span class="add-on">&copy;</span>
													<input name="copyright" type="text" placeholder="<?php echo esc_attr_x( 'Copyright', 'input placeholder', \Ekko\TEXT_DOMAIN ); ?>" class="input-block-level" value="<?php echo esc_attr( $course->copyright ); ?>" />
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</script>
			</div><?php
		}

		/**
		 * (non-PHPdoc)
		 * @see \GTO\Framework\Posts\PostMetabox::save()
		 * @param \Ekko\Core\CoursePost $course
		 */
		final public function save( $course ) {
			$course->show_metadata = array_key_exists( 'show-course-metadata', $_POST ) ? false : true;
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