<?php namespace GTO\Framework\Util {

	class User {

		/**
		 * Get a user transient
		 *
		 * @param \WP_User|int $user
		 * @param string       $transient
		 *
		 * @return mixed
		 */
		final public static function get_user_transient( $user, $transient ) {
			$user = ( $user instanceof \WP_User ) ? : new \WP_User( $user );
			$pre  = apply_filters( 'pre_user_transient_' . $transient, false, $user->ID );
			if ( false !== $pre )
				return $pre;

			if ( wp_using_ext_object_cache() ) {
				$value = wp_cache_get( "{$transient}-{$user->ID}", 'user_transient' );
			}
			else {
				$transient_option = "_transient_{$transient}";
				if ( ! defined( 'WP_INSTALLING' ) ) {
					$transient_timeout = "_transient_timeout_{$transient}";
					$timeout           = get_user_meta( $user->ID, $transient_timeout, true );
					if ( $timeout != '' && $timeout < time() ) {
						delete_user_meta( $user->ID, $transient_timeout );
						delete_user_meta( $user->ID, $transient_option );
						$value = false;
					}
				}

				if ( ! isset( $value ) )
					$value = get_user_meta( $user->ID, $transient_option, true );
			}

			return apply_filters( 'user_transient_' . $transient, $value, $user->ID );
		}

		/**
		 * Set a user transient
		 *
		 * @param \WP_User|int $user
		 * @param string       $transient
		 * @param mixed        $value
		 * @param int          $expiration
		 *
		 * @return bool
		 */
		final public static function set_user_transient( $user, $transient, $value, $expiration = 0 ) {
			$user       = ( $user instanceof \WP_User ) ? : new \WP_User( $user );
			$value      = apply_filters( 'pre_set_user_transient_' . $transient, $value, $user->ID );
			$expiration = (int)$expiration;

			if ( wp_using_ext_object_cache() ) {
				$result = wp_cache_set( "{$transient}-{$user->ID}", $value, 'user_transient', $expiration );
			}
			else {
				$result = update_user_meta( $user->ID, "_transient_{$transient}", $value );
				if ( $expiration ) {
					update_user_meta( $user->ID, "_transient_timeout_{$transient}", time() + $expiration );
				}
			}
			if ( $result ) {
				do_action( 'set_user_transient_' . $transient, $value, $user->ID, $expiration );
			}
			return $result;
		}

		/**
		 * Delete a user transient
		 *
		 * @param \WP_User|int $user
		 * @param string       $transient
		 *
		 * @return bool
		 */
		final public static function delete_user_transient( $user, $transient ) {
			$user = ( $user instanceof \WP_User ) ? : new \WP_User( $user );
			do_action( 'delete_user_transient_' . $transient, $transient, $user->ID );

			if ( wp_using_ext_object_cache() ) {
				$result = wp_cache_delete( "{$transient}-{$user->ID}", 'user_transient' );
			}
			else {
				$result = delete_user_meta( $user->ID, "_transient_{$transient}" );
				if ( $result )
					delete_user_meta( $user->ID, "_transient_timeout_{$transient}" );
			}

			if ( $result )
				do_action( 'deleted_user_transient', $transient );
			return $result;
		}

	}
}
