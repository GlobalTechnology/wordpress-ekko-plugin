<?php namespace Ekko\Core\Pages {

	final class SettingsPage extends \GTO\Framework\Admin\AdminPage {

		final protected function __construct() {
			$course_post_type = \Ekko\Core\CoursePostType::singleton()->post_type();
			add_submenu_page(
				"edit.php?post_type={$course_post_type}",
				__( 'Settings', \Ekko\TEXT_DOMAIN ),
				__( 'Ekko Settings', \Ekko\TEXT_DOMAIN ),
				'manage_options',
				'ekko-settings',
				array( &$this, 'display_page' )
			);
			add_action( 'load-ekko-course_page_ekko-settings', array( &$this, 'save_changes' ), 10, 0 );
		}

		final public function save_changes() {
			if ( array_key_exists( 'action', $_POST ) && 'update' == stripslashes( $_POST[ 'action' ] ) ) {
				$nonce    = array_key_exists( '_wpnonce', $_POST ) ? stripslashes( $_POST[ '_wpnonce' ] ) : false;
				if ( wp_verify_nonce( $nonce, 'settings-options' ) ) {
					if ( array_key_exists( 'jfm_arclight_enabled', $_POST ) ) {
						update_option( 'jfm_arclight_enabled', $_POST[ 'jfm_arclight_enabled' ] );
					}
				}
				wp_redirect( wp_unslash( $_REQUEST['_wp_http_referer'] ) );
				exit;
			}
		}

		final public function display_page() {
			?>
			<div class="wrap">
			<h2><?php _e( 'Settings', \Ekko\TEXT_DOMAIN ); ?></h2>

			<form method="post" action="">
				<?php settings_fields( 'settings' ); ?>
				<table class="form-table">
					<tbody>
					<tr>
						<th scope="row">
							<label for="jfm_arclight_enabled"><?php _e( 'Jesus Film Media', \Ekko\TEXT_DOMAIN ); ?></label>
						</th>
						<td>
							<select name="jfm_arclight_enabled" id="jfm_arclight_enabled">
								<?php $arclight_enabled = get_option( 'jfm_arclight_enabled', 0 ); ?>
								<option value="1" <?php selected( $arclight_enabled, 1 ); ?>>Enabled</option>
								<option value="0" <?php selected( $arclight_enabled, 0 ); ?>>Disabled</option>
							</select>
						</td>
					</tr>
					</tbody>
				</table>
				<?php submit_button(); ?>
			</form>
			</div><?php
		}
	}
}
