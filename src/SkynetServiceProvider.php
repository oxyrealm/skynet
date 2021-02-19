<?php

namespace TheLostAsura\Skynet;

use Asura\Settings\SkynetSettings;
use Composer\Semver\Comparator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class SkynetServiceProvider extends ServiceProvider {
	public function register() {
		register_activation_hook( ASURA_PLUGIN_FILE, [ Setup::class, 'install' ] );
		register_deactivation_hook( ASURA_PLUGIN_FILE, [ Setup::class, 'deactivate' ] );
		register_uninstall_hook( ASURA_PLUGIN_FILE, [ Setup::class, 'uninstall' ] );
	}

	public function boot() {
		if ( Setup::isInstalled() ) {
			if ( Comparator::lessThan( get_option( 'asura_installed' ), THELOSTASURA ) ) {
				Log::info( 'upgrading_database', [
					'installed_version' => get_option( 'asura_installed' ),
					'upgrade_version'   => THELOSTASURA,
				] );
				Setup::migrate();
			}

			$skynet = config( 'services.skynet' );

			$setting = app( SkynetSettings::class );

			$payload = [
				'version' => THELOSTASURA,
				'license' => $setting->license,
				'item_id' => $skynet['id'],
				'author'  => $skynet['commander'],
				'beta'    => $setting->beta,
			];

			$this->app->singleton( License::class, function ( $app ) use ( $payload, $skynet ) {
				return new License( array_merge( $payload, [
					'protocol' => $skynet['protocol'],
				] ) );
			} );

			$this->app->singleton( Updater::class, function ( $app ) use ( $payload, $skynet ) {
				return new Updater( $skynet['protocol'], ASURA_PLUGIN_FILE, $payload );
			} );

			$this->app->make( License::class );
		}
	}
}