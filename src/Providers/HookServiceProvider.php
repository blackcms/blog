<?php

namespace BlackCMS\Blog\Providers;

use Assets;
use BlackCMS\Base\Enums\BaseStatusEnum;
use BlackCMS\Base\Models\BaseModel;
use BlackCMS\Blog\Models\Category;
use BlackCMS\Blog\Models\Post;
use BlackCMS\Blog\Models\Tag;
use BlackCMS\Blog\Services\BlogService;
use BlackCMS\Dashboard\Supports\WidgetInstance as WidgetInstance;
use BlackCMS\Page\Models\Page;
use BlackCMS\Page\Repositories\Interfaces\PageInterface;
use Eloquent;
use Html;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Menu;
use MediaManagement;
use stdClass;
use Theme;

class HookServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if (defined("MENU_ACTION_SIDEBAR_OPTIONS")) {
            Menu::addMenuOptionModel(Category::class);
            Menu::addMenuOptionModel(Tag::class);
            add_action(
                MENU_ACTION_SIDEBAR_OPTIONS,
                [$this, "registerMenuOptions"],
                2
            );
        }
        add_filter(
            DASHBOARD_FILTER_ADMIN_LIST,
            [$this, "registerDashboardWidgets"],
            21,
            2
        );
        add_filter(
            BASE_FILTER_PUBLIC_SINGLE_DATA,
            [$this, "handleSingleView"],
            2
        );
        if (defined("PAGE_MODULE_NAME")) {
            add_filter(
                PAGE_FILTER_FRONT_PAGE_CONTENT,
                [$this, "renderBlogPage"],
                2,
                2
            );
            add_filter(
                PAGE_FILTER_PAGE_NAME_IN_ADMIN_LIST,
                [$this, "addAdditionNameToPageName"],
                147,
                2
            );
        }

        if (function_exists("admin_bar")) {
            Event::listen(RouteMatched::class, function () {
                admin_bar()->registerLink(
                    trans("addons/blog::posts.post"),
                    route("posts.create"),
                    "add-new"
                );
            });
        }

        if (function_exists("add_shortcode")) {
            add_shortcode(
                "blog-posts",
                trans("addons/blog::base.short_code_name"),
                trans("addons/blog::base.short_code_description"),
                [$this, "renderBlogPosts"]
            );
            shortcode()->setAdminConfig("blog-posts", function (
                $attributes,
                $content
            ) {
                return view(
                    "addons/blog::partials.posts-short-code-admin-config",
                    compact("attributes", "content")
                )->render();
            });
        }

        if (function_exists("theme_option")) {
            add_action(
                RENDERING_THEME_OPTIONS_PAGE,
                [$this, "addThemeOptions"],
                35
            );
        }

        if (defined("LANGUAGE_MODULE_NAME")) {
            add_action(
                BASE_ACTION_META_BOXES,
                [$this, "addLanguageChooser"],
                55,
                2
            );
        }

        if (defined("THEME_FRONT_HEADER")) {
            add_action(
                BASE_ACTION_PUBLIC_RENDER_SINGLE,
                function ($screen, $post) {
                    add_filter(
                        THEME_FRONT_HEADER,
                        function ($html) use ($post) {
                            if (get_class($post) != Post::class) {
                                return $html;
                            }

                            $schema = [
                                "@context" => "https://schema.org",
                                "@type" => "NewsArticle",
                                "mainEntityOfPage" => [
                                    "@type" => "WebPage",
                                    "@id" => $post->url,
                                ],
                                "headline" => clean($post->name),
                                "description" => clean($post->description),
                                "image" => [
                                    "@type" => "ImageObject",
                                    "url" => MediaManagement::getImageUrl(
                                        $post->image,
                                        null,
                                        false,
                                        MediaManagement::getDefaultImage()
                                    ),
                                ],
                                "author" => [
                                    "@type" => "Person",
                                    "url" => route("public.index"),
                                    "name" => $post->author->name,
                                ],
                                "publisher" => [
                                    "@type" => "Organization",
                                    "name" => theme_option("site_title"),
                                    "logo" => [
                                        "@type" => "ImageObject",
                                        "url" => MediaManagement::getImageUrl(
                                            theme_option("logo")
                                        ),
                                    ],
                                ],
                                "datePublished" => $post->created_at->toDateString(),
                                "dateModified" => $post->updated_at->toDateString(),
                            ];

                            return $html .
                                Html::tag("script", json_encode($schema), [
                                    "type" => "application/ld+json",
                                ])->toHtml();
                        },
                        35
                    );
                },
                35,
                2
            );
        }
    }

    public function addThemeOptions()
    {
        $pages = $this->app
            ->make(PageInterface::class)
            ->pluck("name", "id", ["status" => BaseStatusEnum::PUBLISHED]);

        theme_option()->setSection([
            "title" => "Blog",
            "desc" => "Theme options for blog",
            "id" => "opt-text-subsection-blog",
            "subsection" => true,
            "icon" => "las la-newspaper la-2x",
            "fields" => [
                [
                    "id" => "blog_page_id",
                    "type" => "customSelect",
                    "label" => trans("addons/blog::base.blog_page_id"),
                    "attributes" => [
                        "name" => "blog_page_id",
                        "list" =>
                            ["" => trans("addons/blog::base.select")] + $pages,
                        "value" => "",
                        "options" => [
                            "class" => "form-control",
                        ],
                    ],
                ],
                [
                    "id" => "number_of_posts_in_a_category",
                    "type" => "number",
                    "label" => trans(
                        "addons/blog::base.number_posts_per_page_in_category"
                    ),
                    "attributes" => [
                        "name" => "number_of_posts_in_a_category",
                        "value" => 12,
                        "options" => [
                            "class" => "form-control",
                        ],
                    ],
                ],
                [
                    "id" => "number_of_posts_in_a_tag",
                    "type" => "number",
                    "label" => trans(
                        "addons/blog::base.number_posts_per_page_in_tag"
                    ),
                    "attributes" => [
                        "name" => "number_of_posts_in_a_tag",
                        "value" => 12,
                        "options" => [
                            "class" => "form-control",
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Register sidebar options in menu
     */
    public function registerMenuOptions()
    {
        if (Auth::user()->hasPermission("categories.index")) {
            Menu::registerMenuOptions(
                Category::class,
                trans("addons/blog::categories.menu")
            );
        }

        if (Auth::user()->hasPermission("tags.index")) {
            Menu::registerMenuOptions(
                Tag::class,
                trans("addons/blog::tags.menu")
            );
        }
    }

    /**
     * @param array $widgets
     * @param Collection $widgetSettings
     * @return array
     */
    public function registerDashboardWidgets($widgets, $widgetSettings)
    {
        if (!Auth::user()->hasPermission("posts.index")) {
            return $widgets;
        }

        Assets::addScriptsDirectly(["/vendor/core/addons/blog/js/blog.js"]);

        return (new WidgetInstance())
            ->setPermission("posts.index")
            ->setKey("widget_posts_recent")
            ->setTitle(trans("addons/blog::posts.widget_posts_recent"))
            ->setIcon("las la-edit la-2x")
            ->setColor("#f3c200")
            ->setRoute(route("posts.widget.recent-posts"))
            ->setBodyClass("scroll-table")
            ->setColumn("col-md-6 col-sm-6")
            ->init($widgets, $widgetSettings);
    }

    /**
     * @param Eloquent $slug
     * @return array|Eloquent
     */
    public function handleSingleView($slug)
    {
        return (new BlogService())->handleFrontRoutes($slug);
    }

    /**
     * @param stdClass $shortcode
     * @return array|string
     */
    public function renderBlogPosts($shortcode)
    {
        $posts = get_all_posts(true, $shortcode->paginate, [
            "slugable",
            "categories",
            "categories.slugable",
            "author",
        ]);

        $view = "addons/blog::themes.templates.posts";
        $themeView = Theme::getThemeNamespace() . "::views.templates.posts";
        if (view()->exists($themeView)) {
            $view = $themeView;
        }

        return view($view, compact("posts"))->render();
    }

    /**
     * @param string|null $content
     * @param Page $page
     * @return array|string|null
     */
    public function renderBlogPage(?string $content, Page $page)
    {
        if ($page->id == theme_option("blog_page_id", setting("blog_page_id"))
        ) {
            $view = "addons/blog::themes.loop";

            if (view()->exists(Theme::getThemeNamespace() . "::views.loop")) {
                $view = Theme::getThemeNamespace() . "::views.loop";
            }

            return view($view, [
                "posts" => get_all_posts(
                    true,
                    theme_option("number_of_posts_in_a_category", 12),
                    ["slugable", "categories", "categories.slugable", "author"]
                ),
            ])->render();
        }

        return $content;
    }

    /**
     * @param string|null $name
     * @param Page $page
     * @return string|null
     */
    public function addAdditionNameToPageName(?string $name, Page $page)
    {
        if ($page->id == theme_option("blog_page_id", setting("blog_page_id"))
        ) {
            $subTitle = Html::tag(
                "span",
                trans("addons/blog::base.blog_page"),
                ["class" => "additional-page-name"]
            )->toHtml();

            if (Str::contains($name, " —")) {
                return $name . ", " . $subTitle;
            }

            return $name . " —" . $subTitle;
        }

        return $name;
    }

    /**
     * @param BaseModel $model
     * @param string $priority
     * @return string
     */
    public function addLanguageChooser($priority, $model)
    {
        if ($priority == "head" && $model instanceof Category) {
            $route = "categories.index";

            if ($route) {
                echo view(
                    "addons/language::partials.admin-list-language-chooser",
                    compact("route")
                )->render();
            }
        }
    }
}
