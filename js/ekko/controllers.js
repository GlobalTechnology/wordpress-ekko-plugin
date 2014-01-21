'use strict';

angular.module( 'EkkoApp.controllers', [] )
	.controller( 'MetaController', [ '$scope', function ( $scope ) {

	} ] )
	.controller( 'CourseCompleteController', [ '$scope', function ( $scope ) {
		var ekko_complete = jQuery( 'input[name="ekko-complete"]' );
		$scope.complete = angular.fromJson( ekko_complete.val() ) || { message: '' };

		$scope.ckeditor = {
			toolbar: [
				{
					items: [ 'Bold', 'Italic', 'Underline' ]
				},
				{
					items: [ 'Link', 'Unlink' ]
				},
				{
					items: [ 'Format' ]
				}
			]
		};

		$scope.$watch( 'complete', function ( value ) {
			ekko_complete.val( angular.toJson( value, false ) );
		}, true );
	} ] )
	.controller( 'CourseController', [ '$scope', '$location', '$anchorScroll', function ( $scope, $location, $anchorScroll ) {

		$scope.sortableOpts = {
			handle:      '.item-drag-handle',
			helper:      'clone',
			placeholder: 'lesson-placeholder',
			start:       function ( e, ui ) {
				$scope.$broadcast( 'sortStart' );
			},
			stop:        function ( e, ui ) {
				$scope.$broadcast( 'sortStop' );
			}
		};

		$scope.removeContentItem = function ( index ) {
			$scope.$broadcast( 'sortStart' );
			$scope.lessons.splice( index, 1 );
			_.defer( function () {
				$scope.$broadcast( 'sortStop' );
			} );
		};

		$scope.addItem = function ( item ) {
			$scope.lessons.push( item );
			$location.hash( item.id );
			_.defer( function () {
				$anchorScroll( item.id );
			} );
		};

		//Initialize course form input field JSON
		$scope.ekko_lessons = jQuery( 'input[name="ekko-lessons"]' );
		$scope.lessons = angular.fromJson( $scope.ekko_lessons.val() ) || [];

		//Add a new Lesson if the course is empty
		if ( $scope.lessons.length == 0 ) {
			$scope.lessons.push( $scope.$ekko.lesson() );
		}

		//Watch for changes to the course and save them to the form field as JSON
		$scope.$watch( 'lessons', function ( value ) {
			$scope.ekko_lessons.val( angular.toJson( value, false ) );
		}, true );

	} ] )
	.controller( 'MediaAssetsController', [ '$scope', function ( $scope ) {
		$scope._editors = {};

		//Callback for media library
		$scope.addMediaCallback = function ( media ) {
			var item = null;
			if ( 'ecv' == media.mediaType ) {
				item = $scope.$ekko.media( 'video' );
				item.resource = $scope.$ekko.media_ecv( media.attributes.id, media.attributes.title );
				item.thumbnail = $scope.$ekko.media_ecv( media.attributes.id, media.attributes.title );
			}
			else if ( 'file' == media.mediaType ) {
				if ( _.indexOf( [ 'video', 'image' ], media.attributes.type ) > -1 ) {
					item = $scope.$ekko.media( media.attributes.type );
					item.resource = $scope.$ekko.media_file( media.attributes.id );
				}
			}
			else if ( 'embed' == media.mediaType ) {
				if ( 'video' == media.attributes.type ) {
					item = $scope.$ekko.media( 'video' );
					item.resource = $scope.$ekko.media_uri( media.attributes.url, media.attributes.provider_name.toLowerCase() );
					if ( media.attributes.thumbnail_url ) {
						item.thumbnail = $scope.$ekko.media_uri( media.attributes.thumbnail_url );
					}
				}
			}

			if ( item ) {
				$scope.$apply( function () {
					$scope.item.media.assets.push( item );
					$scope.item.media.active = true;
				} );
			}
		};

		//Open the media library and assign the callback
		$scope.addMedia = function ( type ) {
			var id, editor;
			type = type || 'image';
			id = $scope.item.id + '-' + type;

			editor = $scope._editors[ id ];
			if ( !editor ) {
				editor = $scope._editors[ id ] = ekko.editor.open( id, { frame: type } );
				editor.on( 'add-media', $scope.addMediaCallback, editor );
			}
			else {
				editor.open();
			}
		};

		//Remove the editors when $state is destroyed
		$scope.$on( '$destroy', function () {
			for ( var prop in $scope._editors ) {
				ekko.editor.remove( prop );
				delete $scope._editors[ prop ];
			}
		} );
	}] )
	.controller( 'MediaAssetItemController', [ '$scope', function ( $scope ) {
		$scope.thumbnail_url = null;

		$scope.updateThumbnailUrl = function () {
			if ( _.indexOf( [ 'video', 'audio' ], $scope.media.type ) > -1 ) {
				if ( $scope.media.thumbnail ) {
					if( $scope.media.thumbnail.type == "uri" ) {
						$scope.thumbnail_url = $scope.media.thumbnail.uri;
					}
					else if( $scope.media.thumbnail.type == "ecv" ) {
						$scope.thumbnail_url = _EkkoAppL10N.api_url + '?action=ecv-video-thumbnail&id=' + $scope.media.thumbnail.ecv_id;
					}
					else {
						$scope.thumbnail_url = _EkkoAppL10N.api_url + '?action=ekko-thumbnail&id=' + $scope.media.thumbnail.post_id;
					}
				}
			}
			else {
				$scope.thumbnail_url = ( $scope.media.resource.type == "uri" ) ? $scope.media.resource.uri : _EkkoAppL10N.api_url + '?action=ekko-thumbnail&id=' + $scope.media.resource.post_id;
			}
		};
		$scope.updateThumbnailUrl();

		$scope.addMediaThumbnailCallback = function () {
			var state = $scope._thumbnail.state(),
				selection = state.get( 'selection' ).single();

			$scope.$apply( function () {
				$scope.media.thumbnail = $scope.$ekko.media_file( selection.attributes.id );
				$scope.updateThumbnailUrl();
			} );
		};

		$scope.addMediaThumbnail = function () {
			if ( $scope._thumbnail ) {
				$scope._thumbnail.open();
			}
			else {
				$scope._thumbnail = ekko.thumbnail.open( $scope.media.id, {} );
				$scope._thumbnail.on( 'select', $scope.addMediaThumbnailCallback );
			}
		};

		$scope.$on( '$destroy', function () {
			ekko.thumbnail.remove( $scope.media.id );
		} );
	} ] )
	.controller( 'MultipleChoiceQuestionController', [ '$scope', function ( $scope ) {
		$scope.$watch( 'question.options', function ( newVal, oldVal ) {
			var answers = _.where( newVal, { answer: true } );
			if ( answers.length == 0 && newVal.length > 0 ) {
				_.first( newVal ).answer = true;
				return;
			}
			while ( answers.length > 1 ) {
				answers.pop().answer = false;
			}
		}, true );
	} ] );
