<?php namespace GTO\Framework\Util {

	class String {

		/**
		 * Format string with named parameters
		 *
		 * @param string $format
		 * @param array  $args
		 *
		 * @return string
		 */
		final public static function vnsprintf( $format, $args ) {
			preg_match_all( '/%\((.*?)\)/', $format, $matches, PREG_SET_ORDER );

			$values = array();
			foreach ( $matches as $match ) {
				$values[ ] = $args[ $match[ 1 ] ];
			}

			$format = preg_replace( '/%\((.*?)\)/', '%', $format );
			return vsprintf( $format, $values );
		}

	}
}
