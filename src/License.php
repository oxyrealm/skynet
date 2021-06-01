<?php

namespace Oxyrealm\Skynet;

use Asura\Integration\WordPress\Notice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class License {
	private const UNREGISTERED_MSG = 'Your installed Asura is unregistered';
	private $options = [];

	public function __construct( array $options ) {
		$this->options = $options;
	}

	public function isActivated() {
		$license = Cache::get( 'license' );

		if ( ! $license && $this->options['license'] === '' ) {
			Notice::error( self::UNREGISTERED_MSG );

			return false;
		}

		if ( $license ) {
			if ( $license->license !== 'valid' ) {
				Notice::error( [
					self::UNREGISTERED_MSG,
					$this->errorMessage( $license->license )
				] );

				return false;
			}

			return $license;
		}

		$response = $this->apiRequest( 'check_license' );

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			Notice::error( is_wp_error( $response ) ? $response->get_error_message() : 'An error occurred, please try again.' );

			return false;
		}

		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		if ( $license_data->success === false ) {
			if ( property_exists( $license_data, 'error' ) ) {
				Notice::error( self::errorMessage( $license_data->error ) );
			}

			return false;
		}

		return Cache::put( 'license', $license_data, Carbon::now()->addDay() );
	}

	public static function errorMessage( $msgcode ) {
		switch ( $msgcode ) {
			case 'expired' :
				return 'Your license key expired';
			case 'disabled' :
			case 'revoked' :
				return 'Your license key has been disabled.';
			case 'site_inactive' :
				return 'Your license is not active for this URL.';
			case 'missing_url' :
				return 'License doesn\'t exist or URL not provided.';
			case 'key_mismatch' :
			case 'missing' :
			case 'invalid' :
			case 'invalid_item_id' :
			case 'item_name_mismatch' :
				return 'Invalid license key';
			case 'no_activations_left':
				return 'Your license key has reached its activation limit.';
			default :
				return 'An error occurred, please try again.';
		}
	}

	private function apiRequest( $action ) {
		return wp_remote_post( $this->options['protocol'], [
			'timeout'   => 15,
			'sslverify' => false,
			'body'      => [
				'edd_action'  => $action,
				'license'     => $this->options['license'] ?? '',
				'item_id'     => $this->options['item_id'] ?? false,
				'version'     => $this->options['version'] ?? false,
				'slug'        => basename( ASURA_PLUGIN_FILE, '.php' ),
				'author'      => $this->options['author'],
				'url'         => home_url(),
				'beta'        => $this->options['beta'] ?? false,
				'environment' => function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production',
			]
		] );
	}

	public function deactivate() {
		Cache::forget( 'license' );

		return $this->apiRequest( 'deactivate_license' );
	}

	public function activate( ?string $license ) {
		if ( $license ) {
			$this->options['license'] = $license;
		}

		Cache::forget( 'license' );

		return $this->apiRequest( 'activate_license' );
	}
}