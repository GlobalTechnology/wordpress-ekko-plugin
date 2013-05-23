'use strict';

angular.module('EkkoApp.directives', [])
	.directive('ckEditor', [function() {
		return {
			require: '?ngModel',
			link: function(scope, element, attrs, ngModel) {
				var ck = CKEDITOR.replace(element[0], {
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
				});
				if(!ngModel)
					return;

//				ck.on('beforeCommandExec', function(event) {
//					console.log( event.name );
//				});

				ck.on('instanceReady', function() {
					ck.setData(ngModel.$viewValue);
				});

				ck.on('pasteState', function() {
					scope.$apply(function() {
						ngModel.$setViewValue(ck.getData());
					});
				});

				ngModel.$render = function(value) {
					ck.setData(ngModel.$viewValue);
				};

				element.bind('$destroy', function() {
					ck.destroy();
				});
/*
				jQuery(window).on( 'sortstart sortchange sortstop sortupdate', function(event, ui) {
					if( ui.item.has( element ).length ) {
						console.log( event.type + ' - ' + scope.asset.id );
					}
				} );

				jQuery(window).on( 'sortstart', function(event, ui) {
					if( jQuery.contains( ui.item.get(0), element.get(0) ) ) {
						ck.destroy();
						ck = undefined;
					}
				} );

				jQuery(window).on( 'sortstop', function(event, ui) {
					if( ck == undefined )
						ck = CKEDITOR.replace(element[0], {});
				} );
*/

			}
		};
	}])
	.directive('uiSwitch', [function() {
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
	} ] );
