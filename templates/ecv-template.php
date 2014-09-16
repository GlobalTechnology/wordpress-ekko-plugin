<script type="text/html" id="tmpl-oembed-video">
	<div class="video"></div>
</script>

<script type="text/html" id="tmpl-ecv-uploader-window">
	<div class="uploader-window-content">
		<h3><?php _e( 'Drop videos to upload to Ekko Cloud', \Ekko\TEXT_DOMAIN ); ?></h3>
	</div>
</script>

<script type="text/html" id="tmpl-ecv-uploader-inline">
	<# var messageClass = data.message ? 'has-upload-message' : 'no-upload-message'; #>
		<div class="uploader-inline-content {{ messageClass }}">
			<# if ( data.message ) { #>
				<h3 class="upload-message">{{ data.message }}</h3>
				<# } #>
		<?php if ( ! _device_can_upload() ) : ?>
			<h3 class="upload-instructions"><?php printf( __('The web browser on your device cannot be used to upload files. You may be able to use the <a href="%s">native app for your device</a> instead.'), 'http://wordpress.org/mobile/' ); ?></h3>
		<?php else : ?>
			<div class="upload-ui">
					<h3 class="upload-instructions drop-instructions"><?php _e( 'Drop video files anywhere to upload', \Ekko\TEXT_DOMAIN ); ?></h3>
					<a href="#" class="browser button button-hero"><?php _e( 'Select Files', \Ekko\TEXT_DOMAIN ); ?></a>
				</div>
					<div class="upload-inline-status"></div>
					<div class="post-upload-ui">
				<p class="max-upload-size"><?php
					_e( 'Maximum upload file size: 1GB.', \Ekko\TEXT_DOMAIN );
				?></p>
			</div>
		<?php endif; ?>
		</div>
</script>

<script type="text/html" id="tmpl-video">
	<div class="attachment-preview js--select-attachment type-ecv-video {{ data.orientation }}">
		<# if ( data.uploading ) { #>
			<div class="media-progress-bar">
				<div></div>
			</div>
		<# } else if( data.state == 'PENDING' ) { #>
			<img src="<?php echo \Ekko\PLUGIN_URL . 'images/video-processing.gif' ?>" class="icon" draggable="false" />
			<div class="pending">
				<div>Processing</div>
			</div>
		<# } else { #>
			<img src="{{ data.icon }}" class="icon" draggable="false" />
		<# } #>
		<div class="filename">
			<div>{{ data.title }}</div>
		</div>

		<# if ( data.buttons.close ) { #>
			<a class="close media-modal-icon" href="#" title="<?php _e('Remove'); ?>"></a>
		<# } #>

		<# if ( data.buttons.check ) { #>
			<a class="check" href="#" title="<?php _e('Deselect'); ?>">
				<div class="media-modal-icon"></div>
			</a>
		<# } #>
	</div>
</script>
