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
			media: function( type ) {
				return {
					id: UUID.get(),
					type: type,
					resource: null,
					thumbnail: null
				};
			},
			media_file: function( post_id ) {
				return {
					type: 'file',
					post_id: post_id
				};
			},
			media_uri: function( uri, provider ) {
				provider = ( typeof provider === "undefined" ) ? null : provider;
				return {
					type: 'uri',
					uri: uri,
					provider: provider
				};
			},
			//Returns a new Quiz
			quiz: function() {
				return {
					type: 'quiz',
					id: UUID.get(),
					active: true,
					title: 'Quiz',
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