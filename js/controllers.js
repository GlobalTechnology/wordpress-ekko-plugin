'use strict';

angular.module( 'EkkoApp.controllers', [] )
	.controller( 'CourseController', [ '$scope', '$location', '$anchorScroll', function( $scope, $location, $anchorScroll ) {

		$scope.sortableOpts = {
			handle:'.item-drag-handle',
			helper:'clone',
			placeholder: 'lesson-placeholder',
			start: function( e, ui ) {
				$scope.$broadcast( 'sortStart' );
			},
			stop: function( e, ui ) {
				$scope.$broadcast( 'sortStop' );
			}
		};

		$scope.removeContentItem = function( index ) {
			$scope.$broadcast( 'sortStart' );
			$scope.lessons.splice( index, 1 );
			_.defer( function() {
				$scope.$broadcast( 'sortStop' );
			} );
		};
		
		$scope.addItem = function( item ) {
			$scope.lessons.push( item );
			$location.hash( item.id );
			_.defer( function() {$anchorScroll( item.id ); } );
		};

		//Initialize course form input field JSON
		$scope.ekko_lessons = jQuery( 'input[name="ekko-lessons"]' );
		$scope.lessons = angular.fromJson( $scope.ekko_lessons.val() ) || [];

		//Add a new Lesson if the course is empty
		if( $scope.lessons.length == 0 )
			$scope.lessons.push( $scope.$ekko.lesson() );

		//Watch for changes to the course and save them to the form field as JSON
		$scope.$watch( 'lessons', function(value) {
			$scope.ekko_lessons.val( angular.toJson(value, false) );
		}, true);

	} ] )
	.controller( 'MediaAssetsController', [ '$scope', function( $scope ) {
		//Callback for adding Media to lessons
		$scope.addMediaCallback = function( selection ) {
			var state = $scope._editor.state();
			selection = selection || state.get( 'selection' );
			if ( ! selection )
				return;

			var media = selection.single();
			var item = $scope.$ekko.media( media.attributes.type );
			item.resource = $scope.$ekko.media_file( media.attributes.id );

			$scope.$apply( function() {
				$scope.item.media.assets.push( item );
				$scope.item.media.active = true;
			} );
		};

		$scope.addOEmbedCallback = function( data, provider ) {
			var type;
			if( data.type == "video" )
				type = "video";
			else if( data.type == "photo" )
				type = "image";
			else
				return;

			var item = $scope.$ekko.media( type );
			item.resource = $scope.$ekko.media_uri( data.url, provider );
			if( type == "video" && data.thumbnail_url )
				item.thumbnail = $scope.$ekko.media_uri( data.thumbnail_url );

			$scope.$apply( function() {
				$scope.item.media.assets.push( item );
				$scope.item.media.active = true;
			} );
		};

		//Open the media library and assign the callback
		$scope.addMedia = function() {
			if( $scope._editor )
				$scope._editor.open();
			else {
				$scope._editor = ekko.media.editor.open( $scope.item.id, {} );
				$scope._editor.on( 'insert', $scope.addMediaCallback );
				$scope._editor.on( 'embed', $scope.addOEmbedCallback );
			}
		};

		//Remove the editor when $state is destroyed
		$scope.$on( '$destroy', function() {
			ekko.media.editor.remove( $scope.item.id );
		} );
	}])
	.controller( 'MediaAssetItemController', [ '$scope', function( $scope ) {
		$scope.thumbnail_url = null;

		$scope.updateThumbnailUrl = function() {
			if( _.indexOf( [ 'video', 'audio' ], $scope.media.type ) > -1 ) {
				if( $scope.media.thumbnail )
					$scope.thumbnail_url = ( $scope.media.thumbnail.type == "uri" ) ? $scope.media.thumbnail.uri : _EkkoAppL10N.api_url + '?action=ekko-thumbnail&id=' + $scope.media.thumbnail.post_id;
			}
			else
				$scope.thumbnail_url = ( $scope.media.resource.type == "uri" ) ? $scope.media.resource.uri : _EkkoAppL10N.api_url + '?action=ekko-thumbnail&id=' + $scope.media.resource.post_id;
		};
		$scope.updateThumbnailUrl();

		$scope.addMediaThumbnailCallback = function() {
			var state = $scope._thumbnail.state(),
				selection = state.get( 'selection' ).single();

			$scope.$apply( function() {
				$scope.media.thumbnail = $scope.$ekko.media_file( selection.attributes.id );
				$scope.updateThumbnailUrl();
			} );
		};

		$scope.addMediaThumbnail = function() {
			if( $scope._thumbnail )
				$scope._thumbnail.open();
			else {
				$scope._thumbnail = ekko.media.thumbnail.open( $scope.media.id, {} );
				$scope._thumbnail.on( 'select', $scope.addMediaThumbnailCallback );
			}
		};

		$scope.$on( '$destroy', function() {
			ekko.media.thumbnail.remove( $scope.media.id );
		} );
	} ] );
