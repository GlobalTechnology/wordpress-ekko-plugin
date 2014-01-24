window.jfm = window.jfm || {};

(function ( $ ) {
	_.extend( jfm, /** @lends jfm */ { model: {}, view: {}, controller: {}, util: {} } );

	/**
	 * ========================================================================
	 * UTILITY FUNCTIONS
	 * ========================================================================
	 */

	/**
	 * @namespace jfm.util
	 * @param milliseconds
	 * @returns {string}
	 */
	jfm.util.millisecondsToRuntime = function( milliseconds ) {
		var length = milliseconds,
			ms = length % 1000;
		length = ( length - ms ) / 1000;
		var seconds = length % 60;
		length = ( length - seconds ) / 60;
		var minutes = length % 60,
			hours = ( length - minutes ) / 60;
		return hours + ':' + ( minutes < 10 ? '0' : '' ) + minutes + ':' + ( seconds < 10 ? '0' : '' ) + seconds;
	}

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
		idAttribute: 'refId',
		parse:       function ( response, xhr ) {
			if ( response.thumbnailUrl === '' ) {
				response[ 'media_thumbnail' ] = _.chain( response.boxArtUrls )
					.pluck( 'url' )
					.filter( function ( boxArt ) {
						return _.contains( ArclightVideo._boxArtResolutions, boxArt.type )
					} )
					.sortBy( function ( boxArt ) {
						return _.indexOf( ArclightVideo._boxArtResolutions, boxArt.type );
					} )
					.last()
					.value().uri;
			}
			else {
				response['media_thumbnail'] = response.thumbnailUrl;
			}

			if ( response.length ) {
				response[ 'runtime' ] = jfm.util.millisecondsToRuntime( response.length * 1 );
			}

			response.shortDescription = $( '<div/>' ).html( response.shortDescription ).text();

			return response;
		}
	}, /** @lends jfm.model.ArclightVideo.prototype */ {
		_boxArtResolutions: [ //From high to low
			'Mobile cinematic low',
			'Small'
		],
		create:             function ( attrs ) {
			return ArclightVideos.all.push( attrs );
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
			if ( 'read' === method ) {
				options = options || {};
				options.context = this;
				var props = this.props.toJSON();
				options.data = _.extend(
					options.data || {},
					{ action: 'arclight-get-titles' },
					_.omit( props, 'query' )
				);
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
			props: _.extend( _.defaults( props || {}, { language: '529', category: 'featureFilm' } ), { query: true } )
		} );
	};

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

			this.navigating = false;
			this.previousProps = new Backbone.Collection();

			this.get( 'library' ).props.on( 'change:refId', this._refIdChanged, this );

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
				this.set( 'edge', 140 );
			}

			if ( !this.get( 'gutter' ) ) {
				this.set( 'gutter', 8 );
			}
		},

		reset: function () {
			this.get( 'selection' ).reset();
		},

		_refIdChanged: function ( model, value, options ) {
			if ( !this.navigating ) {
				var prevAttr = model.previousAttributes();
				this.previousProps.push( prevAttr );
			}
			this.navigating = false;
		},

		navigateToPrevious: function () {
			if ( this.previousProps.length > 0 ) {
				var prevAttr = this.previousProps.pop(),
					props = this.get( 'library' ).props;
				this.navigating = true;
				if ( prevAttr.has( 'refId' ) ) {
					props.set( 'refId', prevAttr.get( 'refId' ) );
				}
				else {
					props.unset( 'refId' );
				}
			}
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
			var languages = [
				{name: "English", languageId: "529"},
				{name: "Chinese (Mandarin)", languageId: "20615"},
				{name: "Chinese (Simplified)", languageId: "21754"},
				{name: "Spanish", languageId: "21046"},
				{name: "Japanese", languageId: "7083"},
				{name: "Portuguese", languageId: "584"},
				{name: "German", languageId: "1106"},
				{name: "Arabic", languageId: "525"},
				{name: "French", languageId: "496"},
				{name: "Russian", languageId: "3934"},
				{name: "Korean", languageId: "3804"}
			];
			var filters = {},
				priority = 100;

			_.each( languages, function ( language ) {
				filters[ language.languageId ] = {
					text:     language.name,
					priority: priority,
					props:    {
						language: language.languageId
					}
				};
				priority += 100;
			} );

			this.filters = filters;
		}
	} );

	/**
	 * @class jfm.view.CategoryFilter
	 * @namespace jfm.view
	 * @augments wp.media.view.AttachmentFilters
	 */
	jfm.view.CategoryFilter = wp.media.view.AttachmentFilters.extend( /** @lends jfm.view.CategoryFilter */ {
		createFilters: function () {
			var categories = [
				{"name": "anime", "description": "Anime"},
				{"name": "featureFilm", "description": "Feature Film"},
				{"name": "series", "description": "Series"},
				{"name": "shortFilm", "description": "Short Film"}
			];
			var filters = {},
				priority = 100;
			_.each( categories, function ( category ) {
				filters[ category.name ] = {
					text:     category.description,
					priority: priority,
					props:    {
						category: category.name
					}
				};
				priority += 100;
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
			this.createToolbar();
			this.updateContent();
			this.createSidebar();
			this.updateControls();

			this.collection.on( 'add remove reset', this.updateContent, this );
			this.collection.props.on( 'change:refId', this.updateControls, this );
		},

		dispose: function () {
			this.options.selection.off( null, null, this );
			this.collection.props.off( null, null, this );
			wp.media.View.prototype.dispose.apply( this, arguments );
			return this;
		},

		updateControls: function () {
			var previous = this.sidebar.get( 'previous' ),
				language = this.toolbar.get( 'language' ),
				category = this.toolbar.get( 'category' ),
				refId = ( this.collection.props.get( 'refId' ) ) ? true : false;

			if ( refId ) {
				previous.$el.show();
			}
			else {
				previous.$el.hide();
			}
			language.$el.prop( 'disabled', refId );
			category.$el.prop( 'disabled', refId );
		},

		createToolbar: function () {
			var self = this;
			this.toolbar = new wp.media.view.Toolbar( {
				controller: this.controller
			} );

			this.views.add( this.toolbar );

			this.toolbar.set( 'language', new jfm.view.LanguageFilter( {
				controller: this.controller,
				model:      this.collection.props,
				priority:   -80
			} ).render() );

			this.toolbar.set( 'category', new jfm.view.CategoryFilter( {
				controller: this.controller,
				model:      this.collection.props,
				priority:   -40
			} ).render() );
		},

		updateContent: function () {
			var view = this;
			this.options.selection.reset();

			if ( !this.videos ) {
				this.createVideos();
			}
		},

		createSidebar: function () {
			var options = this.options,
				selection = options.selection,
				sidebar = this.sidebar = new wp.media.view.Sidebar( {
					controller: this.controller
				} );

			this.views.add( sidebar );

			selection.on( 'selection:single', this.createSingle, this );
			selection.on( 'selection:unsingle', this.disposeSingle, this );

			sidebar.set( 'previous', new jfm.view.PreviousButton( {
				controller: this.controller,
				priority:   0
			} ) );

			if ( selection.single() ) {
				this.createSingle();
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
		},

		createSingle: function () {
			var self = this,
				sidebar = this.sidebar,
				collection = this.collection,
				single = this.options.selection.single();

			sidebar.set( 'details', new jfm.view.ArclightVideo.Details( {
				controller: this.controller,
				model:      single,
				priority:   80
			} ) );

			var browseButton = new wp.media.view.Button( {
				controller: this.controller,
				disabled:   !( single.get( 'groupContentCount' ) ),
				priority:   120,
				text:       ('series' === single.get( 'type' ) ) ? 'Browse Series' : 'Browse Clips',
				click:      function () {
					collection.props.set( 'refId', single.get( 'refId' ) );
				}
			} );
			sidebar.set( 'browse', browseButton );

			if ( !single.get( 'groupContentCount' ) ) {
				wp.media.ajax( {
					context: this,
					data:    {
						action: 'arclight-has-associated',
						refId:  single.get( 'refId' )
					}
				} ).done( function () {
					if ( single === self.options.selection.single() ) {
						browseButton.model.set( 'disabled', false );
					}
				} );
			}
		},

		disposeSingle: function () {
			var sidebar = this.sidebar;
			sidebar.unset( 'details' );
			sidebar.unset( 'browse' );
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
	 * @class jfm.view.ArclightVideo.Details
	 * @namespace jfm.view.ArclightVideo
	 * @augments jfm.view.ArclightVideo
	 */
	jfm.view.ArclightVideo.Details = jfm.view.ArclightVideo.extend( /** @lends jfm.view.ArclightVideo.Details */ {
		tagName:   'div',
		className: 'attachment-details',
		template:  wp.media.template( 'arclight-video-details' )
	} );

	/**
	 * @class jfm.view.ArclightVideoToolbar
	 * @namespace jfm.view
	 * @augments wp.media.view.Toolbar.Select
	 */
	jfm.view.ArclightVideoToolbar = wp.media.view.Toolbar.Select.extend( /** @lends jfm.view.ArclightVideoToolbar */ {
		initialize: function () {
			_.defaults( this.options, {
				text:     'Add Video',
				requires: false,
				event:    'arclight-insert'
			} );

			wp.media.view.Toolbar.Select.prototype.initialize.apply( this, arguments );
		},

		refresh: function () {
			var state = this.controller.state(),
				selection = state.get( 'selection' ),
				disabled = true;

			if ( selection && selection.length ) {
				disabled = selection.single().get( 'type' ) === 'series';
			}
			this.get( 'select' ).model.set( 'disabled', disabled );

			wp.media.view.Toolbar.Select.prototype.refresh.apply( this, arguments );
		}
	} );

	/**
	 * @class jfm.view.PreviousButton
	 * @namespace jfm.view
	 * @augments wp.media.View
	 */
	jfm.view.PreviousButton = wp.media.View.extend( /** @lends jfm.view.PreviousButton */ {
		tagName:   'div',
		className: 'arclight-previous',

		initialize: function () {
			this.views.add( new wp.media.view.Button( {
				controller: this.controller,
				text:       'Previous Videos',
				click:      function () {
					this.controller.state().navigateToPrevious();
				}
			} ) );
		}
	} );

	// Clean up. Prevents mobile browsers caching
	$( window ).on( 'unload', function () {
		window.jfm = null;
	} );

})( jQuery );
