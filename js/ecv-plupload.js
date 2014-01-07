window.ekko = window.ekko || {};
window.wp = window.wp || {};

(function ( exports, $ ) {
	var ECVUploader;

	/*
	 * An object that helps create a WordPress uploader using plupload.
	 *
	 * @param options - object - The options passed to the new plupload instance.
	 *    Accepts the following parameters:
	 *    - container - The id of uploader container.
	 *    - browser   - The id of button to trigger the file select.
	 *    - dropzone  - The id of file drop target.
	 *    - plupload  - An object of parameters to pass to the plupload instance.
	 *    - params    - An object of parameters to pass to $_POST when uploading the file.
	 *                  Extends this.plupload.multipart_params under the hood.
	 *
	 * @param attributes - object - Attributes and methods for this specific instance.
	 */
	ECVUploader = function ( options ) {
		var self = this,
			elements = {
				container: 'container',
				browser:   'browse_button',
				dropzone:  'drop_element'
			},
			key, error;

		this.supports = {
			upload: ECVUploader.browser.supported
		};

		this.supported = this.supports.upload;

		if ( !this.supported ) {
			return;
		}

		// Use deep extend to ensure that multipart_params and other objects are cloned.
		this.plupload = $.extend( true, { multipart_params: {} }, ECVUploader.defaults );
		this.container = document.body; // Set default container.

		// Extend the instance with options
		//
		// Use deep extend to allow options.plupload to override individual
		// default plupload keys.
		$.extend( true, this, options );

		// Proxy all methods so this always refers to the current instance.
		for ( key in this ) {
			if ( $.isFunction( this[ key ] ) ) {
				this[ key ] = $.proxy( this[ key ], this );
			}
		}

		// Ensure all elements are jQuery elements and have id attributes
		// Then set the proper plupload arguments to the ids.
		for ( key in elements ) {
			if ( !this[ key ] ) {
				continue;
			}

			this[ key ] = $( this[ key ] ).first();

			if ( !this[ key ].length ) {
				delete this[ key ];
				continue;
			}

			if ( !this[ key ].prop( 'id' ) ) {
				this[ key ].prop( 'id', '__ecv-uploader-id-' + ECVUploader.uuid++ );
			}
			this.plupload[ elements[ key ] ] = this[ key ].prop( 'id' );
		}

		// If the uploader has neither a browse button nor a dropzone, bail.
		if ( !( this.browser && this.browser.length ) && !( this.dropzone && this.dropzone.length ) ) {
			return;
		}

		this.uploader = new plupload.Uploader( this.plupload );
		delete this.plupload;

		// Set default params and remove this.params alias.
		this.param( this.params || {} );
		delete this.params;

		error = function ( message, data, file ) {
			if ( file.ecv_video ) {
				file.ecv_video.destroy();
			}

			ECVUploader.errors.unshift( {
				message: message || pluploadL10n.default_error,
				data:    data,
				file:    file
			} );

			self.error( message, data, file );
		};

		this.uploader.init();

		this.supports.dragdrop = this.uploader.features.dragdrop && !ECVUploader.browser.mobile;

		// Generate drag/drop helper classes.
		(function ( dropzone, supported ) {
			var timer, active;

			if ( !dropzone ) {
				return;
			}

			dropzone.toggleClass( 'supports-drag-drop', !!supported );

			if ( !supported ) {
				return dropzone.unbind( '.wp-uploader' );
			}

			// 'dragenter' doesn't fire correctly,
			// simulate it with a limited 'dragover'
			dropzone.bind( 'dragover.wp-uploader', function () {
				if ( timer ) {
					clearTimeout( timer );
				}

				if ( active ) {
					return;
				}

				dropzone.trigger( 'dropzone:enter' ).addClass( 'drag-over' );
				active = true;
			} );

			dropzone.bind( 'dragleave.wp-uploader, drop.wp-uploader', function () {
				// Using an instant timer prevents the drag-over class from
				// being quickly removed and re-added when elements inside the
				// dropzone are repositioned.
				//
				// See http://core.trac.wordpress.org/ticket/21705
				timer = setTimeout( function () {
					active = false;
					dropzone.trigger( 'dropzone:leave' ).removeClass( 'drag-over' );
				}, 0 );
			} );
		}( this.dropzone, this.supports.dragdrop ));

		if ( this.browser ) {
			this.browser.on( 'mouseenter', this.refresh );
		} else {
			this.uploader.disableBrowse( true );
			// If HTML5 mode, hide the auto-created file container.
			$( '#' + this.uploader.id + '_html5_container' ).hide();
		}

		this.uploader.bind( 'FilesAdded', function ( up, files ) {
			var requests = [];
			var extensions = 'avi,mov,mp4,m4v,mkv,mpg,mpeg,3gp,flv'.split( ',' );
			_.each( files, function ( file ) {
				// Ignore failed uploads.
				if ( plupload.FAILED === file.status ) {
					return;
				}

				var extension = file.name.substring( file.name.lastIndexOf( '.' ) + 1 ).toLowerCase();
				if ( !_.contains( extensions, extension ) ) {
					/*
					 up.trigger('Error', {
					 code: plupload.FILE_EXTENSION_ERROR,
					 message: "A Message",
					 file: file
					 });
					 */
					error( pluploadL10n.invalid_filetype, {}, file );
					up.removeFile( file );
					return
				}

				requests.push( wp.media.ajax( {
					data:    {
						action:   'ecv-create-video',
						filename: file.name
					},
					success: function ( data, status, xhr ) {
						var attributes = _.extend( data || {}, {
							file:      file,
							uploading: true,
							date:      new Date(),
							title:     file.name
						}, _.pick( file, 'loaded', 'size', 'percent' ) );

						//Create the `Video`
						file.ecv_video = ekko.media.model.Video.create( attributes );

						ECVUploader.queue.add( file.ecv_video );

						self.added( file.ecv_video );
					}
				} ) );
			} );

			$.when.apply( $, requests ).always( function () {
				up.refresh();
				up.start();
			} )
		} );

		this.uploader.bind( 'BeforeUpload', function ( up, file ) {
			//Set the s3 object key before upload
			self.param( 'key', file.ecv_video.get( 'key' ) );
		} );

		this.uploader.bind( 'UploadProgress', function ( up, file ) {
			file.ecv_video.set( _.pick( file, 'loaded', 'percent' ) );
			self.progress( file.ecv_video );
		} );

		this.uploader.bind( 'FileUploaded', function ( up, file, response ) {
			try {
				response = $( $.parseXML( response.response ) );
			} catch ( e ) {
				return error( pluploadL10n.default_error, e, file );
			}

			_.each( ['file', 'loaded', 'size', 'percent'], function ( key ) {
				file.ecv_video.unset( key );
			} );

			wp.media.ajax( {
				data:    {
					action: 'ecv-process-video',
					id:     file.ecv_video.id,
					key:    response.find( 'Key' ).text(),
					bucket: response.find( 'Bucket' ).text()
				},
				success: function ( data, status, xhr ) {
					var complete;

					file.ecv_video.set( _.extend( data, { uploading: false } ) );
					ekko.media.model.Video.get( data.id, file.ecv_video );

					complete = ECVUploader.queue.all( function ( video ) {
						return !video.get( 'uploading' );
					} );

					if ( complete ) {
						ECVUploader.queue.reset();
					}

					self.success( file.ecv_video );
				}
			} );
		} );

		this.uploader.bind( 'Error', function ( up, pluploadError ) {
			var message = pluploadL10n.default_error,
				key;

			// Check for plupload errors.
			for ( key in ECVUploader.errorMap ) {
				if ( pluploadError.code === plupload[ key ] ) {
					message = ECVUploader.errorMap[ key ];
					if ( _.isFunction( message ) ) {
						message = message( pluploadError.file, pluploadError );
					}
					break;
				}
			}

			error( message, pluploadError, pluploadError.file );
			up.refresh();
		} );

		this.init();
	};

	// Adds the 'defaults' and 'browser' properties.
	$.extend( ECVUploader, _ecvPluploadSettings );

	ECVUploader.uuid = 0;

	ECVUploader.errorMap = {
		'FAILED':                 pluploadL10n.upload_failed,
		'FILE_EXTENSION_ERROR':   pluploadL10n.invalid_filetype,
		'IMAGE_FORMAT_ERROR':     pluploadL10n.not_an_image,
		'IMAGE_MEMORY_ERROR':     pluploadL10n.image_memory_exceeded,
		'IMAGE_DIMENSIONS_ERROR': pluploadL10n.image_dimensions_exceeded,
		'GENERIC_ERROR':          pluploadL10n.upload_failed,
		'IO_ERROR':               pluploadL10n.io_error,
		'HTTP_ERROR':             pluploadL10n.http_error,
		'SECURITY_ERROR':         pluploadL10n.security_error,

		'FILE_SIZE_ERROR': function ( file ) {
			return pluploadL10n.file_exceeds_size_limit.replace( '%s', file.name );
		}
	};

	$.extend( ECVUploader.prototype, {
		/**
		 * Acts as a shortcut to extending the uploader's multipart_params object.
		 *
		 * param( key )
		 *    Returns the value of the key.
		 *
		 * param( key, value )
		 *    Sets the value of a key.
		 *
		 * param( map )
		 *    Sets values for a map of data.
		 */
		param: function ( key, value ) {
			if ( arguments.length === 1 && typeof key === 'string' ) {
				return this.uploader.settings.multipart_params[ key ];
			}

			if ( arguments.length > 1 ) {
				this.uploader.settings.multipart_params[ key ] = value;
			} else {
				$.extend( this.uploader.settings.multipart_params, key );
			}
		},

		init:     function () {
		},
		error:    function () {
		},
		success:  function () {
		},
		added:    function () {
		},
		progress: function () {
		},
		complete: function () {
		},
		refresh:  function () {
			var node, attached, container, id;

			if ( this.browser ) {
				node = this.browser[0];

				// Check if the browser node is in the DOM.
				while ( node ) {
					if ( node === document.body ) {
						attached = true;
						break;
					}
					node = node.parentNode;
				}

				// If the browser node is not attached to the DOM, use a
				// temporary container to house it, as the browser button
				// shims require the button to exist in the DOM at all times.
				if ( !attached ) {
					id = 'wp-uploader-browser-' + this.uploader.id;

					container = $( '#' + id );
					if ( !container.length ) {
						container = $( '<div class="wp-uploader-browser" />' ).css( {
							position: 'fixed',
							top:      '-1000px',
							left:     '-1000px',
							height:   0,
							width:    0
						} ).attr( 'id', 'wp-uploader-browser-' + this.uploader.id ).appendTo( 'body' );
					}

					container.append( this.browser );
				}
			}

			this.uploader.refresh();
		}
	} );

	ECVUploader.queue = new ekko.media.model.Videos( [], {query: false} );
	ECVUploader.errors = new Backbone.Collection();

	//Attach ECVUploader to ekko namespace
	exports.ECVUploader = ECVUploader;
})( ekko, jQuery );
