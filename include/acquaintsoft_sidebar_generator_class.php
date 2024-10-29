<?php

class ACS_Class {
	static protected $sidebar_prefix = 'acq-';
	static protected $cap_required = 'edit_theme_options';
	static protected $accessibility_mode = false;

	
	static public function instance() {
		static $Inst = null;

		if ( ! did_action( 'set_current_user' ) ) {
			add_action( 'set_current_user', array( __CLASS__, 'instance' ) );
			return null;
		}

		if ( null === $Inst ) {
			$Inst = new ACS_Class();
		}

		return $Inst;
	}
	private function __construct() {
		$plugin_title = 'Sidebars Generator';
		
		ad_action()->html->pointer(
			                              // Internal Pointer-ID
			'#menu-appearance',                       // Point at
			$plugin_title,
			sprintf(
				__(
					'Now you can create and edit custom sidebars in your ' .
					'<a href="%1$s">Widgets screen</a>!', 'custom-sidebars'
				),
				admin_url( 'widgets.php' )
			)                                         // Body
		);

		// Find out if the page is loaded in accessibility mode.
		$flag = isset( $_GET['widgets-access'] ) ? $_GET['widgets-access'] : get_user_setting( 'widgets_access' );
		self::$accessibility_mode = ( 'on' == $flag );

		// We don't support accessibility mode. Display a note to the user.
		if ( true === self::$accessibility_mode ) {
			ad_action()->ui->admin_message(
				sprintf(
					__(
						'<strong>Accessibility mode is not supported by the
						%1$s plugin.</strong><br /><a href="%2$s">Click here</a>
						to disable accessibility mode and use the %1$s plugin!',
						'custom-sidebars'
					),
					$plugin_title,
					admin_url( 'widgets.php?widgets-access=off' )
				),
				'err',
				'widgets'
			);
		} else {
			// Load javascripts/css files
			ad_action()->ui->add( 'core', 'widgets.php' );
			ad_action()->ui->add( 'select', 'widgets.php' );
			ad_action()->ui->add( AQU_JS_URL . 'acquaintsoft_sidebar_generator-public.js', 'widgets.php' );
			ad_action()->ui->add( AQU_CSS_URL . 'acquaintsoft_sidebar_generator-public.css', 'widgets.php' );
			ad_action()->ui->add( AQU_CSS_URL . 'custom.css', 'widgets.php' );
			ad_action()->ui->add( AQU_CSS_URL . 'acquaintsoft_sidebar_generator-public.css', 'edit.php' );

			// AJAX actions
			add_action( 'wp_ajax_acq-ajax', array( $this, 'ajax_handler' ) );

			// Extensions use this hook to initialize themselfs.
			do_action( 'acq_init' );
			add_action(
				'in_widget_form',
				array( $this, 'in_widget_form' ),
				10, 1
			);
		}
		add_filter( 'plugin_action_links_' . plugin_basename( AQU_PLUGIN ), array( $this, 'add_action_links' ), 10, 4 );
	}

	static public function get_array( $val1, $val2 = array() ) {
		if ( is_array( $val1 ) ) {
			return $val1;
		} elseif ( is_array( $val2 ) ) {
			return $val2;
		} else {
			return array();
		}
	}
	static public function get_options( $key = null ) {
		static $Options = null;
		$need_update = false;

		if ( null === $Options ) {
			$Options = get_option( 'acq_modifiable', array() );
			if ( ! is_array( $Options ) ) {
				$Options = array();
			}

			// List of modifiable sidebars.
			if ( ! isset( $Options['modifiable'] ) || ! is_array( $Options['modifiable'] ) ) {
				// By default we make ALL theme sidebars replaceable:
				$all = self::get_sidebars( 'theme' );
				$Options['modifiable'] = array_keys( $all );
				$need_update = true;
			}

			$keys = array(
				'authors',
				'blog',
				'category_archive',
				'category_pages',
				'category_posts',
				'category_single',
				'date',
				'defaults',
				'post_type_archive',
				'post_type_pages',
				'post_type_single',
				'search',
				'tags',
			);

			foreach ( $keys as $k ) {
				if ( isset( $Options[ $k ] ) ) {
					continue;
				}
				$Options[ $k ] = null;
			}

			// Single/Archive pages - new names
			$Options['post_type_single'] = self::get_array(
				$Options['post_type_single'], // new name
				$Options['defaults']          // old name
			);
			$Options['post_type_archive'] = self::get_array(
				$Options['post_type_archive'], // new name
				$Options['post_type_pages']    // old name
			);
			$Options['category_single'] = self::get_array(
				$Options['category_single'], // new name
				$Options['category_posts']   // old name
			);
			$Options['category_archive'] = self::get_array(
				$Options['category_archive'], // new name
				$Options['category_pages']    // old name
			);

			// Remove old item names from the array.
			if ( isset( $Options['defaults'] ) ) {
				unset( $Options['defaults'] );
				$need_update = true;
			}
			if ( isset( $Options['post_type_pages'] ) ) {
				unset( $Options['post_type_pages'] );
				$need_update = true;
			}
			if ( isset( $Options['category_posts'] ) ) {
				unset( $Options['category_posts'] );
				$need_update = true;
			}
			if ( isset( $Options['category_pages'] ) ) {
				unset( $Options['category_pages'] );
				$need_update = true;
			}

			// Special archive pages
			$keys = array( 'blog', 'tags', 'authors', 'search', 'date' );
			foreach ( $keys as $temporary_key ) {
				if ( isset( $Options[ $temporary_key ] ) ) {
					$Options[ $temporary_key ] = self::get_array( $Options[ $temporary_key ] );
				} else {
					$Options[ $temporary_key ] = array();
				}
			}

			$Options = self::validate_options( $Options );
			if ( $need_update ) {
				self::set_options( $Options );
			}
		}
		if ( ! empty( $key ) ) {
			return isset( $Options[ $key ] )? $Options[ $key ] : null;
		} else {
			return $Options;
		}
	}

	static public function set_options( $value ) {
		// Permission check.
		if ( ! current_user_can( self::$cap_required ) ) {
			return;
		}

		update_option( 'acq_modifiable', $value );
	}

	static public function validate_options( $data = null ) {
		$data = (is_object( $data ) ? (array) $data : $data );
		if ( ! is_array( $data ) ) {
			return array();
		}
		$valid = array_keys( self::get_sidebars( 'theme' ) );
		$current = array();
		if ( isset( $data['modifiable'] ) ) {
			$current = self::get_array( $data['modifiable'] );
		}
		// Get all the sidebars that are modifiable AND exist.
		$modifiable = array_intersect( $valid, $current );
		$data['modifiable'] = $modifiable;
		return $data;
	}

