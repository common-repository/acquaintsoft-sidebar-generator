<?php
// Implement data storage and sharing among all child classes. some basic methida re added.
 
abstract class Admin {

	static protected $data = array();
	static protected $core = null;
	protected function _have( $key ) {
		return isset( self::$data[ $key ] );
	}

	protected function _add( $key, $value ) {
		if ( ! isset( self::$data[ $key ] )
			|| ! is_array( self::$data[ $key ] )
		) {
			self::$data[ $key ] = array();
		}

		self::$data[ $key ][] = $value;
	}

	protected function _get( $key ) {
		if ( ! isset( self::$data[ $key ] )
			|| ! is_array( self::$data[ $key ] )
		) {
			self::$data[ $key ] = array();
		}

		return self::$data[ $key ];
	}
	protected function _clear( $key ) {
		self::$data[ $key ] = array();
	}


	// Start of Session access

	static protected $_have_session = null;

	static private function _sess_init() {
		if ( null !== self::$_have_session ) { return; }

		self::$_have_session = false;

		if ( defined( 'AC_USE_SESSION' ) ) {
			$use_session = AC_USE_SESSION;
		} else {
			$use_session = true;
		}
		$use_session = apply_filters( 'ac_key-use_session', $use_session );

		if ( ! session_id() ) {
			if ( ! $use_session ) { return false; }

			if ( ! headers_sent() ) {
		
		      //send header information
				if ( AC_SEND_P3P ) {
					$p3p_done = false;
					foreach ( headers_list() as $header ) {
						if ( false !== stripos( $header, 'P3P:' ) ) {
							$p3p_done = true;
							break;
						}
					}
					if ( ! $p3p_done ) { header( 'P3P:' . AC_SEND_P3P ); }
				}

				session_start();
				self::$_have_session = true;
			}
		} else {
			self::$_have_session = true;
		}

		return true;
	}

	//session storage
	static protected function _sess_have( $key ) {
		if ( null === self::$_have_session ) { self::_sess_init(); }
		if ( ! self::$_have_session ) { return false; }

		return isset( $_SESSION[ '_key_persist_' . $key ] );
	}
	static protected function _sess_add( $key, $value ) {
		if ( null === self::$_have_session ) { self::_sess_init(); }
		if ( ! self::$_have_session ) { return; }

		if ( ! isset( $_SESSION[ '_key_persist_' . $key ] )
			|| ! is_array( $_SESSION[ '_key_persist_' . $key ] )
		) {
			$_SESSION[ '_key_persist_' . $key ] = array();
		}

		$_SESSION[ '_key_persist_' . $key ][] = $value;
	}

	static protected function _sess_get( $key ) {
		if ( null === self::$_have_session ) { self::_sess_init(); }
		if ( ! self::$_have_session ) { return array(); }

		if ( ! isset( $_SESSION[ '_key_persist_' . $key ] )
			|| ! is_array( $_SESSION[ '_key_persist_' . $key ] )
		) {
			$_SESSION[ '_key_persist_' . $key ] = array();
		}

		return $_SESSION[ '_key_persist_' . $key ];
	}
	static protected function _sess_clear( $key ) {
		if ( null === self::$_have_session ) { self::_sess_init(); }
		if ( ! self::$_have_session ) { return; }

		unset( $_SESSION[ '_key_persist_' . $key ] );
	}

	//initioalization
	public function __construct() {
		self::_init_const();
		self::_sess_init();
	}
	static protected function _init_const() {
		if ( ! defined( 'AC_UNMINIFIED' ) ) {
			define( 'AC_UNMINIFIED', false );
		}
		if ( ! defined( 'AC_DEBUG' ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				define( 'AC_DEBUG', true );
			} else {
				define( 'AC_DEBUG', false );
			}
		}
		if ( ! defined( 'AC_AJAX_DEBUG' ) ) {
			define( 'AC_AJAX_DEBUG', AC_DEBUG );
		}
		if ( ! defined( 'AC_SEND_P3P' ) ) {
			define( 'AC_SEND_P3P', 'CP="NOI"' );
		}
	}

