'use strict';

angular.module('EkkoApp', ['ui', 'ui.bootstrap', 'EkkoApp.controllers', 'EkkoApp.services', 'EkkoApp.directives']);

jQuery( function() {
	jQuery('#course-metadata').on('shown hidden', function( event ) {
		jQuery('#show-course-metadata').val( event.type=='shown' ? '1' : '0' );
	} );
} );