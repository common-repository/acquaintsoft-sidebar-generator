<?php

//Update component.
 
class Admin_Updates extends Admin  {

	protected $commands = array();

	protected $error = false;

	
	 // The plugin name. Used to log data in the uploads directory.

	protected $plugin = '';

	//clear all commands
	public function clear() {
		$this->commands = array();
		$this->error = false;
	}

	
	 //Adds a command to the transaction queue.

	public function add( $command ) {
		$this->commands[] = func_get_args();
	}

	
	 //Executes each command that is in the transaction queue.

	public function execute() {
		$this->error = false;

		foreach ( $this->commands as $key => $transaction ) {
			$done = false;
			$log_line = '';

			if ( count( $transaction ) < 1 ) {
				$done = false;
			} elseif ( ! is_callable( $transaction[0] ) ) {
				$done = false;
			} else {
				$func = array_shift( $transaction );

				if ( is_array( $func ) ) {
					if ( is_object( $func[0] ) ) {
						$log_line = get_class( $func[0] ) . '->';
					} elseif ( is_scalar( $func[0] ) ) {
						$log_line = $func[0] . '::';
					}
					if ( is_scalar( $func[1] ) ) {
						$log_line .= $func[1];
					}
				} else {
					$log_line = $func;
				}
				$log_line .= '(' . json_encode( $transaction ) . ')';

				try {
					call_user_func_array( $func, $transaction );
					$done = true;
				} catch( Exception $ex ) {
					$this->set_error( $ex, $func );
					return false;
				}
			}

			if ( $done ) {
				$this->log_action( $log_line );
				unset( $this->commands[$key] );
			}
		}

		return true;
	}

	 //Saves error details if a command fails during execution.
	protected function set_error( $exception, $command ) {
		$this->error = $exception;
		$this->error->command = $command;
	}

	 // Returns the last error and resets the error-flag.
	public function last_error() {
		$error = $this->error;
		$this->error = false;

		return $error;
	}

	
	 // Debug function that will display the contents of the current queue.
	
	public function debug() {
		self::$core->debug->dump( $this->commands );
	}

	//sets plugin name
	public function plugin( $name ) {
		$this->plugin = sanitize_html_class( $name );
	}

	 //Writes data to a file in the uploads directory.
	
	private function write_to_file( $file, $ext, $data, $silent_fail = false ) {
		// Find the uploads-folder.
		$upload = wp_upload_dir();

		if ( false !== $upload['error'] ) {
			return $this->write_file_failed(
				$silent_fail,
				1,
				$upload['error']
			);
		}

		// Create the Snapshot sub-folder.
		if ( empty( $this->plugin ) ) {
			$this->plugin( 'plugin' );
		}
		$target = trailingslashit( $upload['basedir'] ) . $this->plugin . '/';

		if ( ! is_dir( $target ) ) {
			mkdir( $target );
		}

		if ( ! is_dir( $target ) ) {
			return $this->write_file_failed(
				$silent_fail,
				2,
				'Could not create sub-directory ' . $target
			);
		}

		// Create the empty snapshot file.
		$filename = sanitize_html_class( $file );
		$filename .= '-' . date( 'Ymd-His' );
		$ext = '.' . $ext;
		$i = '';
		$sep = '';

		while ( file_exists( $target . $filename . $sep . $i . $ext ) ) {
			if ( empty( $i ) ) { $i = 1; }
			else { $i += 1; }
			$sep = '-';
		}
		$filename = $target . $filename . $sep . $i . $ext;

		file_put_contents( $filename, '' );

		if ( ! file_exists( $filename ) ) {
			return $this->write_file_failed(
				$silent_fail,
				3,
				'Could not create file ' . $filename
			);
		}

		// Write data to file.
		file_put_contents( $filename, $data );

		return $filename;
	}
    //reads data from file
	private function read_from_file( $file ) {
		// Find the uploads-folder.
		$upload = wp_upload_dir();

		if ( false !== $upload['error'] ) {
			return '';
		}

		// Build the full file name.
		if ( empty( $this->plugin ) ) {
			$this->plugin( 'plugin' );
		}
		$target = trailingslashit( $upload['basedir'] ) . $this->plugin . '/';

		if ( ! is_dir( $target ) ) {
			return '';
		}

		$filename = $target . $file;

		if ( ! is_file( $filename ) ) {
			return '';
		}

		$data = file_get_contents( $filename );

		return $data;
	}
    //returns backup files
	public function list_files( $ext = '' ) {
		$res = array();

		// Find the uploads-folder.
		$upload = wp_upload_dir();

		if ( false !== $upload['error'] ) {
			return $res;
		}

		// Build the full file name.
		if ( empty( $this->plugin ) ) {
			$this->plugin( 'plugin' );
		}
		$target = trailingslashit( $upload['basedir'] ) . $this->plugin . '/';

		if ( ! is_dir( $target ) ) {
			return $res;
		}

		if ( empty( $ext ) ) {
			$ext = '*';
		}

		$pattern = $target . '*.' . $ext;
		$res = glob( $pattern );
		foreach ( $res as $key => $path ) {
			$res[$key] = str_replace( $target, '', $path );
		}

		return $res;
	}

