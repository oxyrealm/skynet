<?php

namespace TheLostAsura\Skynet;

use function Composer\Autoload\includeFile;

class DiagnosticData {

	public static function getData() {
		return self::collectData();
	}

	private static function collectData() {
		global $wpdb;

		if (
			! function_exists( 'get_plugins' )
			|| ! function_exists( 'get_plugin_updates' )
		) {
			includeFile( ABSPATH . 'wp-admin/includes/update.php' );
			includeFile( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		$plugin_updates = get_plugin_updates();
		$plugins        = [
			'active'   => [],
			'inactive' => [],
			'mu'       => get_mu_plugins(),
		];

		foreach ( get_plugins() as $plugin_path => $plugin ) {
			if ( array_key_exists( $plugin_path, $plugin_updates ) ) {
				$plugin['updates'] = $plugin_updates[ $plugin_path ]->update;
			}
			$plugins[ is_plugin_active( $plugin_path ) ? 'active' : 'inactive' ][] = $plugin;
		}

		$database = [];

		if ( is_resource( $wpdb->dbh ) ) {
			$database['extension'] = 'mysql';
		} elseif ( is_object( $wpdb->dbh ) ) {
			$database['extension'] = get_class( $wpdb->dbh );
		} else {
			$database['extension'] = null;
		}

		if ( isset( $wpdb->use_mysqli ) && $wpdb->use_mysqli ) {
			$database['server_version'] = mysqli_get_server_info( $wpdb->dbh );
			$database['client_version'] = $wpdb->dbh->client_info;
		} else {
			$database['server_version'] = $wpdb->get_var( 'SELECT VERSION()' );
			$database['client_version'] = preg_match( '|[0-9]{1,2}\.[0-9]{1,2}\.[0-9]{1,2}|', mysql_get_client_info(), $matches ) ? $matches[0] : null;
		}

		return [
			'wordpress' => [
				'debug'     => config( 'app.debug' ),
				'version'   => get_bloginfo( 'version' ),
				'multisite' => is_multisite(),
				'ssl'       => is_ssl(),
				'plugins'   => $plugins,
				'server'    => [
					'server_architecture' => function_exists( 'php_uname' ) ? sprintf( '%s %s %s', php_uname( 's' ), php_uname( 'r' ), php_uname( 'm' ) ) : null,
					'httpd_software'      => $_SERVER['SERVER_SOFTWARE'] ?? null,
					'php_version'         => phpversion() . ( PHP_INT_SIZE * 8 === 64 ? ' 64bit' : '' ),
					'php_sapi'            => function_exists( 'php_sapi_name' ) ? php_sapi_name() : null,
					'max_execution_time'  => function_exists( 'ini_get' ) ? ini_get( 'max_execution_time' ) : null,
					'memory_limit'        => function_exists( 'ini_get' ) ? ini_get( 'memory_limit' ) : null,
					'curl_version'        => function_exists( 'curl_version' ) ? curl_version() : null,
				],
				'database'  => $database
			]
		];
	}

}
