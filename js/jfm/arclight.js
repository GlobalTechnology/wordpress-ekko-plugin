window.jfm = window.jfm || {};

(function ( $ ) {
	_.extend( jfm, /** @lends jfm */ { model: {}, view: {}, controller: {} } );

	/**
	 * ========================================================================
	 * MODELS
	 * ========================================================================
	 */

	/**
	 * JFM Arclight Video Model
	 *
	 * @class jfm.model.ArclightVideo
	 * @namespace jfm.models
	 * @augments Backbone.Model
	 */
	var ArclightVideo = jfm.model.ArclightVideo = Backbone.Model.extend( /** @lends jfm.model.ArclightVideo */ {
		idAttribute: 'refId'
	}, /** @lends jfm.model.ArclightVideo.prototype */ {
		create: function ( attrs ) {
			return Videos.all.push( attrs );
		},

		get: _.memoize( function ( id, video ) {
			return ArclightVideos.all.push( video || { refId: id } );
		} )
	} );

	/**
	 * JFM Arclight Video Collection
	 *
	 * @class jfm.model.ArclightVideos
	 * @namespace jfm.models
	 * @augments Backbone.Collection
	 */
	var ArclightVideos = jfm.model.ArclightVideos = Backbone.Collection.extend( /** @lends jfm.model.ArclightVideos */ {
		model: ArclightVideo,

		initialize: function ( models, options ) {
			this.on( 'all', function ( eventName ) {
				console.log( 'ArclightVideos::' + eventName );
			}, this );
			options = options || {};

			this.props = new Backbone.Model();

			this.props.on( 'change', this._requery, this );

			// Set the `props` model and fill the default property values.
			this.props.set( _.defaults( options.props || {} ) );
		},

		hasMore: function () {
			return false;
		},

		more: function () {
			return $.Deferred().resolveWith( this ).promise();
		},

		_requery: function () {
			if ( this.props.get( 'query' ) ) {
				this.fetch( {reset: true} );
			}
		},

		parse: function ( resp, xhr ) {
			if ( !_.isArray( resp ) ) {
				resp = [resp];
			}

			return _.map( resp, function ( attrs ) {
				var id, video, newAttributes;

				if ( attrs instanceof Backbone.Model ) {
					id = attrs.get( 'refId' );
					attrs = attrs.attributes;
				} else {
					id = attrs.refId;
				}

				video = ArclightVideo.get( id );
				newAttributes = video.parse( attrs, xhr );

				if ( !_.isEqual( video.attributes, newAttributes ) ) {
					video.set( newAttributes );
				}

				return video;
			} );
		},

		sync: function ( method, model, options ) {
			console.log( 'ArclightVideos::' + method );
			if ( 'read' === method ) {
				options = options || {};
				options.context = this;
				options.data = _.extend( options.data || {}, {
					action:   'arclight-get-titles',
					language: this.props.get( 'language' )
				} );

				return wp.media.ajax( options );
			} else {
				return Backbone.sync.apply( this, arguments );
			}
		}

	} );

	ArclightVideos.all = new ArclightVideos();

	/**
	 * @returns ArclightVideos
	 */
	jfm.query = function ( props ) {
		return new ArclightVideos( null, {
			props: _.extend( _.defaults( props || {}, { language: '529' } ), { query: true } )
		} );
	};

	/**
	 * JFM Video Query
	 * @class jfm.model.ArclightQuery
	 * @namespace jfm.models
	 * @augments jfm.model.ArclightVideos
	 */
	var ArclightQuery = jfm.model.ArclightQuery = jfm.model.ArclightVideos.extend( /** @lends jfm.model.ArclightQuery */ {
		sync: function ( method, model, options ) {
			console.log( 'ArclightQuery::' + method );
			if ( 'read' === method ) {

			} else {
				fallback = ArclightVideos.prototype.sync ? ArclightVideos.prototype : Backbone;
				return fallback.sync.apply( this, arguments );
			}
		}
	} );

	/**
	 * JFM Video Single Selection
	 * @class jfm.model.ArclightSelection
	 * @namespace jfm.models
	 * @augments jfm.model.ArclightVideos
	 */
	var ArclightSelection = jfm.model.ArclightSelection = jfm.model.ArclightVideos.extend( /** @lends jfm.model.ArclightSelection */ {
		initialize: function ( models, options ) {
			ArclightVideos.prototype.initialize.apply( this, arguments );
			this.multiple = false;

			// Refresh the `single` model whenever the selection changes.
			// Binds `single` instead of using the context argument to ensure
			// it receives no parameters.
			this.on( 'add remove reset', _.bind( this.single, this, false ) );
		},

		// Override the selection's add method.
		// If the workflow does not support multiple
		// selected attachments, reset the selection.
		add:        function ( models, options ) {
			if ( !this.multiple ) {
				this.remove( this.models );
			}

			return ArclightVideos.prototype.add.call( this, models, options );
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
	 * Jesus Film Media Arclight Library
	 *
	 * @namespace jfm.controllers
	 * @class jfm.controller.ArclightLibrary
	 * @augments wp.media.controller.State
	 */
	jfm.controller.ArclightLibrary = wp.media.controller.State.extend( /** @lends jfm.controller.ArclightLibrary */ {
		defaults: {
			id:      'arclight',
			toolbar: 'arclight',
			sidebar: 'settings',
			content: 'arclight',
			menu:    'default',
			title:   'Jesus Film Media'
		},

		initialize: function () {
			var selection = this.get( 'selection' ),
				props;

			// If a library isn't provided, query all media items.
			if ( !this.get( 'library' ) ) {
				this.set( 'library', jfm.query() );
			}

			// If a selection instance isn't provided, create one.
			if ( !(selection instanceof jfm.model.ArclightSelection) ) {
				props = selection;

				if ( !props ) {
					props = this.get( 'library' ).props.toJSON();
					props = _.omit( props, 'query' );
				}

				// If the `selection` attribute is set to an object,
				// it will use those values as the selection instance's
				// `props` model. Otherwise, it will copy the library's
				// `props` model.
				this.set( 'selection', new jfm.model.ArclightSelection( null, {
					props: props
				} ) );
			}

			if ( !this.get( 'edge' ) ) {
				this.set( 'edge', 120 );
			}

			if ( !this.get( 'gutter' ) ) {
				this.set( 'gutter', 8 );
			}
		},

		reset: function () {
			this.get( 'selection' ).reset();
		}

	} );

	/**
	 * ========================================================================
	 * VIEWS
	 * ========================================================================
	 */

	/**
	 * @class jfm.view.LanguageFilter
	 * @namespace jfm.views
	 * @augments wp.media.view.AttachmentFilters
	 */
	jfm.view.LanguageFilter = wp.media.view.AttachmentFilters.extend( /** @lends jfm.view.LanguageFilter */ {
		createFilters: function () {
			var languages = {
				'529':   'English',
				'20615': 'Chinese (Mandarin)',
				'21754': 'Chinese (Simplified)',
				'21046': 'Spanish',
				'7083':  'Japanese',
				'584':   'Portuguese',
				'1106':  'German',
				'525':   'Arabic',
				'496':   'French',
				'3934':  'Russian',
				'3804':  'Korean'
			};
			var filters = {},
				priority = 0;

			_.each( languages, function ( text, key ) {
				filters[ key ] = {
					text:     text,
					priority: priority,
					props:    {
						language: key
					}
				};
				priority += 10;
			} );

			this.filters = filters;
		}
	} );

	/**
	 * @class jfm.view.ArclightBrowser
	 * @namespace jfm.views
	 * @augments wp.media.View
	 */
	jfm.view.ArclightBrowser = wp.media.View.extend( /** @lends jfm.view.ArclightBrowser */ {
		tagName:   'div',
		className: 'attachments-browser arclight-browser',

		initialize: function () {
			_.defaults( this.options, {
				display: false
			} );

			this.createToolbar();
			this.updateContent();
//			this.createSidebar();

			this.collection.on( 'add remove reset', this.updateContent, this );
		},

		dispose: function () {
//			this.options.selection.off( null, null, this );
			wp.media.View.prototype.dispose.apply( this, arguments );
			return this;
		},

		createToolbar: function () {
			var filters, FiltersConstructor;

			this.toolbar = new wp.media.view.Toolbar( {
				controller: this.controller
			} );

			this.views.add( this.toolbar );

			this.toolbar.set( 'language', new jfm.view.LanguageFilter( {
				controller: this.controller,
				model:      this.collection.props,
				priority:   -80
			} ).render() );
		},

		updateContent: function () {
			console.log( 'Update Content!' );
			var view = this;

			if ( !this.videos ) {
				this.createVideos();
			}
		},

		createVideos: function () {
			this.videos = new wp.media.view.Attachments( {
				controller:     this.controller,
				collection:     this.collection,
				selection:      this.options.selection,
				model:          this.model,
				sortable:       false,

				// The single `Attachment` view to be used in the `Attachments` view.
				AttachmentView: jfm.view.ArclightVideo
			} );

			this.views.add( this.videos );
		}

	} );

	/**
	 * @class jfm.view.ArclightVideo
	 * @namespace jfm.views
	 * @augments wp.media.view.Attachment
	 */
	jfm.view.ArclightVideo = wp.media.view.Attachment.extend( /** @lends jfm.view.ArclightVideo */ {
		template:   wp.media.template( 'arclight-video' ),
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
//			this.details( this.model, this.controller.state().get( 'selection' ) );
		}
	} );

	jfm.view.ArclightVideoToolbar = wp.media.view.Toolbar.Select.extend( {

	} );

	// Clean up. Prevents mobile browsers caching
	$( window ).on( 'unload', function () {
		window.jfm = null;
	} );

})( jQuery );
