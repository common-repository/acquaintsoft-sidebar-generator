<?php
//Ui component accessed via ad_action() css,js etc..

class Admin_Ui extends Admin {

	// Load the main JS module and basic styles.
	const MODULE_CORE = 'core';

	public function __construct() {
		parent::__construct();

		// Check for persistent data from last request that needs to be processed.
		$this->add_action(
			'plugins_loaded',
			'_check_admin_notices'
		);
	}

	public function add( $module = 'core', $onpage = 'all' ) {
		switch ( $module ) {
			case 'core':
				$this->css( $this->_css_url( 'acquaintsoft_sidebar_generator-admin.css' ), $onpage );
				$this->js( $this->_js_url( 'acquaintsoft_sidebar_generator-admin.js' ), $onpage );
				break;

			default:
				$ext = strrchr( $module, '.' );

				if ( AC_UNMINIFIED ) {
					$module = str_replace( '.min' . $ext, $ext, $module );
				}
				if ( '.css' === $ext ) {
					$this->css( $module, $onpage, 20 );
				} else if ( '.js' === $ext ) {
					$this->js( $module, $onpage, 20 );
				}
		}
	}

	public function data( $name, $data ) {
		$this->_add( 'js_data_hook', true );

		// Determine which hook should print the data.
		$hook = ( is_admin() ? 'admin_footer' : 'wp_footer' );

		// Enqueue the data for output with javascript sources.
		$this->_add( 'js_data', array( $name, $data ) );

		$this->add_action( $hook, '_print_script_data' );
	}

	public function script( $jscript ) {
		$this->_add( 'js_data_hook', true );

		// Determine which hook should print the data.
		$hook = ( is_admin() ? 'admin_footer' : 'wp_footer' );

		// Enqueue the data for output with javascript sources.
		$this->_add( 'js_script', $jscript );

		$this->add_action( $hook, '_print_script_code' );
	}
	public function js( $url, $onpage = 'all', $priority = 10 ) {
		$this->_prepare_js_or_css( $url, 'js', $onpage, $priority );
	}
	public function css( $url, $onpage = 'all', $priority = 10 ) {
		$this->_prepare_js_or_css( $url, 'css', $onpage, $priority );
	}
	protected function _prepare_js_or_css( $url, $type, $onpage, $priority ) {
		$this->_add( 'js_or_css', compact( 'url', 'type', 'onpage', 'priority' ) );

		$this->add_action( 'init', '_add_js_or_css' );
	}

