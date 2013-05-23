<?php namespace GTO\Framework {

	/**
	 * Singleton Pattern
	 * @author Brian Zoetewey <brian.zoetewey@ccci.org>
	 */
	abstract class Singleton {
		/**
		 * Subclass singleton instances
		 * @var array
		 */
		private static $instances = array();

		/**
		 * Returns the subclass singleton.
		 * @return Singleton
		*/
		final public static function singleton() {
			$class = get_called_class();

			if( !isset( self::$instances[ $class ] ) )
				self::$instances[ $class ] = new $class();

			return self::$instances[ $class ];
		}

		/**
		 * Do not allow object cloning
		 */
		final private function __clone() {}

		/**
		 * Constructor
		 */
		abstract protected function __construct();
	}
}