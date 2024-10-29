<?php
//Debug component
class Admin_Debug extends Admin  {


	protected $enabled = null;
	protected $stacktrace = true;
	protected $plain_text = false;

	
	 //Constructor.
	 
	public function __construct() {
		remove_all_actions( 'AC_DEBUG_log' );
		remove_all_actions( 'AC_DEBUG_log_trace' );
		remove_all_actions( 'AC_DEBUG_dump' );
		remove_all_actions( 'AC_DEBUG_trace' );

		add_action(
			'AC_DEBUG_log',
			array( $this, 'log' ),
			10, 99
		);

		add_action(
			'AC_DEBUG_log_trace',
			array( $this, 'log_trace' )
		);

		add_action(
			'AC_DEBUG_dump',
			array( $this, 'dump' ),
			10, 99
		);

		add_action(
			'AC_DEBUG_trace',
			array( $this, 'trace' )
		);
	}

	//reset debug outputs 
	public function reset() {
		$this->enabled = null;
		$this->stacktrace = true;
	}

   //force enable debug
	public function enable() {
		$this->enabled = true;
	}

	//force disable debug
	public function disable() {
		$this->enabled = false;
	}

	//returns debug status
	public function is_enabled() {
		$enabled = $this->enabled;
		$is_ajax = false;
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) { $is_ajax = true; }
		if ( defined( 'DOING_CRON' ) && DOING_CRON ) { $is_ajax = true; }

		if ( null === $enabled ) {
			if ( $is_ajax ) {
				$enabled = AC_AJAX_DEBUG;
			} else {
				$enabled = AC_DEBUG;
			}
		}

		return $enabled;
	}

	public function stacktrace_on() {
		$this->stacktrace = true;
	}
	public function stacktrace_off() {
		$this->stacktrace = false;
	}
	public function format_text() {
		$this->plain_text = true;
	}
	public function format_html() {
		$this->plain_text = false;
	}
	public function is_plain_text() {
		$plain_text = $this->plain_text;

		$is_ajax = false;
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) { $is_ajax = true; }
		if ( defined( 'DOING_CRON' ) && DOING_CRON ) { $is_ajax = true; }
		if ( $is_ajax ) { $plain_text = true; }

		return $plain_text;
	}

	//debug information
	public function log( $first_arg ) {
		if ( $this->is_enabled() ) {
			$plain_text = $this->plain_text;
			$this->format_text();
			$log_file = WP_CONTENT_DIR . '/ad_action.log';
			$time = date( "Y-m-d\tH:i:s\t" );

			foreach ( func_get_args() as $param ) {
				if ( is_scalar( $param ) ) {
					$dump = $param;
				} else {
					$dump = var_export( $param, true );
				}
				error_log( $time . $dump . "\n", 3, $log_file );
			}

			$this->plain_text = $plain_text;
		}
	}

	
	 //Write stacktrace information to error log file.
	
	public function log_trace() {
		if ( $this->is_enabled() ) {
			$plain_text = $this->plain_text;
			$this->format_text();
			$log_file = WP_CONTENT_DIR . '/ad_action.log';

			// Display the backtrace.
			$trace = $this->trace( false );
			error_log( $trace, 3, $log_file );

			$this->plain_text = $plain_text;
		}
	}
//adds log messages
	public function header( $message ) {
		static $Number = 0;
		if ( ! $this->is_enabled() ) { return; }

		$Number += 1;
		if ( headers_sent() ) {
			// HTTP Headers already sent, so add the response as HTML comment.
			$message = str_replace( '-->', '--/>', $message );
			printf( "<!-- Debug-Note[%s]: %s -->\n", $Number, $message );
		} else {
			// No output was sent yet so add the message to the HTTP headers.
			$message = str_replace( array( "\n", "\r" ), ' ', $message );
			header( "X-Debug-Note[$Number]: $message", false );
		}
	}

	

}