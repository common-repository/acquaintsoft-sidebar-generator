<?php
//Html Helper functions with some contants

class Admin_Html extends Admin  {

	/* Constants for default HTML input elements. */
	const INPUT_TYPE_HIDDEN = 'hidden';
	const INPUT_TYPE_TEXT_AREA = 'textarea';
	const INPUT_TYPE_SELECT = 'select';
	const INPUT_TYPE_RADIO = 'radio';
	const INPUT_TYPE_SUBMIT = 'submit';
	const INPUT_TYPE_BUTTON = 'button';
	const INPUT_TYPE_CHECKBOX = 'checkbox';
	const INPUT_TYPE_IMAGE = 'image';
	// Different input types
	const INPUT_TYPE_TEXT = 'text';
	const INPUT_TYPE_PASSWORD = 'password';
	const INPUT_TYPE_NUMBER = 'number';
	const INPUT_TYPE_EMAIL = 'email';
	const INPUT_TYPE_URL = 'url';
	const INPUT_TYPE_TIME = 'time';
	const INPUT_TYPE_SEARCH = 'search';
	const INPUT_TYPE_FILE = 'file';

	/* Constants for advanced HTML input elements. */
	const INPUT_TYPE_WP_EDITOR = 'wp_editor';
	const INPUT_TYPE_DATEPICKER = 'datepicker';
	const INPUT_TYPE_RADIO_SLIDER = 'radio_slider';
	const INPUT_TYPE_TAG_SELECT = 'tag_select';
	const INPUT_TYPE_WP_PAGES = 'wp_pages';

	/* Constants for default HTML elements. */
	const TYPE_HTML_LINK = 'html_link';
	const TYPE_HTML_SEPARATOR = 'html_separator';
	const TYPE_HTML_TEXT = 'html_text';
	const TYPE_HTML_TABLE = 'html_table';


	public function __construct() {
		parent::__construct();
	}

	public function pointer( $args, $html_el = '', $title = false, $body = '' ) {
		if ( ! is_admin() ) {
			return;
		}

		if ( is_array( $args ) ) {
			if ( isset( $args['target'] ) && ! isset( $args['html_el'] ) ) {
				$args['html_el'] = $args['target'];
			}
			if ( isset( $args['id'] ) && ! isset( $args['pointer_id'] ) ) {
				$args['pointer_id'] = $args['id'];
			}
			if ( isset( $args['modal'] ) && ! isset( $args['blur'] ) ) {
				$args['blur'] = $args['modal'];
			}
			if ( ! isset( $args['once'] ) ) {
				$args['once'] = true;
			}

			self::$core->array->equip( $args, 'pointer_id', 'html_el', 'title', 'body', 'once', 'modal', 'blur' );

			extract( $args );
		} else {
			$pointer_id = $args;
			$once = true;
			$modal = true;
			$blur = true;
		}

		$once = self::$core->is_true( $once );
		$modal = self::$core->is_true( $modal );
		$blur = self::$core->is_true( $blur );

		$this->_add( 'init_pointer', compact( 'pointer_id', 'html_el', 'title', 'body', 'once', 'modal', 'blur' ) );
		$this->add_action( 'init', '_init_pointer' );

		return $this;
	}

	//plugin loaded
	public function _init_pointer() {
		$items = $this->_get( 'init_pointer' );
		foreach ( $items as $item ) {
			extract( $item ); // pointer_id, html_el, title, body, once, modal, blur
			$show = true;

			if ( $once ) {
				// Find out which pointer IDs this user has already seen.
				$seen = (string) get_user_meta(
					get_current_user_id(),
					'dismissed_wp_pointers',
					true
				);
				$seen_list = explode( ',', $seen );
				$show = ! in_array( $pointer_id, $seen_list );
			} else {
				$show = true;
			}

			// Include all scripts and code to display the pointer!
			if ( $show ) {
				
				$this->add_action( 'admin_enqueue_scripts', '_enqueue_pointer' );

				$this->_add( 'pointer', $item );
			}
		}
	}
	public function _enqueue_pointer() {
		// Load the JS/CSS for WP Pointers
		wp_enqueue_script( 'wp-pointer' );
		wp_enqueue_style( 'wp-pointer' );
	}


// -----POPUP-------//

	public function popup( $args = array() ) {
		// Determine which hook should print the data.
		$hook = ( is_admin() ? 'admin_footer' : 'wp_footer' );

		self::$core->array->equip( $args, 'title', 'body', 'screen', 'modal', 'width', 'height', 'class' );

		// Don't add empty popups
		if ( empty( $args['title'] ) && empty( $args['body'] ) ) {
			return;
		}
		if ( ! isset( $args['close'] ) ) {
			$args['close'] = true;
		}
		if ( ! isset( $args['sticky'] ) ) {
			$args['sticky'] = false;
		}

		$args['width'] = absint( $args['width'] );
		$args['height'] = absint( $args['height'] );

		if ( $args['width'] < 20 ) {
			$args['width'] = -1;
		}
		if ( $args['height'] < 20 ) {
			$args['height'] = -1;
		}

		$args['modal'] = $args['modal'] ? 'true' : 'false';
		$args['persist'] = $args['sticky'] ? 'false' : 'true';
		$args['close'] = $args['close'] ? 'true' : 'false';

		self::_add( 'popup', $args );
		$this->add_action( $hook, '_popup_callback' );
		self::$core->ui->add( 'core' );

		return $this;
	}

	public function _popup_callback() {
		$items = self::_get( 'popup' );
		self::_clear( 'popup' );
		$screen_info = get_current_screen();
		$screen_id = $screen_info->id;

		foreach ( $items as $item ) {
			extract( $item ); // title, body, modal, close, modal, persist, width, height, class

			if ( empty( $title ) ) {
				$close = false;
			}

			if ( empty( $screen ) || $screen_id == $screen ) {
				$body = '<div>' . $body . '</div>';
				echo '<script>jQuery(function(){acquaintUi.popup()';
				printf( '.title( %1$s, %2$s )', json_encode( $title ), $close );
				printf( '.modal( %1$s, %2$s )', $modal, $persist );
				printf( '.size( %1$s, %2$s )', json_encode( $width ), json_encode( $height ) );
				printf( '.set_class( %1$s )', json_encode( $class ) );
				printf( '.content( %1$s )', json_encode( $body ) );
				echo '.show();})</script>';
			}
		}
	}

}