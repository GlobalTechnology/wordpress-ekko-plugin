/**
 * @namespace ekko
 */

window.wp = window.wp || {};
window.ekko = window.ekko || {};

(function ( $ ) {
	var Video, Videos, Query;

	/**
	 * @namespace ekko
	 * @param attributes
	 * @returns {ImagesMediaFrame|VideosMediaFrame}
	 */
	ekko.media = function ( attributes ) {
		var frame;

		if ( !wp.media.view.MediaFrame ) {
			return;
		}

		attributes = _.defaults( attributes || {}, {
			frame: 'image'
		} );

		if ( 'image' === attributes.frame && ekko.media.view.ImagesMediaFrame ) {
			frame = new ekko.media.view.ImagesMediaFrame( attributes );
		}
		else if ( 'video' === attributes.frame && ekko.media.view.VideosMediaFrame ) {
			frame = new ekko.media.view.VideosMediaFrame( attributes );
		}

		delete attributes.frame;

		return frame;
	};

	_.extend( ekko.media, /** @lends ekko.media */ { model: {}, view: {}, controller: {}, frames: {} } );

	/**
	 * ========================================================================
	 * MODELS
	 * ========================================================================
	 */

	/**
	 * Get an ECV_Video by id
	 * @namespace ekko.media
	 * @param {int} id
	 * @returns {ekko.media.model.Video}
	 */
	ekko.media.video = function ( id ) {
		return Video.get( id );
	};

	/**
	 * Video Model
	 * @class ekko.media.model.Video
	 * @namespace ekko.media.model
	 * @augments Backbone.Model
	 */
	Video = ekko.media.model.Video = Backbone.Model.extend( /** @lends ekko.media.model.Video */ {
		sync: function ( method, model, options ) {
			console.log( 'ekko.media.model.Video::' + method );
			var deferred = $.Deferred();
			return deferred.resolveWith( model ).promise();
		}
	}, /** @lends ekko.media.model.Video.prototype */ {
		create: function ( attrs ) {
			return Videos.all.push( attrs );
		},

		get: _.memoize( function ( id, video ) {
			return Videos.all.push( video || { id: id } );
		} )
	} );

	/**
	 * @class ekko.media.model.Videos
	 * @namespace ekko.media.model
	 * @augments wp.media.model.Attachments
	 */
	Videos = ekko.media.model.Videos = wp.media.model.Attachments.extend( /** @lends ekko.media.model.Videos */ {
		model: Video,

		validator: function ( attachment ) {
			return true;
		},

		_requery: function () {
			if ( this.props.get( 'query' ) ) {
				this.mirror( Query.get( this.props.toJSON() ) );
			}
		}
	} );

	/**
	 * Global collection of Videos
	 * @namespace ekko.media.model.Videos
	 * @type {ekko.media.model.Videos}
	 */
	Videos.all = new Videos();

	/**
	 * Create a new Videos Query
	 * @namespace ekko.media
	 * @param props
	 * @returns {ekko.media.model.Videos}
	 */
	ekko.media.query = function ( props ) {
		return new Videos( null, {
			props: _.extend( props || {}, { query: true } )
		} );
	};

	/**
	 * @class ekko.media.model.Query
	 * @namespace ekko.media.model
	 * @augments ekko.media.model.Videos
	 */
	Query = ekko.media.model.Query = Videos.extend( /** @lends ekko.media.model.Query */ {
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

			// Overload the read method so Attachment.fetch() functions correctly.
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
	}, /** @lends ekko.media.model.Query.prototype */ {
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
	 * @class ekko.media.model.Selection
	 * @namespace ekko.media.model
	 * @augments ekko.media.model.Videos
	 */
	ekko.media.model.Selection = Videos.extend( /** @lends ekko.media.model.Selection */ {
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

}( jQuery ));