	public function _get_script_handle( $item ) {
		if ( ! property_exists( $item, 'handle' ) ) {
			$item->handle = '';
		}

		return $item->handle;
	}
	public function _add_js_or_css() {
		global $wp_styles, $wp_scripts;

		$scripts = $this->_get( 'js_or_css' );
		$this->_clear( 'js_or_css' );

		// Prevent adding the same URL twice.
		$done_urls = array();

		foreach ( $scripts as $script ) {
			extract( $script ); // url, type, onpage, priority

			// Skip Front-End files in Admin Dashboard.
			if ( 'front' === $onpage && is_admin() ) { continue; }

			// Prevent adding the same URL twice.
			if ( in_array( $url, $done_urls ) ) { continue; }
			$done_urls[] = $url;

			$type = ( 'css' === $type || 'style' === $type ? 'css' : 'js' );

			// The $handle values are intentionally not cached:
			// Any plugin/theme could add new handles at any moment...
			$handles = array();
			if ( 'css' == $type ) {
				if ( ! is_a( $wp_styles, 'WP_Styles' ) ) {
					$wp_styles = new WP_Styles();
				}
				$handles = array_values(
					array_map(
						array( $this, '_get_script_handle' ),
						$wp_styles->registered
					)
				);
				$type_callback = '_enqueue_style_callback';
			} else {
				if ( ! is_a( $wp_scripts, 'WP_Scripts' ) ) {
					$wp_scripts = new WP_Scripts();
				}
				$handles = array_values(
					array_map(
						array( $this, '_get_script_handle' ),
						$wp_scripts->registered
					)
				);
				$type_callback = '_enqueue_script_callback';
			}

			if ( in_array( $url, $handles ) ) {
				$alias = $url;
				$url = '';
			} else {
				// Get the filename from the URL, then sanitize it and prefix "acquaint-"
				$urlparts = explode( '?', $url, 2 );
				$alias = 'acquaint-' . sanitize_title( basename( $urlparts[0] ) );
			}
			$onpage = empty( $onpage ) ? 'all' : $onpage;

			if ( ! is_admin() ) {
				$hook = 'wp_enqueue_scripts';
			} else {
				$hook = 'admin_enqueue_scripts';
			}

			$item = compact( 'url', 'alias', 'onpage' );
			$this->_add( $type, $item );

			$this->add_action( $hook, $type_callback, 100 + $priority );
		}
	}
	public function _enqueue_style_callback() {
		global $hook_suffix;

		$items = $this->_get( 'css' );
		$this->_clear( 'css' );
		$hook = $hook_suffix;

		if ( empty( $hook ) ) { $hook = 'front'; }

		foreach ( $items as $item ) {
			extract( $item ); // url, alias, onpage

			if ( empty( $onpage ) ) { $onpage = 'all'; }
			if ( 'all' == $onpage || $hook == $onpage ) {
				if ( empty( $url ) ) {
					wp_enqueue_style( $alias );
				} else {
					wp_enqueue_style( $alias, $url );
				}
			}
		}
	}

	public function _enqueue_script_callback() {
		global $hook_suffix;

		$items = $this->_get( 'js' );
		$this->_clear( 'js' );
		$hook = $hook_suffix;

		if ( empty( $hook ) ) { $hook = 'front'; }

		foreach ( $items as $item ) {
			extract( $item ); // url, alias, onpage

			if ( empty( $onpage ) ) { $onpage = 'all'; }

			if ( 'all' == $onpage || $hook == $onpage ) {
				// Load the Media-library functions.
				if ( 'acquaint:media' === $url ) {
					wp_enqueue_media();
					continue;
				}

				// Register script if it has an URL.
				if ( ! empty( $url ) ) {
					wp_register_script( $alias, $url, array( 'jquery' ), false, true );
				}

				// Enqueue the script for output in the page footer.
				wp_enqueue_script( $alias );
			}
		}
	}
	public function _print_script_data() {
		$data = $this->_get( 'js_data' );
		$this->_clear( 'js_data' );

		// Append javascript data to the script output.
		if ( is_array( $data ) ) {
			$collected = array();

			foreach ( $data as $item ) {
				if ( ! is_array( $item ) ) { continue; }
				$key = sanitize_html_class( $item[0] );
				$obj = array( 'window.' . $key => $item[1] );
				$collected = self::$core->array->merge_recursive_distinct( $collected, $obj );
			}

			echo '<script>';
			foreach ( $collected as $var => $value ) {
				printf(
					'%1$s = %2$s;',
					$var,
					json_encode( $value )
				);
			}
			echo '</script>';
		}
	}

	public function _print_script_code() {
		$data = $this->_get( 'js_script' );
		$this->_clear( 'js_script' );

		// Append javascript data to the script output.
		if ( is_array( $data ) ) {
			foreach ( $data as $item ) {
				printf(
					'<script>try { %1$s } catch( err ){ window.console.log(err.message); }</script>',
					$item
				);
			}
		}
	}
   
	public function _check_admin_notices() {
		if ( self::_sess_have( 'admin_notice' ) ) {
			$this->add_action( 'admin_notices', '_admin_notice_callback', 1 );
			$this->add_action( 'network_admin_notices', '_admin_notice_callback', 1 );
		}
	}

}
//Debug component
class Admin_Debug extends Admin  {	

}