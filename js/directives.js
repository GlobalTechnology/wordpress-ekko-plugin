'use strict';

angular.module('EkkoApp.directives', [])
	.directive('ckEditor', [function() {
		return {
			require: 'ngModel',
			link: function(scope, element, attrs, ngModel) {
				var defaults = {
						customConfig: '',
						toolbar: [ {
							items: [ 'Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript' ]
						}, {
							items: [ 'NumberedList', 'BulletedList', '-', 'Outdent', 'Indent' ]
						}, {
							items: [ 'Link', 'Unlink' ]
						}, {
							items: [ 'Format' ]
						}, {
							items: [ 'PageBreak' ]
						} ]
					},
					opts = angular.extend( {}, defaults, scope.$eval( attrs.ckEditor ) ),
					ck = undefined,
					createEditor = function() {
						ck = CKEDITOR.replace( element[ 0 ], opts );

						ck.on( 'instanceReady', function() {
							ck.setData( ngModel.$viewValue );
						});

						ck.on( 'pasteState', function() {
							scope.$apply( function() {
								ngModel.$setViewValue( ck.getData() );
							} );
						});
					},
					destroyEditor = function() {
						if( ck )
							ck.destroy();
						ck = undefined;
					};

				createEditor();

				ngModel.$render = function(value) {
					if( ck )
						ck.setData( ngModel.$viewValue );
				};

				element.bind('$destroy', function() {
					destroyEditor();
				});

				scope.$on( 'sortStart', function( e ) {
					destroyEditor();
				} );

				scope.$on( 'sortStop', function( e ) {
					createEditor();
				} );
			}
		};
	}])
	.directive( 'uiSwitch', [ function() {
		return {
			restrict: 'A',
			replace: true,
			template: '<div class="switch switch-small" data-on="success" data-off="error" data-off-label="<i class=\'icon-remove\'></i>" data-on-label="<i class=\'icon-ok icon-white\'></i>"><input type="checkbox"></div>',
			require: '^ngModel',
			link: function(scope, element, attrs, ngModel) {
				var bs = null;

				ngModel.$render = function() {
					if( ! bs ) {
						jQuery(':checkbox', element).prop('checked', ngModel.$viewValue );
						bs = element.bootstrapSwitch();
					} else
						bs.bootstrapSwitch('setState', ngModel.$viewValue );
				};

				element.on( 'switch-change', function( event, data ) {
					if( data.value === ngModel.$viewValue )
						return;
					scope.$apply(function() {
						ngModel.$setViewValue( data.value );
					});
				} );

				element.bind('$destroy', function() {
					if( bs )
						bs.bootstrapSwitch('destroy');
				});
			}
		};
	} ] )
	.directive( 'testing', [ function() {

	} ] );
