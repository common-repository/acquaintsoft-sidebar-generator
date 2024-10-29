<?php

$version = '1.0';

if ( ! function_exists( 'ad_action' ) ) {

	function ad_action() {
		return Thead_action_Wrap::get_obj();
	}
}
// Internal module definition:
$dirname = dirname( __FILE__ ) . '/inc/';
$files = array(
	'Admin'         => $dirname . 'class-admin.php',
	'Admin_Array'   => $dirname . 'class-admin-array.php',
	//'Admin_Debug'   => $dirname . 'class-admin-debug.php',
	'Admin_Html'    => $dirname . 'class-admin-html.php',
	'Admin_Session' => $dirname . 'class-admin-session.php',
	'Admin_Updates' => $dirname . 'class-admin-updates.php',
	'Admin_Ui'      => $dirname . 'class-admin-ui.php',
);

if ( ! class_exists( 'Thead_action_Wrap' ) ) {
	//wrapper classes

	class Thead_action_Wrap {
		static protected $version = '0.0.0';
		static protected $files = array();
		static protected $object = null;

		//store module files
		static public function set_version( $version, $files ) {
			if ( null !== self::$object ) { return; }

			if ( version_compare( $version, self::$version, '>' ) ) {
				self::$version = $version;
				self::$files = $files;
			}
		}

		/**
		 * Return the module object.
		 */
		static public function get_obj() {
			if ( null === self::$object ) {
				foreach ( self::$files as $class_name => $class_file ) {
					if ( ! class_exists( $class_name ) && file_exists( $class_file ) ) {
						require_once $class_file;
					}
				}
				self::$object = new Admin_Core();
			}
			return self::$object;
		}
	} // End: Thead_action_Wrap
}
// Stores the lib-directory if it contains the highest version files.
Thead_action_Wrap::set_version( $version, $files );
