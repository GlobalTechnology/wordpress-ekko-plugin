'use strict';

angular.module( 'EkkoApp.services', [] )
	.factory( 'UUID', [ function() {
		return {
			get: function() {
				return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
					var r = Math.random()*16|0, v = c == 'x' ? r : (r&0x3|0x8);
					return v.toString(16);
				} );
			}
		};
	} ] )
	.factory( 'EKKO', [ 'UUID', function( UUID ) {
		return {
			//Returns a new Lesson
			lesson: function() {
				return {
					type: 'lesson',
					id: UUID.get(),
					active: true,
					title: '',
					media: {
						active:false,
						assets:[]
					},
					text: {
						active: true,
						id: UUID.get(),
						content: ''
					}
				};
			},
			media: function( media_type, media_id ) {
				return {
					id: UUID.get(),
					type: media_type,
					resource: {
						type: 'file',
						post_id: media_id
					},
					thumbnail: null
				};
			},
			//Returns a new Quiz
			quiz: function() {
				return {
					type: 'quiz',
					id: UUID.get(),
					active: true,
					questions: [ {
						type: 'multiple',
						active: true,
						id: UUID.get(),
						question: '',
						options: [ {
							id: UUID.get(),
							answer: true,
							text: ''
						}, {
							id: UUID.get(),
							answer: false,
							text: ''
						} ]
					} ]
				};
			},
			question_multiple: function() {
				return {
					type: 'multiple',
					active: true,
					id: UUID.get(),
					question: '',
					options: [ {
						id: UUID.get(),
						answer: true,
						text: ''
					}, {
						id: UUID.get(),
						answer: false,
						text: ''
					} ]
				};
			},
			question_multiple_option: function() {
				return {
					id: UUID.get(),
					answer: false,
					text: ''
				};
			}
		};
	} ] );