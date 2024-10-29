<?php
//Session storage add/get/clear
class Admin_Session extends Admin {

	
	 // Adds a value to the data collection in the user session.
	
	public function add( $key, $value ) {
		self::_sess_add( 'store:' . $key, $value );
	}

	
	public function get( $key ) {
		$vals = self::_sess_get( 'store:' . $key );
		foreach ( $vals as $key => $val ) {
			if ( null === $val ) { unset( $vals[ $key ] ); }
		}
		$vals = array_values( $vals );
		return $vals;
	}


	public function get_clear( $key ) {
		$val = $this->get( $key );
		self::_sess_clear( 'store:' . $key );
		return $val;
	}

}
class Admin_Net extends Admin {	

}