<?php namespace GTO\Framework\Posts {

	abstract class PostMeta implements \Serializable {

		public function serialize() {
			return "";
		}

		public function unserialize( $serialized ) {
//			$this->__construct( unserialize( $serialized ) );
		}
	}
}