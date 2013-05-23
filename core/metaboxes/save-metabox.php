<?php namespace Ekko\Core\Metaboxes {

	final class SaveMetabox extends \GTO\Framework\Posts\PostMetabox {

		final protected function __construct() {
			$this->id = 'ekksavecoursediv';
			$this->title = __( 'Options', \Ekko\TEXT_DOMAIN );
			$this->priority = 'high';
			$this->context = 'side';
		}

		public function display($post, $metabox) {
			?>
			<div class="submitbox" id="submitpost">
				<div id="major-publishing-actions" class="ekko-bootstrap container">
					<div class="row">
						<div class="pull-right">
							<input type="submit" name="publish" id="publish" value="Save" class="btn btn-ekko btn-large">
							<span class="spinner" style="display:none;"></span>
						</div>
					</div>
					<div class="clear"></div>
				</div>
			</div><?php
		}
	}
}