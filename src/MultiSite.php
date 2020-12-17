<?php

namespace TheLostAsura\Skynet;

use Asura\Models\Blog;

class MultiSite {
	public static function isMultiSite() {
        return is_multisite();
    }

    public static function blog() : ?Blog  {
        if ( ! is_multisite() ) {
            return null;
        }
        return Blog::find( get_current_blog_id() );
    }

    public static function blogs() {
        return Blog::all();
    }
}