<?php namespace Ekko {

	spl_autoload_register( function ( $class_name ) {

		if ( strpos( $class_name, __NAMESPACE__ ) === 0 ) {
			$name     = preg_replace( '/^' . preg_quote( __NAMESPACE__ ) . '/', '', $class_name );
			$name     = str_replace( '\\', DIRECTORY_SEPARATOR, $name );
			$filename = strtolower( preg_replace( '/([a-z])([A-Z])/', '$1-$2', $name ) ) . '.php';

			require_once( dirname( __FILE__ ) . $filename );
		}

	} );

}
