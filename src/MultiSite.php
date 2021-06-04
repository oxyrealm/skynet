<?php

namespace Oxyrealm\Skynet;

use Oxyrealm\Asura\Models\Blog;

class MultiSite {
	public static function isMultiSite(): bool {
		return is_multisite();
	}

	public static function blog(): ?Blog {
		if ( ! is_multisite() ) {
			return null;
		}

		return Blog::find( get_current_blog_id() );
	}

	public static function blogs() {
		return Blog::all();
	}

	public static function getAdminRouteUuid() {
		$uuid = get_option( 'asura_admin_route_uuid' );

		if ( ! wp_is_uuid($uuid) ) {
			$uuid = static::setAdminRouteUuid();
		}

		return $uuid;
	}

	public static function setAdminRouteUuid() {
		update_option( 'asura_admin_route_uuid', wp_generate_uuid4() )
	}
}