	static public function get_custom_sidebars() {
		$sidebars = get_option( 'acq_sidebars', array() );
		if ( ! is_array( $sidebars ) ) {
			$sidebars = array();
		}

		// Remove invalid items.
		foreach ( $sidebars as $key => $data ) {
			if ( ! is_array( $data ) ) {
				unset( $sidebars[ $key ] );
			}
		}

		return $sidebars;
	}
	static public function set_custom_sidebars( $value ) {
		// Permission check.
		if ( ! current_user_can( self::$cap_required ) ) {
			return;
		}

		update_option( 'acq_sidebars', $value );
	}

	static public function get_sidebar_widgets() {
		return get_option( 'sidebars_widgets', array() );
	}
	static public function refresh_sidebar_widgets() {
		// Contains an array of all sidebars and widgets inside each sidebar.
		$widgetized_sidebars = self::get_sidebar_widgets();

		$acq_sidebars = self::get_custom_sidebars();
		$delete_widgetized_sidebars = array();

		foreach ( $widgetized_sidebars as $id => $bar ) {
			if ( substr( $id, 0, 3 ) == self::$sidebar_prefix ) {
				$found = false;
				foreach ( $acq_sidebars as $acqbar ) {
					if ( $acqbar['id'] == $id ) {
						$found = true;
					}
				}
				if ( ! $found ) {
					$delete_widgetized_sidebars[] = $id;
				}
			}
		}

		$all_ids = array_keys( $widgetized_sidebars );
		foreach ( $acq_sidebars as $acq ) {
			$sb_id = $acq['id'];
			if ( ! in_array( $sb_id, $all_ids ) ) {
				$widgetized_sidebars[ $sb_id ] = array();
			}
		}

		foreach ( $delete_widgetized_sidebars as $id ) {
			unset( $widgetized_sidebars[ $id ] );
		}

		update_option( 'sidebars_widgets', $widgetized_sidebars );
	}
	static public function get_post_meta( $post_id ) {
		$data = get_post_meta( $post_id, '_acq_replacements', true );
		if ( ! is_array( $data ) ) {
			$data = array();
		}
		return $data;
	}
	static public function set_post_meta( $post_id, $data ) {
		if ( ! empty( $data ) ) {
			update_post_meta( $post_id, '_acq_replacements', $data );
		} else {
			delete_post_meta( $post_id, '_acq_replacements' );
		}
	}
	static public function get_sidebars( $type = 'theme' ) {
		global $wp_registered_sidebars;
		$allsidebars = ACS_Class::sort_sidebars_by_name( $wp_registered_sidebars );
		$result = array();

		// Remove inactive sidebars.
		foreach ( $allsidebars as $sb_id => $sidebar ) {
			if ( false !== strpos( $sidebar['class'], 'inactive-sidebar' ) ) {
				unset( $allsidebars[ $sb_id ] );
			}
		}

		ksort( $allsidebars );
		if ( 'all' == $type ) {
			$result = $allsidebars;
		} elseif ( 'cust' == $type ) {
			foreach ( $allsidebars as $key => $sb ) {
				// Only keep custom sidebars in the results.
				if ( substr( $key, 0, 3 ) == self::$sidebar_prefix ) {
					$result[ $key ] = $sb;
				}
			}
		} elseif ( 'theme' == $type ) {
			foreach ( $allsidebars as $key => $sb ) {
				// Remove custom sidebars from results.
				if ( substr( $key, 0, 3 ) != self::$sidebar_prefix ) {
					$result[ $key ] = $sb;
				}
			}
		}

		return $result;
	}
	static public function get_sidebar( $id, $type = 'all' ) {
		if ( empty( $id ) ) { return false; }

		// Get all sidebars
		$sidebars = self::get_sidebars( $type );

		if ( isset( $sidebars[ $id ] ) ) {
			return $sidebars[ $id ];
		} else {
			return false;
		}
	}

	static public function get_replacements( $postid ) {
		$replacements = self::get_post_meta( $postid );
		if ( ! is_array( $replacements ) ) {
			$replacements = array();
		} else {
			$replacements = $replacements;
		}
		return $replacements;
	}

	static public function supported_post_type( $posttype ) {
		$Ignored_types = null;
		$Response = array();

		if ( null === $Ignored_types ) {
			$Ignored_types = get_post_types(
				array( 'public' => false ),
				'names'
			);
			$Ignored_types[] = 'attachment';
		}

		if ( is_object( $posttype ) ) {
			$posttype = $posttype->name;
		}

		if ( ! isset( $Response[ $posttype ] ) ) {
			$response = ! in_array( $posttype, $Ignored_types );
			$response = apply_filters( 'acq_support_posttype', $response, $posttype );
			$Response[ $posttype ] = $response;
		}

		return $Response[ $posttype ];
	}

	static public function get_post_types( $type = 'names' ) {
		$Valid = array();

		if ( 'objects' != $type ) {
			$type = 'names';
		}

		if ( ! isset( $Valid[ $type ] ) ) {
			$all = get_post_types( array(), $type );
			$Valid[ $type ] = array();

			foreach ( $all as $post_type ) {
				if ( self::supported_post_type( $post_type ) ) {
					$Valid[ $type ][] = $post_type;
				}
			}
		}

		return $Valid[ $type ];
	}

	static public function get_all_categories() {
		$args = array(
			'hide_empty' => 0,
			'taxonomy' => 'category',
		);

		return get_categories( $args );
	}
	static public function get_sorted_categories( $post_id = null ) {
		static $Sorted = array();

		// Return categories of current post when no post_id is specified.
		$post_id = empty( $post_id ) ? get_the_ID() : $post_id;

		if ( ! isset( $Sorted[ $post_id ] ) ) {
			$Sorted[ $post_id ] = get_the_category( $post_id );
			usort( $Sorted[ $post_id ], array( __CLASS__, 'cmp_cat_level' ) );
		}
		return $Sorted[ $post_id ];
	}
	static public function cmp_cat_level( $cat1, $cat2 ) {
		$l1 = self::get_category_level( $cat1->cat_ID );
		$l2 = self::get_category_level( $cat2->cat_ID );
		if ( $l1 == $l2 ) {
			return strcasecmp( $cat1->name, $cat1->name );
		} else {
			return $l1 < $l2 ? 1 : -1;
		}
	}
	static public function get_category_level( $catid ) {
		if ( ! $catid ) {
			return 0;
		}

		$cat = get_category( $catid );
		return 1 + self::get_category_level( $cat->category_parent );
	}

	static protected function json_response( $obj ) {
		// Flush any output that was made prior to this function call
		while ( 0 < ob_get_level() ) { ob_end_clean(); }

		header( 'Content-Type: application/json' );
		echo json_encode( (object) $obj );
		die();
	}
	static protected function plain_response( $data ) {
		// Flush any output that was made prior to this function call
		while ( 0 < ob_get_level() ) { ob_end_clean(); }

		header( 'Content-Type: text/plain' );
		echo '' . $data;
		die();
	}
	static protected function req_err( $req, $message ) {
		$req->status = 'ERR';
		$req->message = $message;
		return $req;
	}

