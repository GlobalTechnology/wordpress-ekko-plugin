<script type="text/html" id="tmpl-arclight-video">
	<# console.log( data ) #>
	<div class="attachment-preview {{ data.orientation }}">
		<div class="thumbnail">
			<div class="centered">
				<img src="{{ data.thumbnailUrl }}" draggable="false" />
			</div>
		</div>
		<div class="filename">
			<div>{{ data.name }}</div>
		</div>
		<# if ( data.buttons.close ) { #>
			<a class="close media-modal-icon" href="#" title="<?php _e('Remove'); ?>"></a>
		<# } #>

		<# if ( data.buttons.check ) { #>
			<a class="check" href="#" title="<?php _e('Deselect'); ?>"><div class="media-modal-icon"></div></a>
		<# } #>
	</div>
</script>