	//saves actions
	public function log_action( $data, $silent_fail = false ) {
		static $Logfile = null;

		$data .= "\n-----\n";
		if ( null === $Logfile ) {
			$Logfile = $this->write_to_file( 'update_log', 'log', $data, $silent_fail );
		} else {
			file_put_contents( $Logfile, $data, FILE_APPEND );
		}
	}

	//saves certain database values
	public function snapshot( $name, $data_list, $silent_fail = false ) {
		// Collect data from the DB that was specified by the user.
		$data = $this->snapshot_collect( $data_list );
		$data = json_encode( $data );

		$this->write_to_file( $name, 'json', $data, $silent_fail );
	}
	private function write_file_failed( $silent_fail, $err_code, $error = '' ) {
		if ( $silent_fail ) { return false; }

		if ( empty( $this->plugin ) ) {
			$this->plugin( 'plugin' );
		}

		$msg = sprintf(
			'<b>Abborting update of %s!</b> '.
			'Could not create a restore-point [%s]<br />%s',
			ucwords( $this->plugin ),
			$err_code,
			$error
		);

		wp_die( $msg );
	}
	private function snapshot_collect( $data_list ) {
		$dump = (object) array();

		// Options.
		$dump->options = array();
		if ( isset( $data_list->options )
			&& is_array( $data_list->options )
		) {
			foreach ( $data_list->options as $option ) {
				$dump->options[$option] = get_option( $option );
			}
		}

		// Posts and Post-Meta
		$dump->posts = array();
		$dump->postmeta = array();
		if ( isset( $data_list->posts )
			&& is_array( $data_list->posts )
		) {
			foreach ( $data_list->posts as $id ) {
				$post = get_post( $id );
				$meta = get_post_meta( $id );

				// Flatten the meta values.
				foreach ( $meta as $key => $values ) {
					if ( is_array( $values ) && isset( $values[0] ) ) {
						$meta[ $key ] = $values[0];
					}
				}

				// Append the data to the dump.
				if ( ! isset( $dump->posts[$post->post_type] ) ) {
					$dump->posts[$post->post_type] = array();
					$dump->postmeta[$post->post_type] = array();
				}
				$dump->posts[$post->post_type][$post->ID] = $post;
				$dump->postmeta[$post->post_type][$post->ID] = $meta;
			}
		}

		return $dump;
	}

	 //Restores a saved snapshot.
	 
