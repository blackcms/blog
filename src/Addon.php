<?php

namespace BlackCMS\Blog;

use BlackCMS\Blog\Models\Category;
use BlackCMS\Blog\Models\Tag;
use BlackCMS\Dashboard\Repositories\Interfaces\DashboardWidgetInterface;
use BlackCMS\Menu\Repositories\Interfaces\MenuNodeInterface;
use Illuminate\Support\Facades\Schema;
use BlackCMS\Addon\Abstracts\AddonOperationAbstract;

class Addon extends AddonOperationAbstract
{
    public static function remove()
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists("post_tags");
        Schema::dropIfExists("post_categories");
        Schema::dropIfExists("posts");
        Schema::dropIfExists("categories");
        Schema::dropIfExists("tags");
        Schema::dropIfExists("posts_translations");
        Schema::dropIfExists("categories_translations");
        Schema::dropIfExists("tags_translations");

        app(DashboardWidgetInterface::class)->deleteBy([
            "name" => "widget_posts_recent",
        ]);

        app(MenuNodeInterface::class)->deleteBy([
            "reference_type" => Category::class,
        ]);
        app(MenuNodeInterface::class)->deleteBy([
            "reference_type" => Tag::class,
        ]);
    }
}
