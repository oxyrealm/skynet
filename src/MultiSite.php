<?php

namespace TheLostAsura\Skynet;

use Asura\Models\Blog;

class MultiSite {
	public static function isMultiSite() {
        return is_multisite();
    }

    public static function getBlog() : ?Blog  {
        return Blog::find(get_current_blog_id());
    }
}