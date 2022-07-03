<?php

namespace BlackCMS\Blog\Providers;

use BlackCMS\LanguageAdvanced\Supports\LanguageAdvancedManager;
use BlackCMS\Shortcode\View\View;
use Illuminate\Routing\Events\RouteMatched;
use BlackCMS\Base\Traits\LoadAndPublishDataTrait;
use BlackCMS\Blog\Models\Post;
use BlackCMS\Blog\Repositories\Caches\PostCacheDecorator;
use BlackCMS\Blog\Repositories\Eloquent\PostRepository;
use BlackCMS\Blog\Repositories\Interfaces\PostInterface;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use BlackCMS\Blog\Models\Category;
use BlackCMS\Blog\Repositories\Caches\CategoryCacheDecorator;
use BlackCMS\Blog\Repositories\Eloquent\CategoryRepository;
use BlackCMS\Blog\Repositories\Interfaces\CategoryInterface;
use BlackCMS\Blog\Models\Tag;
use BlackCMS\Blog\Repositories\Caches\TagCacheDecorator;
use BlackCMS\Blog\Repositories\Eloquent\TagRepository;
use BlackCMS\Blog\Repositories\Interfaces\TagInterface;
use Language;
use Note;
use Seo;
use SlugHelper;

/**
 * @since 02/07/2016 09:50 AM
 */
class BlogServiceProvider extends ServiceProvider
{
    use LoadAndPublishDataTrait;

    public function register()
    {
        $this->app->bind(PostInterface::class, function () {
            return new PostCacheDecorator(new PostRepository(new Post()));
        });

        $this->app->bind(CategoryInterface::class, function () {
            return new CategoryCacheDecorator(
                new CategoryRepository(new Category())
            );
        });

        $this->app->bind(TagInterface::class, function () {
            return new TagCacheDecorator(new TagRepository(new Tag()));
        });
    }

    public function boot()
    {
        SlugHelper::registerModule(Post::class, "Blog Posts");
        SlugHelper::registerModule(Category::class, "Blog Categories");
        SlugHelper::registerModule(Tag::class, "Blog Tags");

        SlugHelper::setPrefix(Tag::class, "tag", true);
        SlugHelper::setPrefix(Post::class, null, true);
        SlugHelper::setPrefix(Category::class, null, true);

        $this->setNamespace("addons/blog")
            ->loadHelpers()
            ->loadAndPublishConfigurations(["permissions", "general"])
            ->loadAndPublishViews()
            ->loadAndPublishTranslations()
            ->loadRoutes(["web", "api"])
            ->loadMigrations()
            ->publishAssets();

        $this->app->register(EventServiceProvider::class);

        Event::listen(RouteMatched::class, function () {
            dashboard_menu()
                ->registerItem([
                    "id" => "cms-addons-blog",
                    "priority" => 320,
                    "parent_id" => null,
                    "name" => "addons/blog::base.menu_name",
                    "icon" => "las la-newspaper la-2x",
                    "url" => route("posts.index"),
                    "permissions" => ["posts.index"],
                ])
                ->registerItem([
                    "id" => "cms-addons-blog-post",
                    "priority" => 1,
                    "parent_id" => "cms-addons-blog",
                    "name" => "addons/blog::posts.menu_name",
                    "icon" => null,
                    "url" => route("posts.index"),
                    "permissions" => ["posts.index"],
                ])
                ->registerItem([
                    "id" => "cms-addons-blog-categories",
                    "priority" => 2,
                    "parent_id" => "cms-addons-blog",
                    "name" => "addons/blog::categories.menu_name",
                    "icon" => null,
                    "url" => route("categories.index"),
                    "permissions" => ["categories.index"],
                ])
                ->registerItem([
                    "id" => "cms-addons-blog-tags",
                    "priority" => 3,
                    "parent_id" => "cms-addons-blog",
                    "name" => "addons/blog::tags.menu_name",
                    "icon" => null,
                    "url" => route("tags.index"),
                    "permissions" => ["tags.index"],
                ]);
        });

        $useLanguageV2 =
            $this->app["config"]->get(
                "addons.blog.general.use_language",
                false
            ) && defined("LANGUAGE_ADVANCED_MODULE_NAME");

        if (defined("LANGUAGE_MODULE_NAME") && $useLanguageV2) {
            LanguageAdvancedManager::registerModule(Post::class, [
                "name",
                "description",
                "content",
            ]);

            LanguageAdvancedManager::registerModule(Category::class, [
                "name",
                "description",
            ]);

            LanguageAdvancedManager::registerModule(Tag::class, [
                "name",
                "description",
            ]);
        }

        $this->app->booted(function () use ($useLanguageV2) {
            $models = [Post::class, Category::class, Tag::class];

            if (defined("LANGUAGE_MODULE_NAME") && !$useLanguageV2) {
                Language::registerModule($models);
            }

            Seo::registerModule($models);

            $configKey = "packages.revision.general.supported";
            config()->set(
                $configKey,
                array_merge(config($configKey, []), [Post::class])
            );

            if (defined("NOTE_FILTER_MODEL_USING_NOTE")) {
                Note::registerModule(Post::class);
            }

            $this->app->register(HookServiceProvider::class);
        });

        if (function_exists("shortcode")) {
            view()->composer(
                [
                    "addons/blog::themes.post",
                    "addons/blog::themes.category",
                    "addons/blog::themes.tag",
                ],
                function (View $view) {
                    $view->withShortcodes();
                }
            );
        }
    }
}