	public function ajax_handler() {
		// Permission check.
		if ( ! current_user_can( self::$cap_required ) ) {
			return;
		}

		// Try to disable debug output for ajax handlers of this plugin.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			defined( 'WP_DEBUG_DISPLAY' ) || define( 'WP_DEBUG_DISPLAY', false );
			defined( 'WP_DEBUG_LOG' ) || define( 'WP_DEBUG_LOG', true );
		}
		// Catch any unexpected output via output buffering.
		ob_start();

		$action = isset( $_POST['do'] )? $_POST['do']:null;
		$get_action = isset( $_GET['do'] )? $_GET['do']:null;

		do_action( 'acq_ajax_request', $action );

		do_action( 'acq_ajax_request_get', $get_action );
	}
	public static function sort_sidebars_cmp_function( $a, $b ) {
		if ( ! isset( $a['name'] ) || ! isset( $b['name'] ) ) {
			return 0;
		}
		if ( function_exists( 'mb_strtolower' ) ) {
			$a_name = mb_strtolower( $a['name'] );
			$b_name = mb_strtolower( $b['name'] );
		} else {
			$a_name = strtolower( $a['name'] );
			$b_name = strtolower( $b['name'] );
		}
		if ( $a_name == $b_name ) {
			return 0;
		}
		return ($a_name < $b_name ) ? -1 : 1;
	}
	public static function sort_sidebars_by_name( $available ) {
		if ( empty( $available ) ) {
			return $available;
		}
		foreach ( $available as $key => $data ) {
			$available[ $key ]['acq-key'] = $key;
		}
		usort( $available, array( __CLASS__, 'sort_sidebars_cmp_function' ) );
		$sorted = array();
		foreach ( $available as $data ) {
			$sorted[ $data['acq-key'] ] = $data;
		}
		return $sorted;
	}
	public function add_action_links( $actions, $plugin_file, $plugin_data, $context ) {
		if ( current_user_can( 'edit_theme_options' ) ) {
			$actions['widgets'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( admin_url( 'widgets.php' ) ),
				__( 'Widgets', 'custom-sidebars' )
			);
		}
		return $actions;
	}
        public function in_widget_form(){}
};

//class for widget

add_action( 'acq_init', array( 'SidebarsWidgets', 'instance' ) );

class SidebarsWidgets extends ACS_Class {

	public static function instance() {
		static $Inst = null;

		if ( null === $Inst ) {
			$Inst = new SidebarsWidgets();
		}

		return $Inst;
	}
	private function __construct() {
		if ( is_admin() ) {
			add_action(
				'widgets_admin_page',
				array( $this, 'widget_sidebar_content' )
			);

			add_action(
				'admin_head-widgets.php',
				array( $this, 'init_admin_head' )
			);
		}
	}

	public function widget_sidebar_content() {
		include CSB_VIEWS_DIR . 'widgets.php';
	}

	public function init_admin_head( $classes ) {
		add_filter(
			'admin_body_class',
			array( $this, 'admin_body_class' )
		);
	}
	public function admin_body_class( $classes ) {
		$classes .= ' no-auto-init ';
		return $classes;
	}

};

//class for replace

add_action( 'acq_init', array( 'SidebarsReplacer', 'instance' ) );
class SidebarsReplacer extends ACS_Class {

	private $original_post_id = 0;

	public static function instance() {
		static $Inst = null;

		if ( null === $Inst ) {
			$Inst = new SidebarsReplacer();
		}

		return $Inst;
	}
	private function __construct() {
		add_action(
			'widgets_init',
			array( $this, 'register_custom_sidebars' )
		);

		

		if ( ! is_admin() ) {
			// Frontend hooks.
			add_action(
				'wp_head',
				array( $this, 'replace_sidebars' )
			);

			add_action(
				'wp',
				array( $this, 'store_original_post_id' )
			);
		}
	}

	public function register_custom_sidebars() {
		$sb = self::get_custom_sidebars();
		$sb = ACS_Class::sort_sidebars_by_name( $sb );
		foreach ( $sb as $sidebar ) {
		
			$sidebar = apply_filters( 'acq_sidebar_params', $sidebar );
			register_sidebar( $sidebar );
		}
	}
	public function store_original_post_id() {
		global $post;

		if ( isset( $post->ID ) ) {
			$this->original_post_id = $post->ID;
		}
	}
	public function replace_sidebars() {
		global $_wp_sidebars_widgets,
			$wp_registered_sidebars,
			$wp_registered_widgets;

		$expl = SidebarsExplain::do_explain();

		$expl && do_action( 'acq_explain', '<h4>Replace sidebars</h4>', true );

		do_action( 'acq_before_replace_sidebars' );

		
		$original_widgets = $_wp_sidebars_widgets;

		$defaults = self::get_options();

		
		do_action( 'acq_predetermine_replacements', $defaults );

		// Legacy handler with camelCase
		do_action( 'acq_predetermineReplacements', $defaults );

		$replacements = $this->determine_replacements( $defaults );

		foreach ( $replacements as $sb_id => $replace_info ) {
			if ( ! is_array( $replace_info ) || count( $replace_info ) < 3 ) {
				$expl && do_action( 'acq_explain', 'Replacement for "' . $sb_id . '": -none-' );
				continue;
			}

			// Fix rare message "illegal offset type in isset or empty"
			$replacement = (string) @$replace_info[0];
			$replacement_type = (string) @$replace_info[1];
			$extra_index = (string) @$replace_info[2];

			$check = $this->is_valid_replacement( $sb_id, $replacement, $replacement_type, $extra_index );

			if ( $check ) {
				$expl && do_action( 'acq_explain', 'Replacement for "' . $sb_id . '": ' . $replacement );

				if ( sizeof( $original_widgets[ $replacement ] ) == 0 ) {
					// No widgets on custom sidebar, show nothing.
					$wp_registered_widgets['acqemptywidget'] = $this->get_empty_widget();
					$_wp_sidebars_widgets[ $sb_id ] = array( 'acqemptywidget' );
				} else {
					$_wp_sidebars_widgets[ $sb_id ] = $original_widgets[ $replacement ];

				
					$sidebar_for_replacing = $wp_registered_sidebars[ $replacement ];
					if ( $this->has_wrapper_code( $sidebar_for_replacing ) ) {
						$sidebar_for_replacing = $this->clean_wrapper_code( $sidebar_for_replacing );
						$wp_registered_sidebars[ $sb_id ] = $sidebar_for_replacing;
					}
				}
				$wp_registered_sidebars[ $sb_id ]['class'] = $replacement;
			} else {
				// endif: is_valid_replacement
				$expl && do_action( 'acq_explain', 'Replacement for "' . $sb_id . '": -none-' );
			}
		} // endforeach
	}

