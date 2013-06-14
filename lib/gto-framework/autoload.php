<?php namespace GTO\Framework {

	spl_autoload_register( function( $class_name ) {

		if( strpos( $class_name, __NAMESPACE__ ) === 0 ) {
			$name = str_replace( '\\', DIRECTORY_SEPARATOR, str_replace( __NAMESPACE__, '', $class_name ) );
			$filename = dirname( __FILE__ ) . strtolower( preg_replace('/([a-z])([A-Z])/', '$1-$2', $name ) ) . '.php';
			if( file_exists( $filename ) )
				require_once( $filename );
		}

	} );
}