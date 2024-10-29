<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://acquaintsoft.com/
 * @since             1.0.0
 * @package           Acquaintsoft_sidebar_generator
 *
 * @wordpress-plugin
 * Plugin Name:       Acquaintsoft sidebar generator
 * Plugin URI:        http://acquaintsoft.com/
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            Acquaintsoft
 * Author URI:        http://acquaintsoft.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       acquaintsoft_sidebar_generator
 * Domain Path:       /languages
 */

// If this file is called directly, abort.

function ACS_init() {
	if ( class_exists( 'ACS_Class' ) ) {
		return false;
	}
	$plugin_dir = dirname( __FILE__ );
	$plugin_dir_rel = dirname( plugin_basename( __FILE__ ) );
	$plugin_url = plugin_dir_url( __FILE__ );

	define( 'AQU_PLUGIN', __FILE__ );
	define( 'AQU_IS_PRO', false );
	define( 'CSB_VIEWS_DIR', $plugin_dir . '/view/' );
	define( 'AQU_CLASS_DIR', $plugin_dir . '/include/' );
	define( 'AQU_JS_URL', $plugin_url . 'js/' );
	define( 'AQU_CSS_URL', $plugin_url . 'css/' );

	// Include function library.
	$modules[] = AQU_CLASS_DIR . 'admin/index.php';
	$modules[] = AQU_CLASS_DIR . 'acquaintsoft_sidebar_generator_class.php';

	foreach ( $modules as $path ) {
		if ( file_exists( $path ) ) { require_once $path; }
	}

	
}

ACS_init();
// Initialize the plugin
ACS_Class::instance();