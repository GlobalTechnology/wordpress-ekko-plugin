<?php namespace Ekko\Core\Services {

	final class HubSession {
		/**
		 * Session Expires Time
		 * @var \DateTime
		 */
		public $expires = null;

		/**
		 * Session ID
		 * @var string
		 */
		public $session;

		final public function __construct( $session ) {
			$this->session = $session;
			$this->expires = new \DateTime( null, new \DateTimeZone( 'UTC' ) );
			$this->expires->add( new \DateInterval( 'PT5H' ) );
		}

		final public function valid() {
			if( $this->session && $this->expires instanceof \DateTime ) {
				$now = new \DateTime( null, new \DateTimeZone( 'UTC' ) );
				if( $this->expires >= $now )
					return true;
			}
			return false;
		}
	}
}