	//return full url of css
	protected function _css_url( $file ) {
		static $Url = null;

		if ( AC_UNMINIFIED ) {
			$file = str_replace( '.min.css', '.css', $file );
		}
		if ( null === $Url ) {
			$Url = plugins_url( 'css/', dirname( __FILE__ ) );
		}

		return $Url . $file;
	}

	//return full url of js
	protected function _js_url( $file ) {
		static $Url = null;

		if ( AC_UNMINIFIED ) {
			$file = str_replace( '.min.js', '.js', $file );
		}
		if ( null === $Url ) {
			$Url = plugins_url( 'js/', dirname( __FILE__ ) );
		}

		return $Url . $file;
	}
    //returns path 
	protected function _view_path( $file ) {
		static $Path = null;

		if ( null === $Path ) {
			$basedir = dirname( dirname( __FILE__ ) ) . '/';
			$Path = $basedir . 'view/';
		}

		return $Path . $file;
	}

	//executes actions
	protected function add_action( $tag, $function, $priority = 10 ) {
		$hooked = $this->_have( '_hooked_action-' . $tag );

		if ( did_action( $tag ) ) {
			$this->$function();
		} else {
			$this->_add( '_hooked_action-' . $tag, true );
			add_action( $tag, array( $this, $function ), $priority );
		}
	}
}

class Admin_Core extends Admin {
	public $array = null;
	public $debug = null;
	public $html = null;
	public $net = null;
	public $session = null;
	public $updates = null;
	public $ui = null;
	public function __construct() {
		parent::__construct();

		self::$core = $this;

		// A List of all components.
		$components = array(
			'array',
			'debug',
			'html',
			'net',
			'session',
			'updates',
			'ui',
		);

		// Create instances of each component.
		foreach ( $components as $component ) {
			if ( ! property_exists( $this, $component ) ) { continue; }

			$class_name = 'Admin_' . ucfirst( $component );
			$this->$component = new $class_name();
		}
	}
	public function is_true( $value ) {
		if ( false === $value || null === $value || '' === $value ) {
			return false;
		} elseif ( true === $value ) {
			return true;
		} elseif ( is_numeric( $value ) ) {
			$value = intval( $value );
			return $value != 0;
		} elseif ( is_string( $value ) ) {
			$value = strtolower( trim( $value ) );
			return in_array(
				$value,
				array( 'true', 'yes', 'on', '1' )
			);
		}
		return false;
	}
	public function is_false( $value ) {
		return ! $this->is_true( $value );
	}

	
	 //Converts a number from any base to another base
	 
	public function convert( $number, $base_from = '0123456789', $base_to = '0123456789ABCDEF' ) {
		if ( $base_from == $base_to ) {
			// No conversion needed.
			return $number;
		}

		$retval = '';
		$number_len = strlen( $number );

		if ( '0123456789' == $base_to ) {
			// Convert a value to normal decimal base.

			$arr_base_from = str_split( $base_from, 1 );
			$arr_number = str_split( $number, 1 );
			$base_from_len = strlen( $base_from );
			$retval = 0;
			for ( $i = 1; $i <= $number_len; $i += 1 ) {
				$retval = bcadd(
					$retval,
					bcmul(
						array_search( $arr_number[$i - 1], $arr_base_from ),
						bcpow( $base_from_len, $number_len - $i )
					)
				);
			}
		} else {
			// Convert a value to a NON-decimal base.

			if ( '0123456789' != $base_from ) {
				// Base value is non-decimal, convert it to decimal first.
				$base10 = $this->convert( $number, $base_from, '0123456789' );
			} else {
				// Base value is decimal.
				$base10 = $number;
			}

			$arr_base_to = str_split( $base_to, 1 );
			$base_to_len = strlen( $base_to );
			if ( $base10 < strlen( $base_to ) ) {
				$retval = $arr_base_to[$base10];
			} else {
				while ( 0 != $base10 ) {
					$retval = $arr_base_to[bcmod( $base10, $base_to_len )] . $retval;
					$base10 = bcdiv( $base10, $base_to_len, 0 );
				}
			}
		}

		return $retval;
	}

}