	public function determine_replacements( $options ) {
		global $post, $sidebar_category;

		$sidebars = self::get_options( 'modifiable' );
		$replacements_todo = sizeof( $sidebars );
		$replacements = array();
		$expl = SidebarsExplain::do_explain();

		foreach ( $sidebars as $sb ) {
			$replacements[ $sb ] = false;
		}


		if ( is_single() ) {
			$post_type = get_post_type();
			$post_type = apply_filters( 'acq_replace_post_type', $post_type, 'single' );
			$expl && do_action( 'acq_explain', 'Type 1: Single ' . ucfirst( $post_type ) );

			if ( ! self::supported_post_type( $post_type ) ) {
				$expl && do_action( 'acq_explain', 'Invalid post type, use default sidebars.' );
				return $options;
			}
			$reps = self::get_post_meta( $this->original_post_id );
			foreach ( $sidebars as $sb_id ) {
				if ( is_array( $reps ) && ! empty( $reps[ $sb_id ] ) ) {
					$replacements[ $sb_id ] = array(
						$reps[ $sb_id ],
						'particular',
						-1,
					);
					$replacements_todo -= 1;
				}
			}
			if ( $post->post_parent != 0 && $replacements_todo > 0 ) {
				$reps = self::get_post_meta( $post->post_parent );
				foreach ( $sidebars as $sb_id ) {
					if ( $replacements[ $sb_id ] ) { continue; }
					if (
						is_array( $reps )
						&& ! empty( $reps[ $sb_id ] )
					) {
						$replacements[ $sb_id ] = array(
							$reps[ $sb_id ],
							'particular',
							-1,
						);
						$replacements_todo -= 1;
					}
				}
			}
			if ( $replacements_todo > 0 ) {
				$categories = self::get_sorted_categories();
				$ind = sizeof( $categories ) -1;
				while ( $replacements_todo > 0 && $ind >= 0 ) {
					$cat_id = $categories[ $ind ]->cat_ID;
					foreach ( $sidebars as $sb_id ) {
						if ( $replacements[ $sb_id ] ) { continue; }
						if ( ! empty( $options['category_single'][ $cat_id ][ $sb_id ] ) ) {
							$replacements[ $sb_id ] = array(
								$options['category_single'][ $cat_id ][ $sb_id ],
								'category_single',
								$sidebar_category,
							);
							$replacements_todo -= 1;
						}
					}
					$ind -= 1;
				}
			}
			if ( $replacements_todo > 0 ) {
				foreach ( $sidebars as $sb_id ) {
					if ( $replacements[ $sb_id ] ) { continue; }
					if (
						isset( $options['post_type_single'][ $post_type ] )
						&& ! empty( $options['post_type_single'][ $post_type ][ $sb_id ] )
					) {
						$replacements[ $sb_id ] = array(
							$options['post_type_single'][ $post_type ][ $sb_id ],
							'post_type_single',
							$post_type,
						);
						$replacements_todo -= 1;
					}
				}
			}
		} elseif ( is_category() ) {

			$expl && do_action( 'acq_explain', 'Type 2: Category Archive' );

			$category_object = get_queried_object();
			$current_category = $category_object->term_id;
			while ( 0 != $current_category && $replacements_todo > 0 ) {
				foreach ( $sidebars as $sb_id ) {
					if ( $replacements[ $sb_id ] ) { continue; }
					if ( ! empty( $options['category_archive'][ $current_category ][ $sb_id ] ) ) {
						$replacements[ $sb_id ] = array(
							$options['category_archive'][ $current_category ][ $sb_id ],
							'category_archive',
							$current_category,
						);
						$replacements_todo -= 1;
					}
				}
				$current_category = $category_object->category_parent;
				if ( 0 != $current_category ) {
					$category_object = get_category( $current_category );
				}
			}
		} elseif ( is_search() ) {
		
			$expl && do_action( 'acq_explain', 'Type 3: Search Results' );

			foreach ( $sidebars as $sb_id ) {
				if ( ! empty( $options['search'][ $sb_id ] ) ) {
					$replacements[ $sb_id ] = array(
						$options['search'][ $sb_id ],
						'search',
						-1,
					);
				}
			}
		} elseif ( ! is_category() && ! is_singular() && get_post_type() != 'post' ) {
			
			$post_type = get_post_type();
			$post_type = apply_filters( 'acq_replace_post_type', $post_type, 'archive' );
			$expl && do_action( 'acq_explain', 'Type 4: ' . ucfirst( $post_type ) . ' Archive' );

			if ( ! self::supported_post_type( $post_type ) ) {
				$expl && do_action( 'acq_explain', 'Invalid post type, use default sidebars.' );
				return $options;
			}

			foreach ( $sidebars as $sb_id ) {
				if (
					isset( $options['post_type_archive'][ $post_type ] )
					&& ! empty( $options['post_type_archive'][ $post_type ][ $sb_id ] )
				) {
					$replacements[ $sb_id ] = array(
						$options['post_type_archive'][ $post_type ][ $sb_id ],
						'post_type_archive',
						$post_type,
					);
					$replacements_todo -= 1;
				}
			}
		} elseif ( is_page() && ! is_front_page() ) {
			
			$post_type = get_post_type();
			$post_type = apply_filters( 'acq_replace_post_type', $post_type, 'page' );
			$expl && do_action( 'acq_explain', 'Type 5: ' . ucfirst( $post_type ) );

			if ( ! self::supported_post_type( $post_type ) ) {
				$expl && do_action( 'acq_explain', 'Invalid post type, use default sidebars.' );
				return $options;
			}

			$reps = self::get_post_meta( $this->original_post_id );
			foreach ( $sidebars as $sb_id ) {
				if ( is_array( $reps ) && ! empty( $reps[ $sb_id ] ) ) {
					$replacements[ $sb_id ] = array(
						$reps[ $sb_id ],
						'particular',
						-1,
					);
					$replacements_todo -= 1;
				}
			}
			if ( $post->post_parent != 0 && $replacements_todo > 0 ) {
				$reps = self::get_post_meta( $post->post_parent );
				foreach ( $sidebars as $sb_id ) {
					if ( $replacements[ $sb_id ] ) { continue; }
					if ( is_array( $reps )
						&& ! empty( $reps[ $sb_id ] )
					) {
						$replacements[ $sb_id ] = array(
							$reps[ $sb_id ],
							'particular',
							-1,
						);
						$replacements_todo -= 1;
					}
				}
			}
			if ( $replacements_todo > 0 ) {
				foreach ( $sidebars as $sb_id ) {
					if ( $replacements[ $sb_id ] ) { continue; }
					if ( isset( $options['post_type_single'][ $post_type ] )
						&& ! empty( $options['post_type_single'][ $post_type ][ $sb_id ] )
					) {
						$replacements[ $sb_id ] = array(
							$options['post_type_single'][ $post_type ][ $sb_id ],
							'post_type_single',
							$post_type,
						);
						$replacements_todo -= 1;
					}
				}
			}
		} elseif ( is_front_page() ) {

			$expl && do_action( 'acq_explain', 'Type 6: Front Page' );

			if ( ! is_home() ) {
				// A static front-page. Maybe we need the post-meta data...
				$reps_post = self::get_post_meta( $this->original_post_id );
				$reps_parent = self::get_post_meta( $post->post_parent );
			}

			foreach ( $sidebars as $sb_id ) {

				// First check if there is a 'Front Page' replacement.
				if ( ! empty( $options['blog'][ $sb_id ] ) ) {
					$replacements[ $sb_id ] = array(
						$options['blog'][ $sb_id ],
						'blog',
						-1,
					);
				} else if ( ! is_home() ) {
					
					if ( is_array( $reps_post ) && ! empty( $reps_post[ $sb_id ] ) ) {
						$replacements[ $sb_id ] = array(
							$reps_post[ $sb_id ],
							'particular',
							-1,
						);
						$replacements_todo -= 1;
					}

					if ( $post->post_parent != 0 && $replacements_todo > 0 ) {
						if ( $replacements[ $sb_id ] ) { continue; }
						if ( is_array( $reps_parent )
							&& ! empty( $reps_parent[ $sb_id ] )
						) {
							$replacements[ $sb_id ] = array(
								$reps_parent[ $sb_id ],
								'particular',
								-1,
							);
							$replacements_todo -= 1;
						}
					}
				}
			}
		} elseif ( is_home() ) {

			$expl && do_action( 'acq_explain', 'Type 7: Post Index' );

			foreach ( $sidebars as $sb_id ) {
				if ( ! empty( $options['post_type_archive']['post'][ $sb_id ] ) ) {
					$replacements[ $sb_id ] = array(
						$options['post_type_archive']['post'][ $sb_id ],
						'postindex',
						-1,
					);
				}
			}
		} elseif ( is_tag() ) {

			$expl && do_action( 'acq_explain', 'Type 8: Tag Archive' );

			foreach ( $sidebars as $sb_id ) {
				if ( ! empty( $options['tags'][ $sb_id ] ) ) {
					$replacements[ $sb_id ] = array(
						$options['tags'][ $sb_id ],
						'tags',
						-1,
					);
				}
			}
		} elseif ( is_author() ) {

			$author_object = get_queried_object();
			$current_author = $author_object->ID;
			$expl && do_action( 'acq_explain', 'Type 9: Author Archive (' . $current_author . ')' );

			if ( $replacements_todo > 0 ) {
				foreach ( $sidebars as $sb_id ) {
					if ( $replacements[ $sb_id ] ) { continue; }
					if ( ! empty( $options['authors'][ $sb_id ] ) ) {
						$replacements[ $sb_id ] = array(
							$options['authors'][ $sb_id ],
							'authors',
							-1,
						);
					}
				}
			}
		} elseif ( is_date() ) {
			$expl && do_action( 'acq_explain', 'Type 10: Date Archive' );

			foreach ( $sidebars as $sb_id ) {
				if ( ! empty( $options['date'][ $sb_id ] ) ) {
					$replacements[ $sb_id ] = array(
						$options['date'][ $sb_id ],
						'date',
						-1,
					);
				}
			}
		} elseif ( is_404() ) {
		
			$expl && do_action( 'acq_explain', 'Type 11: 404 not found' );

			foreach ( $sidebars as $sb_id ) {
				if ( ! empty( $options['404'][ $sb_id ] ) ) {
					$replacements[ $sb_id ] = array(
						$options['404'][ $sb_id ],
						'404',
						-1,
					);
				}
			}
		}
		$replacements = apply_filters( 'acq_replace_sidebars', $replacements );

		return $replacements;
	}
	public function is_valid_replacement( $sb_id, $replacement, $method, $extra_index ) {
		global $wp_registered_sidebars;
		$options = self::get_options();

		if ( isset( $wp_registered_sidebars[ $replacement ] ) ) {
			// Everything okay, we can use the replacement
			return true;
		}

		if ( 'particular' == $method ) {
			// Invalid replacement was found in post-meta data.
			$sidebars = self::get_post_meta( $this->original_post_id );
			if ( $sidebars && isset( $sidebars[ $sb_id ] ) ) {
				unset( $sidebars[ $sb_id ] );
				self::set_post_meta( $this->original_post_id, $sidebars );
			}
		} else {
			// Invalid replacement is defined in wordpress options table.
			if ( isset( $options[ $method ] ) ) {
				if (
					-1 != $extra_index &&
					isset( $options[ $method ][ $extra_index ] ) &&
					isset( $options[ $method ][ $extra_index ][ $sb_id ] )
				) {
					unset( $options[ $method ][ $extra_index ][ $sb_id ] );
					self::set_options( $options );
				}

				if (
					1 == $extra_index &&
					isset( $options[ $method ] ) &&
					isset( $options[ $method ][ $sb_id ] )
				) {
					unset( $options[ $method ][ $sb_id ] );
					self::set_options( $options );
				}
			}
		}

		return false;
	}
	public function get_empty_widget() {
		$widget = new SidebarsEmptyPlugin();
		return array(
			'name'        => 'CS Empty Widget',
			'id'          => 'csemptywidget',
			'callback'    => array( $widget, 'display_callback' ),
			'params'      => array( array( 'number' => 2 ) ),
			'classname'   => 'SidebarsEmptyPlugin',
			'description' => 'CS dummy widget',
		);
	}

