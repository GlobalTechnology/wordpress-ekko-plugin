<?php namespace GTO\Framework\Util {

	class XML {

		/**
		 * Parses an xml string into a DOMDocument
		 *
		 * @param string $xml
		 *
		 * @return \DOMDocument|false DomDocument on success or false
		 */
		final public static function parse_xml_to_domdoc( $xml ) {
			$dom = new \DOMDocument( '1.0', 'UTF-8' );
			set_error_handler( function ( $error, $error_string ) {
				if ( $error === E_WARNING && stripos( $error_string, "DOMDocument::loadXML()" ) !== false ) {
					return true;
				}
				return false;
			} );
			$res = $dom->loadXML( $xml );
			restore_error_handler();
			if ( $res )
				return $dom;
			return false;
		}

		/**
		 * Returns an xpath parser for the given dom with all XML namespaces registered
		 *
		 * @param \DOMDocument $dom
		 * @param array        $namespaces
		 *
		 * @return \DOMXPath
		 */
		final public static function xpath_parser( $dom, $namespaces = array() ) {
			$xpath = new \DOMXPath( $dom );
			foreach ( $namespaces as $prefix => $namespace ) {
				$xpath->registerNamespace( $prefix, $namespace );
			}
			return $xpath;
		}

	}
}
