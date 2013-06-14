'use strict';

angular.module( 'EkkoApp.controllers', [] )
	.controller( 'CourseController', [ '$scope', function( $scope ) {

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
	.controller( 'MediaAssetsController', [ '$scope', '$rootScope', function( $scope, $rootScope ) {
		//Callback for adding Media to lessons
		$scope.addMediaCallback = function( selection ) {
			var state = $scope._editor.state();
			selection = selection || state.get('selection');
			if ( ! selection )
				return;

			var media = selection.single();
			$scope.$apply( function() {
				$scope.item.media.assets.push( $scope.$ekko.media( media.attributes.type, media.attributes.id ) );
			} );
		};

		//Open the media library and assign the callback
		$scope.addMedia = function() {
			if( $scope._editor )
				$scope._editor.open();
			else {
				$scope._editor = ekko.media.editor.open( $scope.item.id, {} );
				$scope._editor.on( 'insert', $scope.addMediaCallback );
			}
		};

		//Remove the editor when $state is destroyed
		$scope.$on( '$destroy', function() {
			ekko.media.editor.remove( $scope.item.id );
		} );
	}])
	.controller( 'MediaAssetItemController', [ '$scope', function( $scope ) {
		$scope.thumbnail_url = _EkkoAppL10N.api_url + '?action=ekko-thumbnail&id=';

		$scope.addMediaThumbnailCallback = function( selection ) {

		};

		$scope.addMediaThumbnail = function() {
			if( $scope._thumbnail )
				$scope._thumbnail.open();
			else {
				$scope._thumbnail = ekko.media.editor.open( $scope.media.id, { library: { type: 'image' } } );
			}
		};
/*
		$scope.addMediaThumbnail = function() {
			$scope._frame = wp.media.frames.ekkoLibrary.media_frame( $scope.media.id );
			$scope._frame.off('select').on( 'select', $scope.mediaFrameCallback );
			$scope._frame.open();
		};
		$scope.removeMedia = function() {
			$scope.item.media.assets.splice( $scope.$index, 1 );
		};
		$scope.mediaFrameCallback = function() {
			var item = $scope._frame.state().get('selection').first();
			$scope.media.thumbnail_id = item.attributes.id;
			$scope.$parent.$digest();
		};
*/
	}]);
