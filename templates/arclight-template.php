<?php
?>
<script type="text/html" id="tmpl-arclight-video">
	<div class="attachment-preview js--select-attachment portrait">
		<div class="thumbnail">
			<div class="centered">
				<img src="{{ data.media_thumbnail }}" draggable="false" />
			</div>
		</div>
		<div class="filename">
			<div>{{ data.name }}</div>
		</div>
		<# if ( data.buttons.check ) { #>
			<a class="check" href="#" title="<?php _e('Deselect'); ?>"><div class="media-modal-icon"></div></a>
		<# } #>
	</div>
</script>

<script type="text/html" id="tmpl-arclight-video-details">
	<h3><# if( 'series' === data.type ) { #><?php _e('Series Details'); ?><# } else { #><?php _e('Video Details'); ?><# } #></h3>
	<div class="attachment-info">
		<div class="thumbnail">
			<img src="{{ data.media_thumbnail }}" draggable="false" />
		</div>
		<div class="details">
			<div class="filename">{{ data.name }}</div>
			<div class="uploaded">{{ data.shortDescription }}</div>
			<# if( 'series' !== data.type ) { #>
				<div class="dimensions"><em>Runtime: {{ data.runtime }}</em></div>
			<# } else { #>
				<div class="dimensions"><em>Episodes: {{ data.groupContentCount }}</em></div>
			<# } #>
		</div>
	</div>
</script>
