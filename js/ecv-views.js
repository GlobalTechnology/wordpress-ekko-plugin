(function ( $ ) {
	var l10n;

	l10n = ekko.l10n = typeof _ekkoECVL10n === 'undefined' ? {} : _ekkoECVL10n;

	/**
	 * ========================================================================
	 * CONTROLLERS
	 * ========================================================================
	 */

	/**
	 * Ekko Cloud Video Library
	 *
	 * @namespace ekko.media.controller
	 * @class ekko.media.controller.ECVLibrary
	 * @augments wp.media.controller.Library
	 */
	ekko.media.controller.ECVLibrary = wp.media.controller.Library.extend( /** @lends ekko.media.controller.ECVLibrary */ {
		defaults: {
			id:       'library',
			multiple: false,
			toolbar:  'select',
			sidebar:  'settings',
			content:  'browse',//'upload',
			router:   'browse',
			menu:     'default',
			title:    "",
			edge:     140,
			gutter:   8
		},

		initialize: function () {
			var selection = this.get( 'selection' ),
				props;

			// If a library isn't provided, query all videos.
			if ( !this.get( 'library' ) ) {
				this.set( 'library', ekko.media.query() );
			}

			// If a selection instance isn't provided, create one.
			if ( !(selection instanceof ekko.media.model.Selection) ) {
				props = selection;

				if ( !props ) {
					props = this.get( 'library' ).props.toJSON();
					props = _.omit( props, 'orderby', 'query' );
				}

				this.set( 'selection', new ekko.media.model.Selection( null, {
					multiple: false,
					props:    props
				} ) );
			}
			this.resetDisplays();
		},

		activate: function () {
			this.syncSelection();
			ekko.ECVUploader.queue.on( 'add', this.uploading, this );
			this.get( 'selection' ).on( 'add remove reset', this.refreshContent, this );
		},

		deactivate: function () {
			this.recordSelection();
			this.get( 'selection' ).off( null, null, this );
			ekko.ECVUploader.queue.off( null, null, this );
		}
	} );

	/**
	 * @class ekko.media.controller.OEmbed
	 * @namespace ekko.media.controller
	 * @augments wp.media.controller.State
	 */
	ekko.media.controller.OEmbed = wp.media.controller.State.extend( /** @lends ekko.media.controller.OEmbed */ {
		defaults: {
			id:       'oembed',
			url:      '',
			menu:     'default',
			content:  'oembed',
			toolbar:  'main-oembed',
			type:     'none',
			pattern:  /https?:\/\/.*/i,
			title:    'oEmbed URL',
			priority: 120
		},

		sensitivity: 200,

		initialize: function () {
			this.debouncedScan = _.debounce( _.bind( this.scan, this ), this.sensitivity );
			this.props = new Backbone.Model( { url: '' } );
			this.props.on( 'change:url', this.debouncedScan, this );
			this.props.on( 'change:url', this.refresh, this );
			this.on( 'scan', this.scanImage, this );
		},

		scan: function () {
			var scanners,
				embed = this,
				attributes = {
					type:     'none',
					scanners: []
				},
				url = this.props.get( 'url' );

			if ( url && url.match( this.get( 'pattern' ) ) ) {
				this.trigger( 'scan', attributes );
			}

			if ( attributes.scanners.length ) {
				scanners = attributes.scanners = $.when.apply( $, attributes.scanners );
				scanners.always( function () {
					if ( embed.get( 'scanners' ) === scanners ) {
						embed.set( 'loading', false );
					}
				} );
			} else {
				attributes.scanners = null;
			}

			attributes.loading = !!attributes.scanners;
			this.set( attributes );
		},

		scanImage: function ( attributes ) {
			var frame = this.frame,
				state = this,
				url = this.props.get( 'url' );

			attributes.scanners.push( wp.media.ajax( {
				data: {
					action: 'ecv-oembed-video',
					url:    this.props.get( 'url' )
				}
			} ).done( function ( data ) {
					if ( state !== frame.state() || url !== state.props.get( 'url' ) ) {
						return;
					}
					state.set( {
						type: data.type
					} );
					state.props.set( data );
				} )
			);
		},

		refresh: function () {
			this.frame.toolbar.get().refresh();
		},

		reset: function () {
			this.props.clear().set( { url: '' } );

			if ( this.active ) {
				this.refresh();
			}
		}

	} );

	/**
	 * ========================================================================
	 * VIEWS
	 * ========================================================================
	 */

	/**
	 * @class ekko.media.view.ImagesMediaFrame
	 * @namespace ekko.media.view
	 * @augments wp.media.view.MediaFrame.Select
	 */
	ekko.media.view.ImagesMediaFrame = wp.media.view.MediaFrame.Select.extend( /** @lends ekko.media.view.ImagesMediaFrame */ {
		initialize: function () {
			_.defaults( this.options, {
				multiple: false,
				editing:  false,
				state:    'insert'
			} );

			wp.media.view.MediaFrame.Select.prototype.initialize.apply( this, arguments );
		},

		createStates: function () {
			var options = this.options;
			this.states.add( [
				new wp.media.controller.Library( {
					id:                  'insert',
					title:               "WordPress Media",
					priority:            20,
					toolbar:             'main-insert',
					filterable:          false,
					library:             wp.media.query( _.defaults( {
						type: 'image'
					}, options.library ) ),
					multiple:            false,
					editable:            false,//true
					allowLocalEdits:     false,//true
					displaySettings:     false,
					displayUserSettings: false
				} )
			] );
		},

		bindHandlers: function () {
			wp.media.view.MediaFrame.Select.prototype.bindHandlers.apply( this, arguments );
			this.on( 'toolbar:create:main-insert', this.createToolbar, this );
			this.on( 'toolbar:render:main-insert', this.mainInsertToolbar, this );
		},

		mainInsertToolbar: function ( view ) {
			var controller = this;

			view.set( 'insert', {
				style:    'primary',
				priority: 80,
				text:     l10n.addMedia,
				requires: { selection: true },

				click: function () {
					var state = controller.state(),
						selection = state.get( 'selection' );

					controller.close();
					state.trigger( 'insert', selection ).reset();
				}
			} );
		}

	} );

	/**
	 * @class ekko.media.view.VideosMediaFrame
	 * @namespace ekko.media.view
	 * @augments wp.media.view.MediaFrame.Select
	 */
	ekko.media.view.VideosMediaFrame = wp.media.view.MediaFrame.Select.extend( /** @lends ekko.media.view.VideosMediaFrame */ {
		className: 'media-frame ecv-media-frame',

		initialize: function () {
			_.defaults( this.options, {
				// Disable WordPress Uploader for this frame, we build our own.
				uploader:  false,
				selection: []
			} );

			wp.media.view.MediaFrame.Select.prototype.initialize.apply( this, arguments );
			this.createIframeStates();

			// Initialize window-wide ECV uploader.
			this.uploader = new ekko.media.view.ECVUploaderWindow( {
				controller: this,
				uploader:   {
					dropzone:  this.modal ? this.modal.$el : this.$el,
					container: this.$el
				}
			} );
			this.views.set( '.media-frame-uploader', this.uploader );
		},

		createSelection: function () {
			var controller = this,
				selection = this.options.selection;

			if ( !(selection instanceof ekko.media.model.Selection) ) {
				this.options.selection = new ekko.media.model.Selection( selection, {
					multiple: false
				} );
			}

			this._selection = {
				attachments: new ekko.media.model.Videos(),
				difference:  []
			};
		},

		createStates: function () {
			var options = this.options;
			this.states.add( [
				new ekko.media.controller.ECVLibrary( {
					id:       'insert',
					title:    'Ekko Cloud Videos',
					priority: 20,
					toolbar:  'video-insert',
					library:  ekko.media.query()
				} ),

				new ekko.media.controller.OEmbed( {
					id:      'oembed-youtube',
					title:   l10n.youTubeTitle,
					pattern: /https?:\/\/((www\.)?youtube.com\/watch|youtu.be\/).*/i
				} ),

				new ekko.media.controller.OEmbed( {
					id:      'oembed-vimeo',
					title:   l10n.vimeoTitle,
					pattern: /https?:\/\/(www\.)?vimeo\.com\/.*/i
				} )
			] );
		},

		bindHandlers: function () {
			wp.media.view.MediaFrame.Select.prototype.bindHandlers.apply( this, arguments );
			this.on( 'menu:render:default', this.mainMenu, this );
			this.on( 'toolbar:create:video-insert', this.createToolbar, this );
			this.on( 'toolbar:create:main-oembed', this.createOEmbedToolbar, this );
			this.on( 'toolbar:render:video-insert', this.videoInsertToolbar, this );
			this.on( 'content:render:oembed', this.oembedContent, this );
		},

		mainMenu: function ( view ) {
			view.set( {
				'library-separator':  new wp.media.View( {
					className: 'separator',
					priority:  100
				} ),
				'library-separator2': new wp.media.View( {
					className: 'separator',
					priority:  250
				} ),
				'uploader-status':    new ekko.media.view.ECVUploaderStatus( {
					className:  'upload-inline-status',
					controller: this.controller,
					priority:   300
				} )
			} );
		},

		browseRouter:  function ( view ) {
			view.set( {
				upload: {
					text:     "Upload Videos",
					priority: 20
				},
				browse: {
					text:     "Browse Videos",
					priority: 40
				}
			} );
		},

		//Content
		browseContent: function ( content ) {
			var state = this.state();

			this.$el.removeClass( 'hide-toolbar' );

			// Browse our library of videos.
			content.view = new ekko.media.view.VideosBrowser( {
				controller: this,
				collection: state.get( 'library' ),
				selection:  state.get( 'selection' ),
				model:      state,
				display:    state.get( 'displaySettings' ),
				dragInfo:   state.get( 'dragInfo' ),

				AttachmentView: state.get( 'AttachmentView' )
			} );
		},

		oembedContent: function () {
			var view = new ekko.media.view.OEmbed( {
				controller: this,
				model:      this.state()
			} ).render();

			this.content.set( view );
			view.url.focus();
		},

		uploadContent: function () {
			this.$el.removeClass( 'hide-toolbar' );
			this.content.set( new ekko.media.view.ECVUploaderInline( {
				controller: this,
				status:     false
			} ) );
		},

		videoInsertToolbar: function ( view ) {
			var controller = this;

			view.set( 'insert', {
				style:    'primary',
				priority: 80,
				text:     "Add Video",
				requires: { selection: true },

				click: function () {
					var state = controller.state(),
						selection = state.get( 'selection' );

					controller.close();
					state.trigger( 'insert', selection ).reset();
				}
			} );
		},

		createOEmbedToolbar: function ( toolbar ) {
			toolbar.view = new ekko.media.view.OEmbedToolbar( {
				controller: this,
				text:       "Blah Blah"
			} );
		}

	} );

	/**
	 * @class ekko.media.view.OEmbed
	 * @namespace ekko.media.view
	 * @augments wp.media.view.Embed
	 */
	ekko.media.view.OEmbed = wp.media.view.Embed.extend( /** @lends ekko.media.view.OEmbed */ {
		refresh: function () {
			var type = this.model.get( 'type' ),
				constructor;

			if ( 'none' === type ) {
				constructor = ekko.media.view.OEmbedNone;
			} else if ( 'video' === type ) {
				constructor = ekko.media.view.OEmbedVideo;
			}
			else {
				return;
			}

			this.settings( new constructor( {
				controller: this.controller,
				model:      this.model.props,
				priority:   40
			} ) );
		}
	} );

	/**
	 * @class ekko.media.view.OEmbedNone
	 * @namespace ekko.media.view
	 * @augments wp.media.view.Settings
	 */
	ekko.media.view.OEmbedNone = wp.media.view.Settings.extend( /** @lends ekko.media.view.OEmbedNone */ {
		className: 'oembed-none'
	} );

	/**
	 * @class ekko.media.view.OEmbedVideo
	 * @namespace ekko.media.view
	 * @augments wp.media.view.Settings
	 */
	ekko.media.view.OEmbedVideo = wp.media.view.Settings.extend( /** @lends ekko.media.view.OEmbedVideo */ {
		className: 'oembed-video',
		template:  wp.media.template( 'oembed-video' ),

		initialize: function () {
			wp.media.view.Settings.prototype.initialize.apply( this, arguments );
			this.model.on( 'change:html', this.updateHtml, this );
		},

		updateHtml: function () {
			this.$( '.video' ).html( this.model.get( 'html' ) );
		}
	} );

	/**
	 * @class ekko.media.view.OEmbedToolbar
	 * @namespace ekko.media.view
	 * @augments wp.media.view.Toolbar.Select
	 */
	ekko.media.view.OEmbedToolbar = wp.media.view.Toolbar.Select.extend( /** @lends ekko.media.view.OEmbedToolbar */ {
		initialize: function () {
			_.defaults( this.options, {
				text:     '',
				requires: false
			} );

			wp.media.view.Toolbar.Select.prototype.initialize.apply( this, arguments );
		},

		refresh: function () {
			var state = this.controller.state(),
				url = state.props.get( 'url' ),
				pattern = state.get( 'pattern' );

			this.get( 'select' ).model.set( 'disabled', !url || !url.match( pattern ) );

			wp.media.view.Toolbar.Select.prototype.refresh.apply( this, arguments );
		}
	} );

	/**
	 * @class ekko.media.view.ECVUploaderWindow
	 * @namespace ekko.media.view
	 * @augments wp.media.view.UploaderWindow
	 */
	ekko.media.view.ECVUploaderWindow = wp.media.view.UploaderWindow.extend( /** @lends ekko.media.view.ECVUploaderWindow */ {
		template: wp.media.template( 'ecv-uploader-window' ),
		ready:    function () {
			var dropzone;

			// If the uploader already exists, bail.
			if ( this.uploader ) {
				return;
			}

			this.uploader = new ekko.ECVUploader( this.options.uploader );

			dropzone = this.uploader.dropzone;
			dropzone.on( 'dropzone:enter', _.bind( this.show, this ) );
			dropzone.on( 'dropzone:leave', _.bind( this.hide, this ) );
		}
	} );

	/**
	 * @class ekko.media.view.ECVUploaderInline
	 * @namespace ekko.media.view
	 * @augments wp.media.view.UploaderInline
	 */
	ekko.media.view.ECVUploaderInline = wp.media.view.UploaderInline.extend( /** @lends ekko.media.view.ECVUploaderInline */ {
		template: wp.media.template( 'ecv-uploader-inline' )
	} );

	/**
	 * @class ekko.media.view.ECVUploaderStatus
	 * @namespace ekko.media.view
	 * @augments wp.media.view.UploaderStatus
	 */
	ekko.media.view.ECVUploaderStatus = wp.media.view.UploaderStatus.extend( /** @lends ekko.media.view.ECVUploaderStatus */ {
		initialize: function () {
			this.queue = ekko.ECVUploader.queue;
			this.queue.on( 'add remove reset', this.visibility, this );
			this.queue.on( 'add remove reset change:percent', this.progress, this );
			this.queue.on( 'add remove reset change:uploading', this.info, this );

			this.errors = ekko.ECVUploader.errors;
			this.errors.reset();
			this.errors.on( 'add remove reset', this.visibility, this );
			this.errors.on( 'add', this.error, this );
		},

		dispose: function () {
			this.queue.off( null, null, this );
			wp.media.View.prototype.dispose.apply( this, arguments );
			return this;
		},

		dismiss: function ( event ) {
			var errors = this.views.get( '.upload-errors' );

			event.preventDefault();

			if ( errors ) {
				_.invoke( errors, 'remove' );
			}
			this.errors.reset();
		}

	} );

	/**
	 * @class ekko.media.view.VideosBrowser
	 * @namespace ekko.media.view
	 * @augments wp.media.view.AttachmentsBrowser
	 */
	ekko.media.view.VideosBrowser = wp.media.view.AttachmentsBrowser.extend( /** @lends ekko.media.view.VideosBrowser */ {
		className:  'attachments-browser videos-browser',
		initialize: function () {
			_.defaults( this.options, {
				filters:  false,
				search:   false,
				display:  false,
				sortable: false,

				AttachmentView: ekko.media.view.Video
			} );

			this.updateContent();

			this.collection.on( 'add remove reset', this.updateContent, this );
		},

		createUploader: function () {
			this.removeContent();

			this.uploader = new ekko.media.view.ECVUploaderInline( {
				controller: this.controller,
				status:     false,
				message:    "No videos Found"
			} );

			this.views.add( this.uploader );
		}

	} );

	/**
	 * @class ekko.media.view.Video
	 * @namespace ekko.media.view
	 * @augments wp.media.view.Attachment
	 */
	ekko.media.view.Video = wp.media.view.Attachment.extend( /** @lends ekko.media.view.Video */ {
		template:   wp.media.template( 'video' ),
		buttons:    {
			check: true
		},
		initialize: function () {
			var selection = this.options.selection;

			this.model.on( 'change:sizes change:uploading change:state', this.render, this );
			this.model.on( 'change:percent', this.progress, this );

			// Update the selection.
			this.model.on( 'add', this.select, this );
			this.model.on( 'remove', this.deselect, this );
			if ( selection ) {
				selection.on( 'reset', this.updateSelect, this );
			}

			// Update the model's details view.
			this.model.on( 'selection:single selection:unsingle', this.details, this );
			this.details( this.model, this.controller.state().get( 'selection' ) );
		}
	} );

}( jQuery ));