	public function has_wrapper_code( $sidebar ) {
		return (
			strlen( trim( $sidebar['before_widget'] ) )
			|| strlen( trim( $sidebar['after_widget'] ) )
			|| strlen( trim( $sidebar['before_title'] ) )
			|| strlen( trim( $sidebar['after_title'] ) )
		);
	}
	public function clean_wrapper_code( $sidebar ) {
		$sidebar['before_widget'] = stripslashes( $sidebar['before_widget'] );
		$sidebar['after_widget'] = stripslashes( $sidebar['after_widget'] );
		$sidebar['before_title'] = stripslashes( $sidebar['before_title'] );
		$sidebar['after_title'] = stripslashes( $sidebar['after_title'] );
		return $sidebar;
	}

	
};

//class for explain


add_action( 'acq_init', array( 'SidebarsExplain', 'instance' ) );

class SidebarsExplain extends ACS_Class {

	private $infos = array();

	public static function instance() {
		static $Inst = null;

		if ( null === $Inst ) {
			$Inst = new SidebarsExplain();
		}

		return $Inst;
	}
	private function __construct() {
		if ( ! session_id() ) {
			session_start();
		}
		if ( isset( $_GET['acq-explain'] ) ) {
			self::set_explain( $_GET['acq-explain'] );
		}

		if ( is_admin() ) {
			add_action(
				'acq_widget_header',
				array( $this, 'widget_header' )
			);

			add_action(
				'acq_ajax_request',
				array( $this, 'handle_ajax' )
			);
		} else {
			if ( self::do_explain() ) {
				add_action(
					'acq_explain',
					array( $this, 'add_info' ),
					10, 2
				);

				add_action(
					'wp_footer',
					array( $this, 'show_infos' )
				);

				add_action(
					'dynamic_sidebar_before',
					array( $this, 'before_sidebar' ),
					0, 2
				);

				add_action(
					'dynamic_sidebar_after',
					array( $this, 'after_sidebar' ),
					0, 2
				);
			}
		}
	}

