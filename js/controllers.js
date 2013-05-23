'use strict';

angular.module('EkkoApp.controllers', [])
	.controller('CourseController', ['$scope', 'UUID', function($scope, UUID){
		//Initialize course form input field JSON
		$scope.ekko_lessons = jQuery( 'input[name="ekko-lessons"]' );
		$scope.lessons = angular.fromJson( $scope.ekko_lessons.val() ) || [];

		//Method to add a new Lesson
		$scope.addLesson = function() {
			$scope.lessons.push( {
				type: 'lesson',
				title: '',
				uuid: UUID.get(),
				active: true,
				media: {
					active:false,
					assets:[]
				},
				text: {
					active: true,
					uuid: UUID.get(),
					content: ''
				}
			} );
		};

		//Method to add a new Quiz
		$scope.addQuiz = function() {
			$scope.lessons.push( {
				type: 'quiz',
				id: UUID.get(),
				active: true,
				questions: [ {
					type: 'multiple',
					active: true,
					id: UUID.get(),
					question: '',
					options: [ {
						answer: true,
						text: ''
					}, {
						answer: false,
						text: ''

					} ]
				} ]
			} );
		};

		$scope.removeLesson = function( index ) {
			$scope.lessons.splice( index, 1 );
		};

		if( $scope.lessons.length == 0 )
			$scope.addLesson();

		//Watch for changes to the course and save them to the form field as JSON
		$scope.$watch( 'lessons', function(value) {
			$scope.ekko_lessons.val( angular.toJson(value, false) );
		}, true);
	}])
	.controller('MediaAssetsController', ['$scope', 'UUID', '$rootScope', function($scope, UUID, $rootScope){
		$scope.media_frame = function() {
			if( $scope._frame )
				return $scope._frame;
			$scope._frame = wp.media.frames.ekkoLibrary.media_frame( $scope.item.uuid );
			$scope._frame.on( 'select', $scope.addMediaCallback );
			return $scope._frame;
		};

		$scope.addMediaAsset = function() {
			$scope.media_frame().open();
		};

		$scope.addMediaCallback = function() {
			var item = $scope.media_frame().state().get('selection').first();
			$scope.item.media.assets.push( {
				id: UUID.get(),
				type: item.attributes.type,
				post_id: item.attributes.id,
				thumbnail_id: item.attributes.id
			} );
			$rootScope.$digest();
		};

		$scope.$on('$destroy', function() {
			wp.media.frames.ekkoLibrary.remove( $scope.item.uuid );
		});
	}])
	.controller('MediaAssetItemController', ['$scope', function($scope) {
		$scope.thumbnail_url = EkkoL10N.api_url + '?action=ekko-thumbnail&id=';
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
	}]);
