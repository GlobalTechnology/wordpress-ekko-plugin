/**
 * @namespace ekko
 */
window.ekko = window.ekko || {};

(function ( $ ) {
	var Video, Videos, Query, l10n, workflows = {}, editors = {};
	l10n = ekko.l10n = typeof _ekkoECVL10n === 'undefined' ? {} : _ekkoECVL10n;

	/**
	 * Creates or returns an existing frame
	 *
	 * @namespace ekko
	 * @param attributes
	 * @returns {ImagesMediaFrame|VideosMediaFrame}
	 */
	ekko.mediaFrame = function ( attributes ) {
		var frame;

		if ( !wp.media.view.MediaFrame ) {
			return;
		}

		attributes = _.defaults( attributes || {}, {
			frame: 'image'
		} );

		if ( 'image' === attributes.frame && ekko.view.ImagesMediaFrame ) {
			frame = new ekko.view.ImagesMediaFrame( attributes );
		}
		else if ( 'video' === attributes.frame && ekko.view.VideosMediaFrame ) {
			frame = new ekko.view.VideosMediaFrame( attributes );
		}

		delete attributes.frame;

		return frame;
	};

	_.extend( ekko, /** @lends ekko.media */ { model: {}, view: {}, controller: {}, frames: {} } );

	/**
	 * ========================================================================
	 * MODELS
	 * ========================================================================
	 */

	/**
	 * Get an ECV_Video by id
	 * @namespace ekko.media
	 * @param {int} id
	 * @returns {ekko.model.Video}
	 */
	ekko.video = function ( id ) {
		return Video.get( id );
	};

	/**
	 * Video Model
	 * @class ekko.model.Video
	 * @namespace ekko.model
	 * @augments Backbone.Model
	 */
	Video = ekko.model.Video = Backbone.Model.extend( /** @lends ekko.model.Video */ {
		initialize:    function ( attributes, options ) {
			this.on( 'change:state', this._stateChanged, this );
		},
		_stateChanged: function () {
			var self = this,
				timer = this.get( 'state-timer' );
			//Clear the timer if set
			if ( timer ) {
				clearInterval( timer );
				this.unset( 'state-timer', { silent: true } );
			}
			//Set timer if state is pending
			if ( 'PENDING' == this.get( 'state' ) ) {
				this.set( 'state-timer', setInterval( function () {
					self.fetch();
				}, 30 * 1000 ), { silent: true } );
			}
		},
		sync:          function ( method, model, options ) {
			if ( 'read' == method ) {
				options = options || {};
				options.context = this;
				options.data = _.extend( options.data || {}, {
					id:     this.get( 'id' ),
					action: 'ecv-get-video'
				} );
				return wp.media.ajax( options );
			} else {
				return Backbone.Model.prototype.sync.apply( this, arguments );
			}
		}
	}, /** @lends ekko.model.Video.prototype */ {
		create: function ( attrs ) {
			return Videos.all.push( attrs );
		},

		get: _.memoize( function ( id, video ) {
			return Videos.all.push( video || { id: id } );
		} )
	} );

	/**
	 * @class ekko.model.Videos
	 * @namespace ekko.model
	 * @augments wp.media.model.Attachments
	 */
	Videos = ekko.model.Videos = wp.media.model.Attachments.extend( /** @lends ekko.model.Videos */ {
		model: Video,

		validator: function ( attachment ) {
			return true;
		},

		_requery: function () {
			if ( this.props.get( 'query' ) ) {
				this.mirror( Query.get( this.props.toJSON() ) );
			}
		},

		mirror: function ( collection ) {
			if ( this.mirroring && this.mirroring === collection ) {
				return this;
			}

			this.unmirror();
			this.mirroring = collection;

			// Clear the collection silently. A `reset` event will be fired
			// when `observe()` calls `validateAll()`.
			this.reset( [], { silent: true } );
			this.observe( collection );

			return this;
		},

		unmirror: function () {
			if ( !this.mirroring ) {
				return;
			}

			this.unobserve( this.mirroring );
			delete this.mirroring;
		},

		more: function ( options ) {
			var deferred = $.Deferred(),
				mirroring = this.mirroring,
				attachments = this;

			if ( !mirroring || !mirroring.more ) {
				return deferred.resolveWith( this ).promise();
			}

			// If we're mirroring another collection, forward `more` to
			// the mirrored collection. Account for a race condition by
			// checking if we're still mirroring that collection when
			// the request resolves.
			mirroring.more( options ).done( function () {
				if ( this === attachments.mirroring ) {
					deferred.resolveWith( this );
				}
			} );

			return deferred.promise();
		},

		hasMore: function () {
			return this.mirroring ? this.mirroring.hasMore() : false;
		},

		parse: function ( resp, xhr ) {
			if ( !_.isArray( resp ) ) {
				resp = [resp];
			}

			return _.map( resp, function ( attrs ) {
				var id, video, newAttributes;

				if ( attrs instanceof Backbone.Model ) {
					id = attrs.get( 'id' );
					attrs = attrs.attributes;
				} else {
					id = attrs.id;
				}

				video = Video.get( id );
				newAttributes = video.parse( attrs, xhr );

				if ( !_.isEqual( video.attributes, newAttributes ) ) {
					video.set( newAttributes );
				}

				return video;
			} );
		}

	} );

	/**
	 * Global collection of Videos
	 * @namespace ekko.model.Videos
	 * @type {ekko.model.Videos}
	 */
	Videos.all = new Videos();

	/**
	 * Create a new Videos Query
	 * @namespace ekko.media
	 * @param props
	 * @returns {ekko.model.Videos}
	 */
	ekko.query = function ( props ) {
		return new Videos( null, {
			props: _.extend( props || {}, { query: true } )
		} );
	};

	/**
	 * @class ekko.model.Query
	 * @namespace ekko.model
	 * @augments ekko.model.Videos
	 */
	Query = ekko.model.Query = Videos.extend( /** @lends ekko.model.Query */ {
		initialize: function ( models, options ) {
			var allowed;

			options = options || {};
			Videos.prototype.initialize.apply( this, arguments );

			this.args = options.args;
			this._hasMore = true;
			this.created = new Date();

			this.filters.order = function ( attachment ) {
				return true;
			};

			// Observe the central `wp.Uploader.queue` collection to watch for
			// new matches for the query.
			//
			// Only observe when a limited number of query args are set. There
			// are no filters for other properties, so observing will result in
			// false positives in those queries.
			allowed = [ 's', 'order', 'orderby', 'posts_per_page', 'post_mime_type', 'post_parent' ];
			if ( ekko.ECVUploader && _( this.args ).chain().keys().difference( allowed ).isEmpty().value() ) {
				this.observe( ekko.ECVUploader.queue );
			}
		},

		hasMore: function () {
			return this._hasMore;
		},

		more: function ( options ) {
			var query = this;

			if ( this._more && 'pending' === this._more.state() ) {
				return this._more;
			}

			if ( !this.hasMore() ) {
				return $.Deferred().resolveWith( this ).promise();
			}

			options = options || {};
			options.remove = false;

			return this._more = this.fetch( options ).done( function ( resp ) {
				if ( _.isEmpty( resp ) || -1 === this.args.posts_per_page || resp.length < this.args.posts_per_page ) {
					query._hasMore = false;
				}
			} );
		},

		sync: function ( method, collection, options ) {
			var args, fallback;

			// Overload the read method so Videos.fetch() functions correctly.
			if ( 'read' === method ) {
				options = options || {};
				options.context = this;
				options.data = _.extend( options.data || {}, {
					action: 'ecv-query-videos'
				} );

				// Clone the args so manipulation is non-destructive.
				args = _.clone( this.args );

				// Determine which page to query.
				if ( -1 !== args.posts_per_page ) {
					args.paged = Math.floor( this.length / args.posts_per_page ) + 1;
				}

				options.data.query = args;
				return wp.media.ajax( options );

				// Otherwise, fall back to Backbone.sync()
			} else {
				fallback = Videos.prototype.sync ? Videos.prototype : Backbone;
				return fallback.sync.apply( this, arguments );
			}
		}
	}, /** @lends ekko.model.Query.prototype */ {
		defaultProps: {
			orderby: 'date',
			order:   'DESC'
		},

		defaultArgs: {
			posts_per_page: 40
		},

		orderby: {
			allowed:  [ 'name', 'author', 'date', 'title', 'modified', 'uploadedTo', 'id', 'post__in', 'menuOrder' ],
			valuemap: {
				'id':         'ID',
				'uploadedTo': 'parent',
				'menuOrder':  'menu_order ID'
			}
		},

		propmap: {
			'search':     's',
			'type':       'post_mime_type',
			'perPage':    'posts_per_page',
			'menuOrder':  'menu_order',
			'uploadedTo': 'post_parent'
		},

		// Caches query objects so queries can be easily reused.
		get:     (function () {
			var queries = [];

			return function ( props, options ) {
				var args = {},
					orderby = Query.orderby,
					defaults = Query.defaultProps,
					query;

				// Remove the `query` property. This isn't linked to a query,
				// this *is* the query.
				delete props.query;

				// Fill default args.
				_.defaults( props, defaults );

				// Normalize the order.
				props.order = props.order.toUpperCase();
				if ( 'DESC' !== props.order && 'ASC' !== props.order ) {
					props.order = defaults.order.toUpperCase();
				}

				// Ensure we have a valid orderby value.
				if ( !_.contains( orderby.allowed, props.orderby ) ) {
					props.orderby = defaults.orderby;
				}

				// Generate the query `args` object.
				// Correct any differing property names.
				_.each( props, function ( value, prop ) {
					if ( _.isNull( value ) ) {
						return;
					}

					args[ Query.propmap[ prop ] || prop ] = value;
				} );

				// Fill any other default query args.
				_.defaults( args, Query.defaultArgs );

				// `props.orderby` does not always map directly to `args.orderby`.
				// Substitute exceptions specified in orderby.keymap.
				args.orderby = orderby.valuemap[ props.orderby ] || props.orderby;

				// Search the query cache for matches.
				query = _.find( queries, function ( query ) {
					return _.isEqual( query.args, args );
				} );

				// Otherwise, create a new query and add it to the cache.
				if ( !query ) {
					query = new Query( [], _.extend( options || {}, {
						props: props,
						args:  args
					} ) );
					queries.push( query );
				}

				return query;
			};
		}())
	} );

	/**
	 * @class ekko.model.Selection
	 * @namespace ekko.model
	 * @augments ekko.model.Videos
	 */
	ekko.model.Selection = Videos.extend( /** @lends ekko.model.Selection */ {
		initialize: function ( models, options ) {
			Videos.prototype.initialize.apply( this, arguments );
			this.multiple = false;

			// Refresh the `single` model whenever the selection changes.
			// Binds `single` instead of using the context argument to ensure
			// it receives no parameters.
			this.on( 'add remove reset', _.bind( this.single, this, false ) );
		},

		add: function ( models, options ) {
			if ( !this.multiple ) {
				this.remove( this.models );
			}

			return Videos.prototype.add.call( this, models, options );
		},

		single: function ( model ) {
			var previous = this._single;

			// If a `model` is provided, use it as the single model.
			if ( model ) {
				this._single = model;
			}

			// If the single model isn't in the selection, remove it.
			if ( this._single && !this.get( this._single.cid ) ) {
				delete this._single;
			}

			this._single = this._single || this.last();

			// If single has changed, fire an event.
			if ( this._single !== previous ) {
				if ( previous ) {
					previous.trigger( 'selection:unsingle', previous, this );

					// If the model was already removed, trigger the collection
					// event manually.
					if ( !this.get( previous.cid ) ) {
						this.trigger( 'selection:unsingle', previous, this );
					}
				}
				if ( this._single ) {
					this._single.trigger( 'selection:single', this._single, this );
				}
			}

			// Return the single model, or the last model as a fallback.
			return this._single;
		}
	} );

	/**
	 * ========================================================================
	 * CONTROLLERS
	 * ========================================================================
	 */

	/**
	 * Ekko Cloud Video Library
	 *
	 * @namespace ekko.controller
	 * @class ekko.controller.ECVLibrary
	 * @augments wp.media.controller.Library
	 */
	ekko.controller.ECVLibrary = wp.media.controller.Library.extend( /** @lends ekko.controller.ECVLibrary */ {
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
				this.set( 'library', ekko.query() );
			}

			// If a selection instance isn't provided, create one.
			if ( !(selection instanceof ekko.model.Selection) ) {
				props = selection;

				if ( !props ) {
					props = this.get( 'library' ).props.toJSON();
					props = _.omit( props, 'orderby', 'query' );
				}

				this.set( 'selection', new ekko.model.Selection( null, {
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
	 * @class ekko.controller.OEmbed
	 * @namespace ekko.controller
	 * @augments wp.media.controller.State
	 */
	ekko.controller.OEmbed = wp.media.controller.State.extend( /** @lends ekko.controller.OEmbed */ {
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
	 * @class ekko.view.ImagesMediaFrame
	 * @namespace ekko.view
	 * @augments wp.media.view.MediaFrame.Select
	 */
	ekko.view.ImagesMediaFrame = wp.media.view.MediaFrame.Select.extend( /** @lends ekko.view.ImagesMediaFrame */ {
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
	 * @class ekko.view.VideosMediaFrame
	 * @namespace ekko.view
	 * @augments wp.media.view.MediaFrame.Select
	 */
	ekko.view.VideosMediaFrame = wp.media.view.MediaFrame.Select.extend( /** @lends ekko.view.VideosMediaFrame */ {
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
			this.uploader = new ekko.view.ECVUploaderWindow( {
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

			if ( !(selection instanceof ekko.model.Selection) ) {
				this.options.selection = new ekko.model.Selection( selection, {
					multiple: false
				} );
			}

			this._selection = {
				attachments: new ekko.model.Videos(),
				difference:  []
			};
		},

		createStates: function () {
			this.states.add( [
				new ekko.controller.ECVLibrary( {
					id:       'insert',
					title:    'Ekko Cloud Videos',
					priority: 20,
					toolbar:  'video-insert',
					library:  ekko.query()
				} ),

				new ekko.controller.OEmbed( {
					id:      'oembed-youtube',
					title:   l10n.youTubeTitle,
					pattern: /https?:\/\/((www\.)?youtube.com\/watch|youtu.be\/).*/i
				} ),

				new ekko.controller.OEmbed( {
					id:      'oembed-vimeo',
					title:   l10n.vimeoTitle,
					pattern: /https?:\/\/(www\.)?vimeo\.com\/.*/i
				} )
			] );

			if ( jfm.controller.ArclightLibrary ) {
				this.states.add( [
					new jfm.controller.ArclightLibrary( {
						priority: 40
					} )
				] );
			}
		},

		bindHandlers: function () {
//			this.on( 'all', function ( eventName ) {
//				console.log( eventName );
//			}, this );
			wp.media.view.MediaFrame.Select.prototype.bindHandlers.apply( this, arguments );
			this.on( 'menu:render:default', this.mainMenu, this );

			this.on( 'toolbar:create:video-insert', this.createToolbar, this );
			this.on( 'toolbar:create:main-oembed', this.createOEmbedToolbar, this );
			this.on( 'toolbar:render:video-insert', this.videoInsertToolbar, this );

			this.on( 'content:render:oembed', this.oembedContent, this );

			this.on( 'content:create:arclight', this.arclightContent, this );
			this.on( 'toolbar:create:arclight', this.arclightToolbar, this );
		},

		mainMenu: function ( view ) {
			view.set( {
				'library-separator':   new wp.media.View( {
					className: 'separator',
					priority:  100
				} ),
				'library-separator-2': new wp.media.View( {
					className: 'separator',
					priority:  250
				} ),
				'uploader-status':     new ekko.view.ECVUploaderStatus( {
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
			content.view = new ekko.view.VideosBrowser( {
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
			var view = new ekko.view.OEmbed( {
				controller: this,
				model:      this.state()
			} ).render();

			this.content.set( view );
			view.url.focus();
		},

		uploadContent: function () {
			this.$el.removeClass( 'hide-toolbar' );
			this.content.set( new ekko.view.ECVUploaderInline( {
				controller: this,
				status:     false
			} ) );
		},

		arclightContent: function( content ) {
			var state = this.state();

			this.$el.removeClass( 'hide-toolbar' );

			content.view = new jfm.view.ArclightBrowser( {
				controller: this,
				collection: state.get( 'library' ),
				selection:  state.get( 'selection' ),
				model:      state
			} );
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
			toolbar.view = new ekko.view.OEmbedToolbar( {
				controller: this,
				text:       "Blah Blah"
			} );
		},

		arclightToolbar: function( toolbar ) {
			toolbar.view = new jfm.view.ArclightVideoToolbar( {
				controller: this
			} );
		}

	} );

	/**
	 * @class ekko.view.OEmbed
	 * @namespace ekko.view
	 * @augments wp.media.view.Embed
	 */
	ekko.view.OEmbed = wp.media.view.Embed.extend( /** @lends ekko.view.OEmbed */ {
		refresh: function () {
			var type = this.model.get( 'type' ),
				constructor;

			if ( 'none' === type ) {
				constructor = ekko.view.OEmbedNone;
			} else if ( 'video' === type ) {
				constructor = ekko.view.OEmbedVideo;
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
	 * @class ekko.view.OEmbedNone
	 * @namespace ekko.view
	 * @augments wp.media.view.Settings
	 */
	ekko.view.OEmbedNone = wp.media.view.Settings.extend( /** @lends ekko.view.OEmbedNone */ {
		className: 'oembed-none'
	} );

	/**
	 * @class ekko.view.OEmbedVideo
	 * @namespace ekko.view
	 * @augments wp.media.view.Settings
	 */
	ekko.view.OEmbedVideo = wp.media.view.Settings.extend( /** @lends ekko.view.OEmbedVideo */ {
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
	 * @class ekko.view.OEmbedToolbar
	 * @namespace ekko.view
	 * @augments wp.media.view.Toolbar.Select
	 */
	ekko.view.OEmbedToolbar = wp.media.view.Toolbar.Select.extend( /** @lends ekko.view.OEmbedToolbar */ {
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
	 * @class ekko.view.ECVUploaderWindow
	 * @namespace ekko.view
	 * @augments wp.media.view.UploaderWindow
	 */
	ekko.view.ECVUploaderWindow = wp.media.view.UploaderWindow.extend( /** @lends ekko.view.ECVUploaderWindow */ {
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
	 * @class ekko.view.ECVUploaderInline
	 * @namespace ekko.view
	 * @augments wp.media.view.UploaderInline
	 */
	ekko.view.ECVUploaderInline = wp.media.view.UploaderInline.extend( /** @lends ekko.view.ECVUploaderInline */ {
		template: wp.media.template( 'ecv-uploader-inline' )
	} );

	/**
	 * @class ekko.view.ECVUploaderStatus
	 * @namespace ekko.view
	 * @augments wp.media.view.UploaderStatus
	 */
	ekko.view.ECVUploaderStatus = wp.media.view.UploaderStatus.extend( /** @lends ekko.view.ECVUploaderStatus */ {
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
	 * @class ekko.view.VideosBrowser
	 * @namespace ekko.view
	 * @augments wp.media.view.AttachmentsBrowser
	 */
	ekko.view.VideosBrowser = wp.media.view.AttachmentsBrowser.extend( /** @lends ekko.view.VideosBrowser */ {
		className:  'attachments-browser videos-browser',
		initialize: function () {
			_.defaults( this.options, {
				filters:  false,
				search:   false,
				display:  false,
				sortable: false,

				AttachmentView: ekko.view.Video
			} );

			this.updateContent();

			this.collection.on( 'add remove reset', this.updateContent, this );
		},

		createUploader: function () {
			this.removeContent();

			this.uploader = new ekko.view.ECVUploaderInline( {
				controller: this.controller,
				status:     false,
				message:    "No videos Found"
			} );

			this.views.add( this.uploader );
		}

	} );

	/**
	 * @class ekko.view.Video
	 * @namespace ekko.view
	 * @augments wp.media.view.Attachment
	 */
	ekko.view.Video = wp.media.view.Attachment.extend( /** @lends ekko.view.Video */ {
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

	/**
	 * ========================================================================
	 * EDITORS
	 * ========================================================================
	 */

	ekko.editor = {

		add: function ( id, options ) {
			var editor = this.get( id );

			if ( editor ) {
				return editor;
			}

			editor = editors[ id ] = ekko.mediaFrame( _.defaults( options || {}, {
				state:    'insert',
				multiple: false
			} ) );

			editor.on( 'insert', function ( selection ) {
				var media = selection.single();
				this.trigger( 'add-media', {
					mediaType:  selection instanceof ekko.model.Selection ? 'ecv' : 'file',
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
	_.bindAll( ekko.editor, 'open' );

	/**
	 * @namespace ekko.media
	 */
	ekko.EkkoBannerImage = {
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

			ekko.EkkoBannerImage.set( selection ? selection.id : -1 );
		},

		init: function () {
			$( '#coursebannerdiv' ).on( 'click', 'a.btn', function ( event ) {
				event.preventDefault();
				// Stop propagation to prevent thickbox from activating.
				event.stopPropagation();
				ekko.EkkoBannerImage.frame().open();
			} );
		}
	};
	$( ekko.EkkoBannerImage.init );

	ekko.thumbnail = {
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
	_.bindAll( ekko.thumbnail, 'open' );

})( jQuery );
