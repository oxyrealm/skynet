<?php

namespace Oxyrealm\Skynet;

use Illuminate\Support\Str;
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
		update_option( 'asura_admin_route_uuid', wp_generate_uuid4() );
	}

	public static function getEndpointRoutes() {
		$routes = app()->router->getRoutes();
        $url = MultiSite::isMultiSite() ? ( 
            is_ssl() ? 'https://' : 'http://'
        ) . MultiSite::blog()->domain : rtrim( site_url(), '/' ) ;

        $api_routes = [
            'url' => $url,
            'port' => parse_url($url)['port'] ?? null,
            'defaults' => method_exists(app('url'), 'getDefaultParameters')
                ? app('url')->getDefaultParameters()
                : [],
			'admin_route_uuid' => MultiSite::getAdminRouteUuid(),
			'endpoint' => config('rest.endpoint').'/'.config('rest.version')
        ];

        foreach ($routes as $route) {
            if (!empty($route['action']['as'])) {
                $api_routes['routes'][$route['action']['as']] = [
                    'uri' => Str::length($route['uri']) < 2 ? $route['uri'] : (string) Str::of($route['uri'])->replaceFirst( $api_routes['endpoint'] , '')->ltrim('/'),
                    'methods' => $route['method'] === 'GET' ? ['HEAD','GET'] : [$route['method']],
                ];
            }
        }

        return $api_routes;
	}
}