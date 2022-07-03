<?php

namespace BlackCMS\Blog\Services;

use BlackCMS\Base\Enums\BaseStatusEnum;
use BlackCMS\Base\Supports\Helper;
use BlackCMS\Blog\Models\Category;
use BlackCMS\Blog\Models\Post;
use BlackCMS\Blog\Models\Tag;
use BlackCMS\Blog\Repositories\Interfaces\CategoryInterface;
use BlackCMS\Blog\Repositories\Interfaces\PostInterface;
use BlackCMS\Blog\Repositories\Interfaces\TagInterface;
use BlackCMS\Seo\SeoOpenGraph;
use BlackCMS\Slug\Models\Slug;
use Eloquent;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use MediaManagement;
use Seo;
use Theme;

class BlogService
{
    /**
     * @param Slug $slug
     * @return array|Eloquent
     */
    public function handleFrontRoutes($slug)
    {
        if (!$slug instanceof Eloquent) {
            return $slug;
        }

        $condition = [
            "id" => $slug->reference_id,
            "status" => BaseStatusEnum::PUBLISHED,
        ];

        if (Auth::check() && request()->input("preview")) {
            Arr::forget($condition, "status");
        }

        switch ($slug->reference_type) {
            case Post::class:
                $post = app(PostInterface::class)->getFirstBy(
                    $condition,
                    ["*"],
                    [
                        "categories",
                        "tags",
                        "slugable",
                        "categories.slugable",
                        "tags.slugable",
                    ]
                );

                if (empty($post)) {
                    abort(404);
                }

                Helper::handleViewCount($post, "viewed_post");

                Seo::setTitle($post->name)->setDescription($post->description);

                $meta = new SeoOpenGraph();
                if ($post->image) {
                    $meta->setImage(MediaManagement::getImageUrl($post->image));
                }
                $meta->setDescription($post->description);
                $meta->setUrl($post->url);
                $meta->setTitle($post->name);
                $meta->setType("article");

                Seo::setSeoOpenGraph($meta);

                if (function_exists("admin_bar") &&
                    Auth::check() &&
                    Auth::user()->hasPermission("posts.edit")
                ) {
                    admin_bar()->registerLink(
                        trans("addons/blog::posts.edit_this_post"),
                        route("posts.edit", $post->id)
                    );
                }

                Theme::breadcrumb()->add(__("Home"), route("public.index"));

                $category = $post->categories->sortByDesc("id")->first();
                if ($category) {
                    if ($category->parents->count()) {
                        foreach ($category->parents as $parentCategory) {
                            Theme::breadcrumb()->add(
                                $parentCategory->name,
                                $parentCategory->url
                            );
                        }
                    }

                    Theme::breadcrumb()->add($category->name, $category->url);
                }

                Theme::breadcrumb()->add(Seo::getTitle(), $post->url);

                do_action(
                    BASE_ACTION_PUBLIC_RENDER_SINGLE,
                    POST_MODULE_NAME,
                    $post
                );

                return [
                    "view" => "post",
                    "default_view" => "addons/blog::themes.post",
                    "data" => compact("post"),
                    "slug" => $post->slug,
                ];
            case Category::class:
                $category = app(CategoryInterface::class)->getFirstBy(
                    $condition,
                    ["*"],
                    ["slugable"]
                );

                if (empty($category)) {
                    abort(404);
                }

                Seo::setTitle($category->name)->setDescription(
                    $category->description
                );

                $meta = new SeoOpenGraph();
                if ($category->image) {
                    $meta->setImage(
                        MediaManagement::getImageUrl($category->image)
                    );
                }
                $meta->setDescription($category->description);
                $meta->setUrl($category->url);
                $meta->setTitle($category->name);
                $meta->setType("article");

                Seo::setSeoOpenGraph($meta);

                if (function_exists("admin_bar") &&
                    Auth::check() &&
                    Auth::user()->hasPermission("categories.edit")
                ) {
                    admin_bar()->registerLink(
                        trans("addons/blog::categories.edit_this_category"),
                        route("categories.edit", $category->id)
                    );
                }

                $allRelatedCategoryIds = $category->getChildrenIds($category, [
                    $category->id,
                ]);

                $posts = app(PostInterface::class)->getByCategory(
                    $allRelatedCategoryIds,
                    (int) theme_option("number_of_posts_in_a_category", 12)
                );

                Theme::breadcrumb()->add(__("Home"), route("public.index"));

                if ($category->parents->count()) {
                    foreach ($category->parents->reverse() as $parentCategory) {
                        Theme::breadcrumb()->add(
                            $parentCategory->name,
                            $parentCategory->url
                        );
                    }
                }

                Theme::breadcrumb()->add(Seo::getTitle(), $category->url);

                do_action(
                    BASE_ACTION_PUBLIC_RENDER_SINGLE,
                    CATEGORY_MODULE_NAME,
                    $category
                );

                return [
                    "view" => "category",
                    "default_view" => "addons/blog::themes.category",
                    "data" => compact("category", "posts"),
                    "slug" => $category->slug,
                ];
            case Tag::class:
                $tag = app(TagInterface::class)->getFirstBy(
                    $condition,
                    ["*"],
                    ["slugable"]
                );

                if (!$tag) {
                    abort(404);
                }

                Seo::setTitle($tag->name)->setDescription($tag->description);

                $meta = new SeoOpenGraph();
                $meta->setDescription($tag->description);
                $meta->setUrl($tag->url);
                $meta->setTitle($tag->name);
                $meta->setType("article");

                if (function_exists("admin_bar") &&
                    Auth::check() &&
                    Auth::user()->hasPermission("tags.edit")
                ) {
                    admin_bar()->registerLink(
                        trans("addons/blog::tags.edit_this_tag"),
                        route("tags.edit", $tag->id)
                    );
                }

                $posts = get_posts_by_tag(
                    $tag->id,
                    (int) theme_option("number_of_posts_in_a_tag", 12)
                );

                Theme::breadcrumb()
                    ->add(__("Home"), route("public.index"))
                    ->add(Seo::getTitle(), $tag->url);

                do_action(
                    BASE_ACTION_PUBLIC_RENDER_SINGLE,
                    TAG_MODULE_NAME,
                    $tag
                );

                return [
                    "view" => "tag",
                    "default_view" => "addons/blog::themes.tag",
                    "data" => compact("tag", "posts"),
                    "slug" => $tag->slug,
                ];
        }

        return $slug;
    }
}