	public function widget_header() {

	}
	public function handle_ajax( $ajax_action ) {
		$handle_it = false;
		$req = (object) array(
			'status' => 'ERR',
		);

		switch ( $ajax_action ) {
			case 'explain':
				$handle_it = true;
				break;
		}

		if ( ! $handle_it ) {
			return false;
		}

		$state = @$_POST['state'];

		switch ( $ajax_action ) {
			case 'explain':
				self::set_explain( $state );
				$req->status = 'OK';
				$req->state = self::do_explain() ? 'on' : 'off';
				break;
		}

		self::json_response( $req );
	}

	public static function do_explain() {
		return
			isset( $_SESSION['acq-explain'] )
			&& is_string( $_SESSION['acq-explain'] )
			&& 'on' == $_SESSION['acq-explain'];
	}

	public static function set_explain( $state ) {
		if ( 'on' != $state ) {
			$state = 'off';
		}
		$_SESSION['acq-explain'] = $state;
	}
	public function add_info( $info, $new_item = false ) {
		if ( $new_item ) {
			$this->infos[] = $info;
		} else {
			$this->infos[ count( $this->infos ) - 1 ] .= '<br />' . $info;
		}
	}

	public function show_infos() {
		?>
		<div class="acq-infos" style="width:600px;margin:10px auto;padding:10px;color:#666;background:#FFF;">
			<style>
			.acq-infos > ul { list-style:none; padding: 0; margin: 0; }
			.acq-infos > ul > li { margin: 0; padding: 10px 0 10px 30px; border-bottom: 1px solid #eee; }
			.acq-infos h4 { color: #600; margin: 10px 0 0 -30px; }
			.acq-infos h5 { color: #006; margin: 10px 0 0 -15px; }
			</style>
			<h3>Sidebar Infos</h3>
			<a href="?acq-explain=off" style="float:right;color:#009">Turn off explanations</a>
			<ul>
				<?php foreach ( $this->infos as $info ) : ?>
					<li><?php echo $info; ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}
	static public function get_color() {
		$r = rand( 40, 140 );
		$g = rand( 40, 140 );
		$b = rand( 40, 140 );
		return '#' . dechex( $r ) . dechex( $g ) . dechex( $b );
	}

	public function before_sidebar( $index, $has_widgets ) {
		global $wp_registered_sidebars;
		$col = self::get_color();
		$w_col = self::get_color();

		$wp_registered_sidebars[ $index ]['before_widget'] =
			'<div style="border:2px solid ' . $w_col . ';margin:2px;width:auto;clear:both">' .
			'<div style="font-size:12px;padding:1px 4px 1px 6px;float:right;background-color:' . $w_col . ';color:#FFF">%1$s</div>' .
			@$wp_registered_sidebars[ $index ]['before_widget'];
		$wp_registered_sidebars[ $index ]['after_widget'] =
			@$wp_registered_sidebars[ $index ]['after_widget'] .
			'<div style="clear:both"> </div>' .
			'</div>';
		?>
		<div style="border:2px solid <?php echo esc_attr( $col ); ?>;position:relative;">
			<div style="font-size:12px;padding:1px 4px 1px 6px;float:right;background-color:<?php echo esc_attr( $col ); ?>;margin-bottom:2px;color:#FFF"><?php echo esc_html( $index ); ?></div>
		<?php
	}
	public function after_sidebar( $index, $has_widgets ) {
		?>
		<div style="clear:both"> </div>
		</div>
		<?php
	}
};

//class for sidebar popup

add_action( 'acq_init', array( 'SidebarsEditor', 'instance' ) );

class SidebarsEditor extends ACS_Class {

	public static function instance() {
		static $Inst = null;

		if ( null === $Inst ) {
			$Inst = new SidebarsEditor();
		}

		return $Inst;
	}
	private function __construct() {
		if ( is_admin() ) {
			// Add the sidebar metabox to posts.
			add_action(
				'add_meta_boxes',
				array( $this, 'add_meta_box' )
			);

			// Save the options from the sidebars-metabox.
			add_action(
				'save_post',
				array( $this, 'store_replacements' )
			);

			// Handle ajax requests.
			add_action(
				'acq_ajax_request',
				array( $this, 'handle_ajax' )
			);

			
		}
	}
	public function handle_ajax( $action ) {
		$req = (object) array(
			'status' => 'ERR',
		);
		$is_json = true;
		$handle_it = false;
		$view_file = '';
		$sb_id = '';

		if ( isset( $_POST['sb'] ) ) {
			$sb_id = $_POST['sb'];
		}

		switch ( $action ) {
			case 'get':
			case 'save':
			case 'delete':
			case 'get-location':
			case 'set-location':
			case 'replaceable':
				$handle_it = true;
				$req->status = 'OK';
				$req->action = $action;
				$req->id = $sb_id;
				break;
		}

		// The ajax request was not meant for us...
		if ( ! $handle_it ) {
			return false;
		}

		$sb_data = self::get_sidebar( $sb_id );

		if ( ! current_user_can( self::$cap_required ) ) {
			$req = self::req_err(
				$req,
				__( 'You do not have permission for this', 'custom-sidebars' )
			);
		} else {
			switch ( $action ) {
				// Return details for the specified sidebar.
				case 'get':
					$req->sidebar = $sb_data;
					break;

				// Save or insert the specified sidebar.
				case 'save':
					$req = $this->save_item( $req, $_POST );
					break;

				// Delete the specified sidebar.
				case 'delete':
					$req->sidebar = $sb_data;
					$req = $this->delete_item( $req );
					break;

				// Get the location data.
				case 'get-location':
					$req->sidebar = $sb_data;
					$req = $this->get_location_data( $req );
					break;

				// Update the location data.
				case 'set-location':
					$req->sidebar = $sb_data;
					$req = $this->set_location_data( $req );
					break;

				// Toggle theme sidebar replaceable-flag.
				case 'replaceable':
					$req = $this->set_replaceable( $req );
					break;
			}
		}

		// Make the ajax response either as JSON or plain text.
		if ( $is_json ) {
			self::json_response( $req );
		} else {
			ob_start();
			include CSB_VIEWS_DIR . $view_file;
			$resp = ob_get_clean();

			self::plain_response( $resp );
		}
	}
	private function save_item( $req, $data ) {
		$sidebars = self::get_custom_sidebars();
		$sb_id = $req->id;
		$sb_desc = stripslashes( trim( @$_POST['description'] ) );

		if ( function_exists( 'mb_substr' ) ) {
			$sb_name = mb_substr( stripslashes( trim( @$data['name'] ) ), 0, 40 );
		} else {
			$sb_name = substr( stripslashes( trim( @$data['name'] ) ), 0, 40 );
		}

		if ( empty( $sb_name ) ) {
			return self::req_err(
				$req,
				__( 'Sidebar-name cannot be empty', 'custom-sidebars' )
			);
		}

		if ( empty( $sb_id ) ) {
			// Create a new sidebar.
			$action = 'insert';
			$num = count( $sidebars );
			do {
				$num += 1;
				$sb_id = self::$sidebar_prefix . $num;
			} while ( self::get_sidebar( $sb_id, 'all' ) );

			$sidebar = array(
				'id' => $sb_id,
			);
		} else {
			// Update existing sidebar
			$action = 'update';
			$sidebar = self::get_sidebar( $sb_id, 'all' );

			if ( ! $sidebar ) {
				return self::req_err(
					$req,
					__( 'The sidebar does not exist', 'custom-sidebars' )
				);
			}
		}

		if ( function_exists( 'mb_strlen' ) ) {
			if ( mb_strlen( $sb_desc ) > 200 ) {
				$sb_desc = mb_substr( $sb_desc, 0, 200 );
			}
		} else {
			if ( strlen( $sb_desc ) > 200 ) {
				$sb_desc = substr( $sb_desc, 0, 200 );
			}
		}

		// Populate the sidebar object.
		if ( ! AQU_IS_PRO  ) {
			$sidebar['name'] = $sb_name;
			$sidebar['description'] = $sb_desc;
		} else {
			$sidebar['name_lang'] = $sb_name;
			$sidebar['description_lang'] = $sb_desc;
		}
		$sidebar['before_widget'] = stripslashes( trim( @$_POST['before_widget'] ) );
		$sidebar['after_widget'] = stripslashes( trim( @$_POST['after_widget'] ) );
		$sidebar['before_title'] = stripslashes( trim( @$_POST['before_title'] ) );
		$sidebar['after_title'] = stripslashes( trim( @$_POST['after_title'] ) );

		if ( 'insert' == $action ) {
			$sidebars[] = $sidebar;
			$req->message = sprintf(
				__( 'Created new sidebar <strong>%1$s</strong>', 'custom-sidebars' ),
				esc_html( $sidebar['name'] )
			);
		} else {
			$found = false;
			foreach ( $sidebars as $ind => $item ) {
				if ( $item['id'] == $sb_id ) {
					$req->message = sprintf(
						__( 'Updated sidebar <strong>%1$s</strong>', 'custom-sidebars' ),
						esc_html( $sidebar['name'] )
					);
					$sidebars[ $ind ] = $sidebar;
					$found = true;
					break;
				}
			}
			if ( ! $found ) {
				return self::req_err(
					$req,
					__( 'The sidebar was not found', 'custom-sidebars' )
				);
			}
		}

		// Save the changes.
		self::set_custom_sidebars( $sidebars );
		self::refresh_sidebar_widgets();

		$req->data = $sidebar;
		$req->action = $action;

		

		return $req;
	}
	private function delete_item( $req ) {
		$sidebars = self::get_custom_sidebars();
		$sidebar = self::get_sidebar( $req->id, 'all' );

		if ( ! $sidebar ) {
			return self::req_err(
				$req,
				__( 'The sidebar does not exist', 'custom-sidebars' )
			);
		}

		$found = false;
		foreach ( $sidebars as $ind => $item ) {
			if ( $item['id'] == $req->id ) {
				$found = true;
				$req->message = sprintf(
					__( 'Deleted sidebar <strong>%1$s</strong>', 'custom-sidebars' ),
					esc_html( $req->sidebar['name'] )
				);
				unset( $sidebars[ $ind ] );
				break;
			}
		}

		if ( ! $found ) {
			return self::req_err(
				$req,
				__( 'The sidebar was not found', 'custom-sidebars' )
			);
		}

		// Save the changes.
		self::set_custom_sidebars( $sidebars );
		self::refresh_sidebar_widgets();

		return $req;
	}
	private function set_replaceable( $req ) {
		$state = @$_POST['state'];

		$options = self::get_options();
		if ( 'true' === $state ) {
			$req->status = true;
			if ( ! in_array( $req->id, $options['modifiable'] ) ) {
				$options['modifiable'][] = $req->id;
			}
		} else {
			$req->status = false;
			foreach ( $options['modifiable'] as $i => $sb_id ) {
				if ( $sb_id == $req->id ) {
					unset( $options['modifiable'][ $i ] );
					break;
				}
			}
		}
		$options['modifiable'] = array_values( $options['modifiable'] );
		self::set_options( $options );
		$req->replaceable = (object) $options['modifiable'];

		return $req;
	}
	private function get_location_data( $req ) {
		$defaults = self::get_options();
		$raw_posttype = self::get_post_types( 'objects' );
		$raw_cat = self::get_all_categories();

		$archive_type = array(
			'_blog' => __( 'Front Page', 'custom-sidebars' ),
			'_search' => __( 'Search Results', 'custom-sidebars' ),
			'_404' => __( 'Not found (404)', 'custom-sidebars' ),
			'_authors' => __( 'Any Author Archive', 'custom-sidebars' ),
			'_tags' => __( 'Tag Archives', 'custom-sidebars' ),
			'_date' => __( 'Date Archives', 'custom-sidebars' ),
		);

		$raw_authors = array();
		

		// Collect required data for all posttypes.
		$posttypes = array();
		foreach ( $raw_posttype as $item ) {
			$sel_single = @$defaults['post_type_single'][ $item->name ];

			$posttypes[ $item->name ] = array(
				'name' => $item->labels->name,
				'single' => self::get_array( $sel_single ),
			);
		}

		// Extract the data from categories list that we need.
		$categories = array();
		foreach ( $raw_cat as $item ) {
			$sel_single = @$defaults['category_single'][ $item->term_id ];
			$sel_archive = @$defaults['category_archive'][ $item->term_id ];

			$categories[ $item->term_id ] = array(
				'name' => $item->name,
				'count' => $item->count,
				'single' => self::get_array( $sel_single ),
				'archive' => self::get_array( $sel_archive ),
			);
		}

		// Build a list of archive types.
		$archives = array(); // Start with a copy of the posttype list.
		foreach ( $raw_posttype as $item ) {
			if ( $item->name == 'post' ) {
				$label = __( 'Post Index', 'custom-sidebars' );
			} else {
				if ( ! $item->has_archive ) { continue; }
				$label = sprintf(
					__( '%1$s Archives', 'custom-sidebars' ),
					$item->labels->singular_name
				);
			}

			$sel_archive = @$defaults['post_type_archive'][ $item->name ];

			$archives[ $item->name ] = array(
				'name' => $label,
				'archive' => self::get_array( $sel_archive ),
			);
		}

		foreach ( $archive_type as $key => $name ) {
			$sel_archive = @$defaults[ substr( $key, 1 ) ];

			$archives[ $key ] = array(
				'name' => $name,
				'archive' => self::get_array( $sel_archive ),
			);
		}

		

		$req->replaceable = $defaults['modifiable'];
		$req->posttypes = $posttypes;
		$req->categories = $categories;
		$req->archives = $archives;
		return $req;
	}
	private function set_location_data( $req ) {
		$options = self::get_options();
		$sidebars = $options['modifiable'];
		$raw_posttype = self::get_post_types( 'objects' );
		$raw_cat = self::get_all_categories();
		$data = array();

		foreach ( $_POST as $key => $value ) {
			if ( strlen( $key ) > 8 && '___acq___' == substr( $key, 0, 8 ) ) {
				list( $prefix, $id ) = explode( '___', substr( $key, 8 ) );

				if ( ! isset( $data[ $prefix ] ) ) {
					$data[ $prefix ] = array();
				}
				$data[ $prefix ][ $id ] = $value;
			}
		}

		$special_arc = array(
			'blog',
			'404',
			'tags',
			'authors',
			'search',
			'date',
		);

		$raw_authors = array();

		foreach ( $sidebars as $sb_id ) {
			// Post-type settings.
			foreach ( $raw_posttype as $item ) {
				$pt = $item->name;
				if (
					is_array( @$data['pt'][ $sb_id ] ) &&
					in_array( $pt, $data['pt'][ $sb_id ] )
				) {
					$options['post_type_single'][ $pt ][ $sb_id ] = $req->id;
				} elseif (
					isset( $options['post_type_single'][ $pt ][ $sb_id ] ) &&
					$options['post_type_single'][ $pt ][ $sb_id ] == $req->id
				) {
					unset( $options['post_type_single'][ $pt ][ $sb_id ] );
				}

				if (
					is_array( @$data['arc'][ $sb_id ] ) &&
					in_array( $pt, $data['arc'][ $sb_id ] )
				) {
					$options['post_type_archive'][ $pt ][ $sb_id ] = $req->id;
				} elseif (
					isset( $options['post_type_archive'][ $pt ][ $sb_id ] ) &&
					$options['post_type_archive'][ $pt ][ $sb_id ] == $req->id
				) {
					unset( $options['post_type_archive'][ $pt ][ $sb_id ] );
				}
			}

			// Category settings.
			foreach ( $raw_cat as $item ) {
				$cat = $item->term_id;
				if (
					is_array( @$data['cat'][ $sb_id ] ) &&
					in_array( $cat, $data['cat'][ $sb_id ] )
				) {
					$options['category_single'][ $cat ][ $sb_id ] = $req->id;
				} elseif (
					isset( $options['category_single'][ $cat ][ $sb_id ] ) &&
					$options['category_single'][ $cat ][ $sb_id ] == $req->id
				) {
					unset( $options['category_single'][ $cat ][ $sb_id ] );
				}

				if (
					is_array( @$data['arc-cat'][ $sb_id ] ) &&
					in_array( $cat, $data['arc-cat'][ $sb_id ] )
				) {
					$options['category_archive'][ $cat ][ $sb_id ] = $req->id;
				} elseif (
					isset( $options['category_archive'][ $cat ][ $sb_id ] ) &&
					$options['category_archive'][ $cat ][ $sb_id ] == $req->id
				) {
					unset( $options['category_archive'][ $cat ][ $sb_id ] );
				}
			}

			foreach ( $special_arc as $key ) {
				if (
					is_array( @$data['arc'][ $sb_id ] ) &&
					in_array( '_' . $key, $data['arc'][ $sb_id ] )
				) {
					$options[ $key ][ $sb_id ] = $req->id;
				} elseif (
					isset( $options[ $key ][ $sb_id ] ) &&
					$options[ $key ][ $sb_id ] == $req->id
				) {
					unset( $options[ $key ][ $sb_id ] );
				}
			}

			
		}

		$req->message = sprintf(
			__( 'Updated sidebar <strong>%1$s</strong> settings.', 'custom-sidebars' ),
			esc_html( $req->sidebar['name'] )
		);
		self::set_options( $options );
		return $req;
	}
	public function add_meta_box() {
		global $post;

		$post_type = get_post_type( $post );
		if ( ! $post_type ) { return false; }
		if ( ! self::supported_post_type( $post_type ) ) { return false; }

		if (
			defined( 'CUSTOM_SIDEBAR_DISABLE_METABOXES' ) &&
			CUSTOM_SIDEBAR_DISABLE_METABOXES == true
		) {
			return false;
		}

		$pt_obj = get_post_type_object( $post_type );
		if ( $pt_obj->publicly_queryable || $pt_obj->public ) {
			add_meta_box(
				'Sidebars-mb',
				__( 'Sidebars', 'custom-sidebars' ),
				array( $this, 'print_metabox_editor' ),
				$post_type,
				'side'
			);
		}
	}
	public function print_metabox_editor() {
		global $post;
		$this->print_sidebars_form( $post->ID, 'metabox' );
	}
	public function print_metabox_quick() {
		$this->print_sidebars_form( 0, 'quick-edit' );
	}
	protected function print_sidebars_form( $post_id, $type = 'metabox' ) {
		global $wp_registered_sidebars;
		$available = ACS_Class::sort_sidebars_by_name( $wp_registered_sidebars );
		$replacements = self::get_replacements( $post_id );
		$sidebars = self::get_options( 'modifiable' );
		$selected = array();
		if ( ! empty( $sidebars ) ) {
			foreach ( $sidebars as $s ) {
				if ( isset( $replacements[ $s ] ) ) {
					$selected[ $s ] = $replacements[ $s ];
				} else {
					$selected[ $s ] = '';
				}
			}
		}

		switch ( $type ) {
			case 'col-sidebars':
				include CSB_VIEWS_DIR . 'col-sidebars.php';
				break;

			case 'quick-edit':
				include CSB_VIEWS_DIR . 'quick-edit.php';
				break;

			default:
				include CSB_VIEWS_DIR . 'select_sidebar.php';
				break;
		}
	}

	public function store_replacements( $post_id ) {
		global $action;

		if ( ! current_user_can( self::$cap_required ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}
		if ( ( isset( $_POST['action'] ) && 'inline-save' == $_POST['action'] ) || 'editpost' != $action  ) {
			return $post_id;
		}

		// Make sure meta is added to the post, not a revision.
		if ( $the_post = wp_is_post_revision( $post_id ) ) {
			$post_id = $the_post;
		}

		$sidebars = self::get_options( 'modifiable' );
		$data = array();
		if ( ! empty( $sidebars ) ) {
			foreach ( $sidebars as $sb_id ) {
				if ( isset( $_POST[ 'acq_replacement_' . $sb_id ] ) ) {
					$replacement = $_POST[ 'acq_replacement_' . $sb_id ];
					if ( ! empty( $replacement ) ) {
						$data[ $sb_id ] = $replacement;
					}
				}
			}
		}

		self::set_post_meta( $post_id, $data );
	}

	
};


