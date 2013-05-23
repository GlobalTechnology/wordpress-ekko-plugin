;(function($) {
	var frames = {};

	wp.media.frames.ekkoLibrary = {

		add: function( id ) {
			var frame = this.get( id );

			if( frame )
				return frame;

			frame = frames[ id ] = wp.media({
				frame: 'select',
				title: 'Select Media'
			});

			return frame;
		},

		get: function( id ) {
			return frames[ id ];
		},

		remove: function( id ) {
			delete frames[ id ];
		},

		media_frame: function( id ) {
			return this.add( id );
		}
	};

})(jQuery);