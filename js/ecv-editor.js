window.ekko = window.ekko || {};

( function ( $ ) {
	var workflows = {};
	var editors = {};

	ekko.media.editor = {

		add: function ( id, options ) {
			var editor = this.get( id );

			if ( editor ) {
				return editor;
			}

			editor = editors[ id ] = ekko.media( _.defaults( options || {}, {
				state:    'insert',
				multiple: false
			} ) );

			editor.on( 'insert', function ( selection ) {
				var media = selection.single();
				this.trigger( 'add-media', {
					mediaType:  selection instanceof ekko.media.model.Selection ? 'ecv' : 'file',
					attributes: media.attributes
				} );
			}, editor );

			editor.on( 'select', function () {
				this.trigger( 'add-media', {
					mediaType:  'embed',
					attributes: this.state().props.toJSON()
				} );
			}, editor );

			editor.setState( editor.options.state );
			return editor;
		},

		get: function ( id ) {
			return editors[ id ];
		},

		remove: function ( id ) {
			delete editors[ id ];
		},

		open: function ( id, options ) {
			var editor;

			editor = this.get( id );

			if ( !editor ) {
				editor = this.add( id, options );
			}

			return editor.open();
		}
	};
	_.bindAll( ekko.media.editor, 'open' );

	/**
	 * @namespace ekko.media
	 */
	ekko.media.EkkoBannerImage = {
		get: function () {
			return wp.media.view.settings.post.featuredImageId;
		},

		set: function ( id ) {
			var settings = wp.media.view.settings;

			settings.post.featuredImageId = id;

			wp.media.post( 'ekko-set-course-banner', {
				json:      true,
				post_id:   settings.post.id,
				banner_id: settings.post.featuredImageId,
				_wpnonce:  settings.post.nonce
			} ).done( function ( html ) {
					$( '.well', '#coursebannerdiv' ).html( html );
				} );
		},

		frame: function () {
			if ( this._frame ) {
				return this._frame;
			}

			this._frame = wp.media( {
				state:  'featured-image',
				states: [ new wp.media.controller.FeaturedImage( {
					title: ekko.l10n.setBannerTitle
				} ) ]
			} );

			this._frame.on( 'toolbar:create:featured-image', function ( toolbar ) {
				this.createSelectToolbar( toolbar, {
					text: ekko.l10n.setBanner
				} );
			}, this._frame );

			this._frame.state( 'featured-image' ).on( 'select', this.select );
			return this._frame;
		},

		select: function () {
			var settings = wp.media.view.settings,
				selection = this.get( 'selection' ).single();

			if ( !settings.post.featuredImageId ) {
				return;
			}

			ekko.media.EkkoBannerImage.set( selection ? selection.id : -1 );
		},

		init: function () {
			$( '#coursebannerdiv' ).on( 'click', 'a.btn', function ( event ) {
				event.preventDefault();
				// Stop propagation to prevent thickbox from activating.
				event.stopPropagation();
				ekko.media.EkkoBannerImage.frame().open();
			} );
		}
	};
	$( ekko.media.EkkoBannerImage.init );

	ekko.media.thumbnail = {
		add: function ( id, options ) {
			var workflow = this.get( id );

			if ( workflow ) {
				return workflow;
			}

			workflow = workflows[ id ] = new wp.media.view.MediaFrame.Select( {
				title:      ekko.l10n.addThumbnailTitle,
				filterable: false,
				library:    { type: 'image' },
				multiple:   false
			} );

			return workflow;
		},

		get: function ( id ) {
			return workflows[ id ];
		},

		remove: function ( id ) {
			delete workflows[ id ];
		},

		open: function ( id, options ) {
			var workflow;

			workflow = this.get( id );

			if ( !workflow ) {
				workflow = this.add( id, options );
			}

			return workflow.open();
		}
	};
	_.bindAll( ekko.media.thumbnail, 'open' );

}( jQuery ) );