	public function restore( $snapshot ) {
		global $wpdb;

		// Get the contents of the snapshot file.
		$data = $this->read_from_file( $snapshot );
		if ( empty( $data ) ) {
			return false;
		}

		// Decode the snapshot data to an PHP object.
		$data = json_decode( $data, true );
		if ( empty( $data ) ) {
			return false;
		}

		// The restore-process is handled as execution transaction.
		$this->clear();

		// Options
		if ( ! empty( $data['options'] ) && is_array( $data['options'] ) ) {
			$sql_delete = "DELETE FROM {$wpdb->options} WHERE option_name IN ";
			$sql_idlist = array();
			$sql_insert = "INSERT INTO {$wpdb->options} (option_name, option_value) VALUES ";
			$sql_values = array();

			foreach ( $data['options'] as $key => $value ) {
				$sql_idlist[] = $wpdb->prepare( '%s', $key );
				$sql_values[] = $wpdb->prepare( '(%s,%s)', $key, maybe_serialize( $value ) );
			}

			if ( ! empty( $sql_values ) ) {
				$this->add( $sql_delete . '(' . implode( ',', $sql_idlist ) . ')' );
				$this->add( $sql_insert . implode( ",\n", $sql_values ) );
			}
		}

		// Posts
		if ( ! empty( $data['posts'] ) && is_array( $data['posts'] ) ) {
			foreach ( $data['posts'] as $posttype => $items ) {
				$sql_delete_post = "DELETE FROM {$wpdb->posts} WHERE ID IN ";
				$sql_delete_meta = "DELETE FROM {$wpdb->postmeta} WHERE post_id IN ";
				$sql_idlist = array();
				$sql_insert = "INSERT INTO {$wpdb->posts} (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count) VALUES ";
				$sql_values = array();

				foreach ( $items as $id => $post ) {
					self::$core->array->equip(
						$post,
						'post_author',
						'post_date',
						'post_date_gmt',
						'post_content',
						'post_title',
						'post_excerpt',
						'post_status',
						'comment_status',
						'ping_status',
						'post_password',
						'post_name',
						'to_ping',
						'pinged',
						'post_modified',
						'post_modified_gmt',
						'post_content_filtered',
						'post_parent',
						'guid',
						'menu_order',
						'post_type',
						'post_mime_type',
						'comment_count'
					);
					$sql_idlist[] = $wpdb->prepare( '%s', $id );
					$sql_values[] = $wpdb->prepare(
						'(%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)',
						$id,
						$post['post_author'],
						$post['post_date'],
						$post['post_date_gmt'],
						$post['post_content'],
						$post['post_title'],
						$post['post_excerpt'],
						$post['post_status'],
						$post['comment_status'],
						$post['ping_status'],
						$post['post_password'],
						$post['post_name'],
						$post['to_ping'],
						$post['pinged'],
						$post['post_modified'],
						$post['post_modified_gmt'],
						$post['post_content_filtered'],
						$post['post_parent'],
						$post['guid'],
						$post['menu_order'],
						$post['post_type'],
						$post['post_mime_type'],
						$post['comment_count']
					);
				}

				while ( ! empty( $sql_idlist ) ) {
					$values = array();
					for ( $i = 0; $i < 100; $i += 1 ) {
						if ( empty( $sql_idlist ) ) { break; }
						$values[] = array_shift( $sql_idlist );
					}
					$this->add( $sql_delete_post . '(' . implode( ',', $values ) . ')' );
					$this->add( $sql_delete_meta . '(' . implode( ',', $values ) . ')' );
				}
				while ( ! empty( $sql_values ) ) {
					$values = array();
					for ( $i = 0; $i < 100; $i += 1 ) {
						if ( empty( $sql_values ) ) { break; }
						$values[] = array_shift( $sql_values );
					}
					$this->add( $sql_insert . implode( ",\n", $values ) );
				}
			}
		}

		// Postmeta
		if ( ! empty( $data['postmeta'] ) && is_array( $data['postmeta'] ) ) {
			foreach ( $data['postmeta'] as $posttype => $items ) {
				foreach ( $items as $id => $entries ) {
					$sql_meta = "INSERT INTO {$wpdb->postmeta} (post_id,meta_key,meta_value) VALUES ";
					$sql_values = array();

					foreach ( $entries as $key => $value ) {
						$sql_values[] = $wpdb->prepare( '(%s,%s,%s)', $id, $key, $value );
					}

					if ( ! empty( $sql_values ) ) {
						$this->add( $sql_meta . implode( ",\n", $sql_values ) );
					}
				}
			}
		}

		// Run all scheduled queries
		foreach ( $this->commands as $key => $params ) {
			if ( ! isset( $params[0] ) ) { continue; }
			$query = $params[0];

			$res = $wpdb->query( $query );
		}

		return true;
	}

}