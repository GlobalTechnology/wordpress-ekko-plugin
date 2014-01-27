'use strict';

angular.module( 'EkkoApp.directives', [] )
	.directive( 'ckEditor', [function () {
		return {
			require: 'ngModel',
			link:    function ( scope, element, attrs, ngModel ) {
				var defaults = {
						customConfig: '',
						toolbar:      [
							{
								items: [ 'Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript' ]
							},
							{
								items: [ 'NumberedList', 'BulletedList', '-', 'Outdent', 'Indent' ]
							},
							{
								items: [ 'Link', 'Unlink' ]
							},
							{
								items: [ 'Format' ]
							},
							{
								items: [ 'PageBreak' ]
							}
						]
					},
					opts = angular.extend( {}, defaults, scope.$eval( attrs.ckEditor ) ),
					ck = undefined,
					createEditor = function () {
						ck = CKEDITOR.replace( element[ 0 ], opts );

						ck.on( 'instanceReady', function () {
							ck.setData( ngModel.$viewValue );
						} );

						ck.on( 'pasteState', function () {
							scope.$apply( function () {
								ngModel.$setViewValue( ck.getData() );
							} );
						} );
					},
					destroyEditor = function () {
						if ( ck ) {
							ck.destroy();
						}
						ck = undefined;
					};

				ngModel.$render = function ( value ) {
					if ( ck ) {
						ck.setData( ngModel.$viewValue );
					}
				};

				element.bind( '$destroy', function () {
					destroyEditor();
				} );

				scope.$on( 'sortStart', function ( e ) {
					destroyEditor();
				} );

				scope.$on( 'sortStop', function ( e ) {
					createEditor();
				} );

				CKEDITOR.domReady( function () {
					createEditor();
				} );
			}
		};
	}] )
	.directive( 'uiSwitch', [ function () {
		return {
			restrict: 'A',
			replace:  true,
			template: '<div class="switch switch-small" data-on="success" data-off="error" data-off-label="<i class=\'icon-remove\'></i>" data-on-label="<i class=\'icon-ok icon-white\'></i>"><input type="checkbox"></div>',
			require:  'ngModel',
			link:     function ( scope, element, attrs, ngModel ) {
				var bs = null;

				ngModel.$render = function () {
					if ( !bs ) {
						jQuery( ':checkbox', element ).prop( 'checked', ngModel.$viewValue );
						bs = element.bootstrapSwitch();
					} else {
						bs.bootstrapSwitch( 'setState', ngModel.$viewValue );
					}
				};

				element.on( 'switch-change', function ( event, data ) {
					if ( data.value === ngModel.$viewValue ) {
						return;
					}
					scope.$apply( function () {
						ngModel.$setViewValue( data.value );
						if ( data.value ) {
							scope.$parent.$broadcast( 'ui-switch-change', {scope: scope, value: data.value } );
						}
					} );
				} );

				scope.$on( 'ui-switch-change', function ( event, value ) {
					if ( scope === value.scope ) {
						return;
					}
					if ( bs && ngModel.$viewValue ) {
						ngModel.$setViewValue( false );
						bs.bootstrapSwitch( 'setState', false );
					}
				} );

				element.bind( '$destroy', function () {
					if ( bs ) {
						bs.bootstrapSwitch( 'destroy' );
					}
				} );
			}
		};
	} ] );
