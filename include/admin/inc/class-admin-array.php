<?php
//array all kind of functions

class Admin_Array extends Admin {

	public function get( &$val1, $val2 = array() ) {
		if ( is_array( $val1 ) ) {
			return $val1;
		} else if ( is_array( $val2 ) ) {
			return $val2;
		} else {
			return array();
		}
	}


	 // Inserts any number of scalars or arrays at the point
	
	public function insert( &$haystack, $where, $needle, $stuff ){
		if ( ! is_array( $haystack ) ) { return $haystack; }

		$new_array = array();
		for ( $i = 3; $i < func_num_args(); ++$i ){
			$arg = func_get_arg( $i );
			if ( is_array( $arg ) ) {
				$new_array = array_merge( $new_array, $arg );
			} else {
				$new_array[] = $arg;
			}
		}

		$i = 0;
		foreach ( $haystack as $key => $value ) {
			$i += 1;

			if ( $key == $needle ) {
				if ( 'before' == $where ) {
					$i -= 1;
				}

				break;
			}
		}

		$haystack = array_merge(
			array_slice( $haystack, 0, $i, true ),
			$new_array,
			array_slice( $haystack, $i, null, true )
		);

		return $i;
	}

	
	// Tests if the given array is sequential or associative.

	public function is_seq( $array ) {
		for (
			reset( $array );
			is_int( key( $array ) );
			next( $array )
		) {}
		return is_null( key( $array ) );
	}
 //merge arrays
	public function merge_recursive_distinct( array &$array1, array &$array2 ) {
		$merged = $array1;

		foreach ( $array2 as $key => &$value ) {
			if ( is_array( $value ) && isset( $merged[$key] ) && is_array( $merged[$key] ) ) {
				if ( $this->is_seq( $value ) && $this->is_seq( $merged[$key] ) ) {
					$merged[$key] = array_merge( $merged[$key], $value );
				} else {
					$merged[$key] = $this->merge_recursive_distinct( $merged[$key], $value );
				}
			} else {
				$merged[$key] = $value;
			}
		}

		return $merged;
	}

	
	 // Checks if the given array contains all the specified fields.
	
	public function equip( &$arr, $fields ) {
		$missing = 0;
		$is_obj = false;

		if ( is_object( $arr ) ) { $is_obj = true; }
		else if ( ! is_array( $arr ) ) { return -1; }

		if ( ! is_array( $fields ) ) {
			$fields = func_get_args();
			array_shift( $fields ); // Remove $arr from the field list.
		}

		foreach ( $fields as $field ) {
			if ( $is_obj ) {
				if ( ! property_exists( $arr, $field ) ) {
					$arr->$field = false;
					$missing += 1;
				}
			} else {
				if ( ! isset( $arr[ $field ] ) ) {
					$arr[ $field ] = false;
					$missing += 1;
				}
			}
		}

		return $missing;
	}

	//short functions
	public function equip_post( $fields ) {
		$fields = is_array( $fields ) ? $fields : func_get_args();
		return $this->equip( $_POST, $fields );
	}
	public function equip_request( $fields ) {
		$fields = is_array( $fields ) ? $fields : func_get_args();
		return $this->equip( $_REQUEST, $fields );
	}

	public function equip_get( $fields ) {
		$fields = is_array( $fields ) ? $fields : func_get_args();
		return $this->equip( $_GET, $fields );
	}

	
	 // By default WordPress escapes all GPC values with slashes
	
	public function strip_slashes( &$arr, $fields ) {
		$modified = 0;
		if ( ! is_array( $arr ) ) { return -1; }

		if ( ! is_array( $fields ) ) {
			$fields = func_get_args();
			array_shift( $fields ); // Remove $arr from the field list.
		}

		foreach ( $fields as $field ) {
			if ( isset( $arr[ $field ] ) ) {
				$arr[ $field ] = stripslashes_deep( $arr[$field] );
				$modified += 1;
			}
		}

		return $modified;
	}

}