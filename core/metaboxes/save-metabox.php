<?php namespace Ekko\Core\Metaboxes {

	final class SaveMetabox extends \GTO\Framework\Posts\PostMetabox {

		final protected function __construct() {
			$this->id       = 'savecoursediv';
			$this->location = self::LOC_STATIC_SIDE;
		}

		public function display( $post, $metabox ) {
			?>
			<div id="savecoursediv" class="ekko-bootstrap container-fluid">
			<div class="row-fluid">
				<div class="ekko-item">
					<div class="navbar ekko-item-blue">
						<div class="navbar-inner">
							<div class="container">
								<div class="brand"><?php esc_html_e( 'Options', \Ekko\TEXT_DOMAIN ); ?></div>
							</div>
						</div>
						<div>
							<div class="well">
								<div class="pull-left">
									<input type="submit" name="save" id="save" value="<?php esc_attr_e( 'Save', \Ekko\TEXT_DOMAIN ); ?>" class="btn btn-ekko btn-large">
								</div>
								<div class="pull-right">
									<input type="submit" name="publish" id="publish" value="<?php esc_attr_e( 'Publish', \Ekko\TEXT_DOMAIN ); ?>" class="btn btn-ekko btn-large">
								</div>
								<div class="clear"></div>
							</div>
						</div>
					</div>
				</div>
			</div>
			</div><?php
		}
	}
}
