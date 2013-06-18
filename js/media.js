window.ekko = window.ekko || {};

;(function($) {
	var media,
		l10n,
		workflows = {};

	//Create new EkkoFrame, used internally by ekko.media.editor
	media = ekko.media = function( attributes ) {
		var frame;

		if ( ! ekko.media.view.EkkoFrame )
			return;

		frame = new ekko.media.view.EkkoFrame( attributes );

		return frame;
	};

	_.extend( media, { model: {}, view: {}, controller: {}, frames: {} } );

	// Link any localized strings.
	l10n = ekko.l10n = typeof _EkkoAppL10N === 'undefined' ? {} : _EkkoAppL10N.l10n;

	// OEmbed Controller
	media.controller.oEmbed = wp.media.controller.State.extend({
		defaults: {
			id:       'oembed',
			url:      '',
			menu:     'default',
			content:  'embed',
			toolbar:  'main-embed',
			type:     'none',
			pattern:  /https?:\/\/.*/i,
			title:    'oEmbed URL',
			priority: 120
		},

		sensitivity: 200,

		initialize: function() {
			this.debouncedScan = _.debounce( _.bind( this.scan, this ), this.sensitivity );
			this.props = new Backbone.Model({ url: '' });
			this.props.on( 'change:url', this.debouncedScan, this );
			this.props.on( 'change:url', this.refresh, this );
			this.on( 'scan', this.scanImage, this );
		},

		scan: function() {
			var scanners,
				embed = this,
				attributes = {
					type: 'none',
					scanners: []
				},
				url = this.props.get('url');

			if ( url && url.match( this.get( 'pattern' ) ) )
				this.trigger( 'scan', attributes );

			if ( attributes.scanners.length ) {
				scanners = attributes.scanners = $.when.apply( $, attributes.scanners );
				scanners.always( function() {
					if ( embed.get('scanners') === scanners )
						embed.set( 'loading', false );
				});
			} else {
				attributes.scanners = null;
			}

			attributes.loading = !! attributes.scanners;
			this.set( attributes );
		},

		scanImage: function( attributes ) {
			var frame = this.frame,
				state = this,
				url = this.props.get('url');

			attributes.scanners.push( wp.media.post( 'ekko-oembed', {
					json: true,
					url: this.props.get('url')
				} ).done( function( data ) {

					if ( state !== frame.state() || url !== state.props.get('url') )
						return;

					state.set( {
						type: data.type
					} );

					state.props.set( data );
				} )
			);
		},

		refresh: function() {
			this.frame.toolbar.get().refresh();
		},

		reset: function() {
			this.props.clear().set({ url: '' });

			if ( this.active )
				this.refresh();
		}

	});

	// OEmbed View Content
	media.view.oEmbed = wp.media.view.Embed.extend({
		refresh: function() {
			var type = this.model.get('type'),
				constructor;

			if ( 'none' === type )
				constructor = media.view.oEmbedNone;
			if( 'video' === type )
				constructor = media.view.oEmbedVideo;
			else
				return;

			this.settings( new constructor({
				controller: this.controller,
				model:      this.model.props,
				priority:   40
			}) );
		}
	});

	// OEmbed View Content - Type = 'none'
	media.view.oEmbedNone = wp.media.view.Settings.extend({
		className: 'oembed-none'
	});

	// OEmbed View Content - Type = 'video'
	media.view.oEmbedVideo = wp.media.view.Settings.extend({
		className: 'oembed-video',
		template: wp.media.template( 'oembed-video' ),

		initialize: function() {
			wp.media.view.Settings.prototype.initialize.apply( this, arguments );
			this.model.on( 'change:html', this.updateHtml, this );
		},

		updateHtml: function() {
			this.$('.video').html( this.model.get( 'html' ) );
		}
	});

	// OEmbed Toolbar
	media.view.oEmbedToolbar = wp.media.view.Toolbar.Select.extend({
		initialize: function() {
			_.defaults( this.options, {
				text: 'Add It!!',
				requires: false
			});

			wp.media.view.Toolbar.Select.prototype.initialize.apply( this, arguments );
		},

		refresh: function() {
			var state = this.controller.state(),
				url = state.props.get( 'url' ),
				pattern = state.get( 'pattern' );

			this.get( 'select' ).model.set( 'disabled', ! url || ! url.match( pattern ) );

			wp.media.view.Toolbar.Select.prototype.refresh.apply( this, arguments );
		}
	});

	// EkkoFrame - The main media selector for Ekko
	media.view.EkkoFrame = wp.media.view.MediaFrame.Select.extend( {

		initialize: function() {
			_.defaults( this.options, {
				multiple:  false,
				editing:   false,
				state:    'insert'
			});

			wp.media.view.MediaFrame.Select.prototype.initialize.apply( this, arguments );
			this.createIframeStates();
		},

		createStates: function() {
			var options = this.options;

			// Add the default states.
			this.states.add([
				// Main states.
				new wp.media.controller.Library({
					id:         'insert',
					title:      l10n.addMediaTitle,
					priority:   20,
					toolbar:    'main-insert',
					filterable: 'all',
					library:    wp.media.query( options.library ),
					multiple:   options.multiple ? 'reset' : false,
					editable:   true,

					// If the user isn't allowed to edit fields,
					// can they still edit it locally?
					allowLocalEdits: true,

					// Show the attachment display settings.
					displaySettings: false,
					// Update user settings when users adjust the
					// attachment display settings.
					displayUserSettings: true
				}),

				// Embed states.
				new media.controller.oEmbed({
					id:      'oembed-youtube',
					title:   'YouTube',
					pattern: /https?:\/\/((www\.)?youtube.com\/watch|youtu.be\/).*/i
				}),

				new media.controller.oEmbed({
					id:      'oembed-vimeo',
					title:   'Vimeo',
					pattern: /https?:\/\/(www\.)?vimeo\.com\/.*/i
				})

			]);

			if ( wp.media.view.settings.post.featuredImageId ) {
				this.states.add( new wp.media.controller.FeaturedImage( {
					title: l10n.setBannerTitle
				} ) );
			}
		},

		bindHandlers: function() {
			wp.media.view.MediaFrame.Select.prototype.bindHandlers.apply( this, arguments );
			this.on( 'toolbar:create:main-insert', this.createToolbar, this );
			this.on( 'toolbar:create:featured-image', this.featuredImageToolbar, this );
			this.on( 'toolbar:create:main-embed', this.mainEmbedToolbar, this );
			this.on( 'menu:render:default', this.mainMenu, this );
			this.on( 'toolbar:render:main-insert', this.mainInsertToolbar, this );
			this.on( 'content:render:embed', this.embedContent, this );
		},

		// Menus
		mainMenu: function( view ) {
			view.set({
				'library-separator': new wp.media.View({
					className: 'separator',
					priority: 100
				})
			});
		},

		//Content
		embedContent: function() {
			var view = new media.view.oEmbed({
				controller: this,
				model:      this.state()
			}).render();

			this.content.set( view );
			view.url.focus();
		},

		// Toolbars
		mainInsertToolbar: function( view ) {
			var controller = this;

			view.set( 'insert', {
				style:    'primary',
				priority: 80,
				text:     l10n.addMedia,
				requires: { selection: true },

				click: function() {
					var state = controller.state(),
						selection = state.get('selection');

					controller.close();
					state.trigger( 'insert', selection ).reset();
				}
			});
		},

		featuredImageToolbar: function( toolbar ) {
			this.createSelectToolbar( toolbar, {
				text:  l10n.setBanner,
				state: this.options.state || 'upload'
			});
		},

		mainEmbedToolbar: function( toolbar ) {
			toolbar.view = new media.view.oEmbedToolbar({
				controller: this,
				text: l10n.addMedia
			});
		}

	} );

	/**
	 * Ekko Banner Image
	 */
	ekko.media.EkkoBannerImage = {
		get: function() {
			return wp.media.view.settings.post.featuredImageId;
		},

		set: function( id ) {
			var settings = wp.media.view.settings;

			settings.post.featuredImageId = id;

			wp.media.post( 'ekko-set-course-banner', {
				json:         true,
				post_id:      settings.post.id,
				banner_id: settings.post.featuredImageId,
				_wpnonce:     settings.post.nonce
			}).done( function( html ) {
				$( '.well', '#coursebannerdiv' ).html( html );
			});
		},

		frame: function() {
			if ( this._frame )
				return this._frame;

			this._frame = wp.media({
				state: 'featured-image',
				states: [ new wp.media.controller.FeaturedImage( {
					title: l10n.setBannerTitle
				} ) ]
			});

			this._frame.on( 'toolbar:create:featured-image', function( toolbar ) {
				this.createSelectToolbar( toolbar, {
					text: l10n.setBanner
				});
			}, this._frame );

			this._frame.state('featured-image').on( 'select', this.select );
			return this._frame;
		},

		select: function() {
			var settings = wp.media.view.settings,
				selection = this.get('selection').single();

			if ( ! settings.post.featuredImageId )
				return;

			ekko.media.EkkoBannerImage.set( selection ? selection.id : -1 );
		},

		init: function() {
			$('#coursebannerdiv').on( 'click', 'a.btn', function( event ) {
				event.preventDefault();
				// Stop propagation to prevent thickbox from activating.
				event.stopPropagation();
				ekko.media.EkkoBannerImage.frame().open();
			});
		}
	};
	$( ekko.media.EkkoBannerImage.init );

	/**
	 * Default Ekko Media workflow
	 */
	ekko.media.editor = {

		add: function( id, options ) {
			var workflow = this.get( id );

			if ( workflow )
				return workflow;

			workflow = workflows[ id ] = ekko.media( _.defaults( options || {}, {
				state:    'insert',
				multiple: false
			} ) );

			workflow.state( 'oembed-youtube' ).on( 'select', function() {
				var state = workflow.state(),
					data = state.props.toJSON();
				state.trigger( 'embed', data, 'youtube' );
			}, this );

			workflow.state( 'oembed-vimeo' ).on( 'select', function() {
				var state = workflow.state(),
					data = state.props.toJSON();
				state.trigger( 'embed', data, 'vimeo' );
			} );

			workflow.state( 'featured-image' ).on( 'select', ekko.media.EkkoBannerImage.select );
			workflow.setState( workflow.options.state );
			return workflow;
		},

		get: function( id ) {
			return workflows[ id ];
		},

		remove: function( id ) {
			delete workflows[ id ];
		},

		open: function( id, options ) {
			var workflow;

			workflow = this.get( id );

			// Initialize the editor's workflow if we haven't yet.
			if ( ! workflow )
				workflow = this.add( id, options );

			return workflow.open();
		}
	};
	_.bindAll( ekko.media.editor, 'open' );

	/**
	 * Thumbnail Media Chooser
	 */
	ekko.media.thumbnail = {
		add: function( id, options ) {
			var workflow = this.get( id );

			if( workflow )
				return workflow;

			workflow = workflows[ id ] = new wp.media.view.MediaFrame.Select({
				title: 'Select a Thumbnail Image',
				library: { type: 'image' },
				multiple: false
			});

			return workflow;
		},

		get: function( id ) {
			return workflows[ id ];
		},

		remove: function( id ) {
			delete workflows[ id ];
		},

		open: function( id, options ) {
			var workflow;

			workflow = this.get( id );

			if( ! workflow )
				workflow = this.add( id, options );

			return workflow.open();
		}
	};
	_.bindAll( ekko.media.thumbnail, 'open' );

} )(jQuery);