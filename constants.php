<?php namespace Ekko {

	/**
	 * Localization Text Domain
	 * @var string
	 */
	const TEXT_DOMAIN = 'ekko';

	/**
	 * Ekko Plugin Version
	 * @var string
	 */
	const VERSION = '0.3';

	/**
	 * Ekko XML Namespace - Manifest
	 * @var string
	 */
	const XMLNS_MANIFEST = 'https://ekkoproject.org/manifest';

	/**
	 * Ekko XML Namespace - Hub
	 * @var string
	 */
	const XMLNS_HUB = 'https://ekkoproject.org/hub';

	/**
	 * Plugin URL
	 * @name PLUGIN_URL
	 * @var string
	 */
	define( __NAMESPACE__ . '\PLUGIN_URL', plugin_dir_url( __FILE__ ) );

	/**
	 * Plugin Directory
	 * @name PLUGIN_DIR
	 * @var string
	 */
	define( __NAMESPACE__ . '\PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

}
