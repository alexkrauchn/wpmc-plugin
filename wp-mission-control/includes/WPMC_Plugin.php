<?php
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

class WPMC_Plugin {
	protected static $instance, $options;
	public static $version = '1.0';

	public function __construct() {
        add_action( 'rest_api_init', array( $this, 'add_api_endpoints' ) );
    }

	public static function init() {
		self::$options = get_option( 'wpmc_options' );
		is_null( self::$instance ) AND self::$instance = new self;

		if ( is_admin() ) {
			require_once WPMC_PLUGIN_DIR_PATH . '/includes/settings/WPMissionControlSettings.php';
			$wp_mission_control_settings = new WPMissionControlSettings();
		}

        return self::$instance;
	}

	public static function activate_plugin() {
		if ( !current_user_can( 'activate_plugins' ) ) {
            return;
		}
		add_action( 'activated_plugin', array( 'WPMC_PLugin', 'activation_redirect' ) );

	}

	public static function activation_redirect( $plugin ) {
        wp_redirect( admin_url( 'options-general.php?page=wp-mission-control-settings' ) );
        exit();
	}

	public static function deactivate_plugin() {
		if ( !current_user_can( 'activate_plugins' ) ) {
            return;
		}
	}

	public static function uninstall_plugin() {
        delete_option( 'wpmc_options' );
	}

	public static function checks() {
		$wpmc_security = new WPMC_Security;
		$health_status = get_transient( 'health-check-site-status-result' );;
		if ( empty( $health_status ) ) {
			self::maybe_create_scheduled_event_or_run_check();
		} else {
			$decoded = json_decode( $health_status );
			if ( isset( $decoded->good ) && !empty( $decoded->good ) ) {
				$decoded->good = strval( $decoded->good );
			}
			if ( isset( $decoded->recommended ) && !empty( $decoded->recommended ) ) {
				$decoded->recommended = strval( $decoded->recommended );
			}
			if ( isset( $decoded->critical ) && !empty( $decoded->critical ) ) {
				$decoded->critical = strval( $decoded->critical );
			}
		}

		return array(
			'api_key'				=> self::$options['api_key'],
			'shell_exec'			=> WPMC_Security::is_shell_exec_enabled(),
			'wp_version'			=> get_bloginfo( 'version' ),
			'locale'				=> get_locale(),
			'abspath'				=> ABSPATH,
			'theme_root'			=> get_theme_root(),
			'plugin_dir'			=> WP_PLUGIN_DIR,
			'health_status'			=> $decoded,
			'core_checksum_data'	=> $wpmc_security->get_core_files_checksum_data(),
			'themes_checksum_data'	=> $wpmc_security->get_themes_files_checksum_data(),
			'plugins_checksum_data'	=> $wpmc_security->get_plugins_files_checksum_data()
		);
	}

	public function add_api_endpoints() {

		register_rest_route( 'wpmc/v1' , '/status', array(
			'methods'				=> WP_REST_Server::READABLE,
			'callback'				=> array( $this, 'rest_status_handler' ),
			'permission_callback'	=> array( $this, 'rest_access_validator' ),
			'show_in_index'			=> false
		) );

	}

	public function rest_status_handler( $request ) {
		$data = $this::checks();
		$response = new WP_REST_Response( array(
			$data,
			// $request->get_attributes()
		) );
		$response->set_status( 200 );
		return $response;
	}

	public function rest_access_validator( $request ) {
		$headers = $request->get_headers();
		
		$authenticationtoken = false;
		if ( !$this->is_https_request() ) {
			// return true;
			return new WP_Error(
				'rest_forbidden',
                __( 'Only requests via HTTPS are permitted', 'wpmc' ),
                array( 'status' => 403 )
			);
		}
		if ( empty( $headers['authenticationtoken'][0] ) ) {
			return new WP_Error(
				'rest_forbidden',
                __( 'Authentication token is required', 'wpmc' ),
                array( 'status' => 403 )
			);
		}
		if ( empty( self::$options['api_key'] ) ) {
			return new WP_Error(
				'rest_forbidden',
                __( 'Authentication token is not set', 'wpmc' ),
                array( 'status' => 403 )
			);
		}
		if ( self::$options['api_key'] != $headers['authenticationtoken'][0] ) {
			return new WP_Error(
				'rest_forbidden',
                __( 'Wrong authentication token', 'wpmc' ),
                array( 'status' => 403 )
			);
		}
		return true;
	}

	private function is_https_request() {
		if ( isset( $_SERVER['HTTPS'] ) &&
		    ( $_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1 ) ||
		    isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) &&
		    $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
		  	return true;
		}
		return false;
	}

	private static function maybe_create_scheduled_event_or_run_check() {
		do_action( 'wp_site_health_scheduled_check' );
		if ( ! wp_next_scheduled( 'wp_site_health_scheduled_check' ) && ! wp_installing() ) {
			wp_schedule_event( time() + DAY_IN_SECONDS, 'daily', 'wp_site_health_scheduled_check' );
		}
	}

}