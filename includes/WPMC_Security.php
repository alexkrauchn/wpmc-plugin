<?php
if ( !defined( 'ABSPATH' ) ) {
	exit;
}
class WPMC_Security {

	public function get_core_files_checksum_data() {
		$scanned_files = self::scan_dir( ABSPATH, array( 'wp-content' ) );
		$result = array();

		foreach ( $scanned_files as $path => $file ) {
			$result[$path] = md5_file( $file );
		}

		return $result;
	}

	public function get_themes_files_checksum_data() {
		$themes = wp_get_themes();
		$result = array();

		foreach ( $themes as $theme ) {
			$theme_data = array(
				'name'		=> $theme->Name,
				'version'	=> $theme->Version,
				'files'		=> array()
			);

			$theme_folder_name = $theme->get_stylesheet();
			$files = $theme->get_files( null, -1 );
			
			foreach ( $files as $relative_path => $full_path ) {
				$theme_data['files'][$theme_folder_name . DIRECTORY_SEPARATOR . $relative_path] = md5_file( $full_path );
			}

			array_push( $result, $theme_data );
		}

		return $result;
	}

	public function get_plugins_files_checksum_data() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$plugins = get_plugins();
		$result = array();

		foreach ( $plugins as $plugin_slug => $plugin_data ) {

			$plugin_data = array(
				'name'		=> self::get_plugin_name( $plugin_slug ),
				'version'	=> !empty( $plugin_data['Version'] ) ? $plugin_data['Version'] : false,
				'files'		=> array()
			);

			$plugin_path = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $plugin_slug;
			$plugin_folder_struct = explode( DIRECTORY_SEPARATOR, $plugin_slug );

			if ( count( $plugin_folder_struct ) > 1 ) {
				$plugin_path = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . dirname( $plugin_slug );

				$scanned_files = self::scan_dir( $plugin_path, array() );
				foreach ( $scanned_files as $path => $file ) {
					$relative_path = trim( str_replace( WP_PLUGIN_DIR, '', ABSPATH . $path ), DIRECTORY_SEPARATOR );
					$plugin_data['files'][$relative_path] = md5_file( $file );
				}
			} else {
				$plugin_data['files'][basename( $plugin_path )] = md5_file( $plugin_path );
			}

			array_push( $result, $plugin_data );
		}

		return $result;
	}

	public static function is_shell_exec_enabled() {
		return is_callable( 'shell_exec' ) && false === stripos( ini_get( 'disable_functions' ), 'shell_exec' );
	}		

	private static function scan_dir( $path, $exclude = array(), $extension = false ) {
        $result = [];
        if ( !in_array( '.', $exclude ) ) {
        	array_push( $exclude, '.' );
        }
        if ( !in_array( '..', $exclude ) ) {
        	array_push( $exclude, '..' );
        }
  		foreach ( scandir( $path ) as $filename ) {
  			if ( in_array( $filename, $exclude ) ) {
  				continue;
  			}
    		$file_path = rtrim( $path, '/' ) . DIRECTORY_SEPARATOR . $filename;
    		if ( is_dir( $file_path ) ) {
    			$result = array_merge( $result, self::scan_dir( $file_path, $exclude, $extension ) );
    		} else {
    			if ( $extension && pathinfo( $file_path, PATHINFO_EXTENSION ) != $extension ) {
    				continue;
    			}
      			$result[str_replace( ABSPATH, '', $file_path )] = $file_path;
    		}
		}
  		return $result;
    }

    private static function get_plugin_name( $basename ) {
		if ( false === strpos( $basename, '/' ) ) {
			$name = basename( $basename, '.php' );
		} else {
			$name = dirname( $basename );
		}

		return $name;
	}

}