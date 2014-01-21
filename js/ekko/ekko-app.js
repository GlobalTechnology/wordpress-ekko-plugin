'use strict';

angular.module('EkkoApp', ['ui', 'ui.bootstrap', 'EkkoApp.controllers', 'EkkoApp.services', 'EkkoApp.directives'])
	.run( [ '$rootScope', 'UUID', 'EKKO', function( $rootScope, UUID, EKKO ) {
		//Attach the UUID factory to the rootScope
		$rootScope.$uuid = function() {
			return UUID.get();
		};

		//Attach the Ekko factory to the rootScope
		$rootScope.$ekko = EKKO;
	} ] );

//Bootstrap angular app
angular.element(document).ready( function() {
	angular.bootstrap( document.getElementById( 'poststuff' ), [ 'EkkoApp' ] );
} );
