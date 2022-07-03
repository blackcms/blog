<?php

namespace BlackCMS\Blog\Http\Controllers;

use BlackCMS\ACL\Models\User;
use BlackCMS\Base\Events\BeforeEditContentEvent;
use BlackCMS\Base\Events\CreatedContentEvent;
use BlackCMS\Base\Events\DeletedContentEvent;
use BlackCMS\Base\Events\UpdatedContentEvent;
use BlackCMS\Base\Forms\FormBuilder;
use BlackCMS\Base\Http\Controllers\BaseController;
use BlackCMS\Base\Http\Responses\BaseHttpResponse;
use BlackCMS\Base\Traits\HasDeleteManyItemsTrait;
use BlackCMS\Blog\Forms\PostForm;
use BlackCMS\Blog\Http\Requests\PostRequest;
use BlackCMS\Blog\Models\Post;
use BlackCMS\Blog\Repositories\Interfaces\CategoryInterface;
use BlackCMS\Blog\Repositories\Interfaces\PostInterface;
use BlackCMS\Blog\Repositories\Interfaces\TagInterface;
use BlackCMS\Blog\Services\StoreCategoryService;
use BlackCMS\Blog\Services\StoreTagService;
use BlackCMS\Blog\Tables\PostTable;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

class PostController extends BaseController
{
    use HasDeleteManyItemsTrait;

    /**
     * @var PostInterface
     */
    protected $postRepository;

    /**
     * @var TagInterface
     */
    protected $tagRepository;

    /**
     * @var CategoryInterface
     */
    protected $categoryRepository;

    /**
     * @param PostInterface $postRepository
     * @param TagInterface $tagRepository
     * @param CategoryInterface $categoryRepository
     */
    public function __construct(
        PostInterface $postRepository,
        TagInterface $tagRepository,
        CategoryInterface $categoryRepository
    ) {
        $this->postRepository = $postRepository;
        $this->tagRepository = $tagRepository;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @param PostTable $dataTable
     * @return Factory|View
     * @throws Throwable
     */
    public function index(PostTable $dataTable)
    {
        page_title()->setTitle(trans("addons/blog::posts.menu_name"));

        return $dataTable->renderTable();
    }

    /**
     * @param FormBuilder $formBuilder
     * @return string
     */
    public function create(FormBuilder $formBuilder)
    {
        page_title()->setTitle(trans("addons/blog::posts.create"));

        return $formBuilder->create(PostForm::class)->renderForm();
    }

    /**
     * @param PostRequest $request
     * @param StoreTagService $tagService
     * @param StoreCategoryService $categoryService
     * @param BaseHttpResponse $response
     * @return BaseHttpResponse
     */
    public function store(
        PostRequest $request,
        StoreTagService $tagService,
        StoreCategoryService $categoryService,
        BaseHttpResponse $response
    ) {
        /**
         * @var Post $post
         */
        $post = $this->postRepository->createOrUpdate(
            array_merge($request->input(), [
                "author_id" => Auth::id(),
                "author_type" => User::class,
            ])
        );

        event(new CreatedContentEvent(POST_MODULE_NAME, $request, $post));

        $tagService->execute($request, $post);

        $categoryService->execute($request, $post);

        return $response
            ->setPreviousUrl(route("posts.index"))
            ->setNextUrl(route("posts.edit", $post->id))
            ->setMessage(trans("core/base::notices.create_success_message"));
    }

    /**
     * @param int $id
     * @param FormBuilder $formBuilder
     * @param Request $request
     * @return string
     */
    public function edit($id, FormBuilder $formBuilder, Request $request)
    {
        $post = $this->postRepository->findOrFail($id);

        event(new BeforeEditContentEvent($request, $post));

        page_title()->setTitle(
            trans("addons/blog::posts.edit") . ' "' . $post->name . '"'
        );

        return $formBuilder
            ->create(PostForm::class, ["model" => $post])
            ->renderForm();
    }

    /**
     * @param int $id
     * @param PostRequest $request
     * @param StoreTagService $tagService
     * @param StoreCategoryService $categoryService
     * @param BaseHttpResponse $response
     * @return BaseHttpResponse
     */
    public function update(
        $id,
        PostRequest $request,
        StoreTagService $tagService,
        StoreCategoryService $categoryService,
        BaseHttpResponse $response
    ) {
        $post = $this->postRepository->findOrFail($id);

        $post->fill($request->input());

        $this->postRepository->createOrUpdate($post);

        event(new UpdatedContentEvent(POST_MODULE_NAME, $request, $post));

        $tagService->execute($request, $post);

        $categoryService->execute($request, $post);

        return $response
            ->setPreviousUrl(route("posts.index"))
            ->setMessage(trans("core/base::notices.update_success_message"));
    }

    /**
     * @param int $id
     * @param Request $request
     * @return BaseHttpResponse
     */
    public function destroy($id, Request $request, BaseHttpResponse $response)
    {
        try {
            $post = $this->postRepository->findOrFail($id);
            $this->postRepository->delete($post);

            event(new DeletedContentEvent(POST_MODULE_NAME, $request, $post));

            return $response->setMessage(
                trans("core/base::notices.delete_success_message")
            );
        } catch (Exception $exception) {
            return $response->setError()->setMessage($exception->getMessage());
        }
    }

    /**
     * @param Request $request
     * @param BaseHttpResponse $response
     * @return BaseHttpResponse
     * @throws Exception
     */
    public function deletes(Request $request, BaseHttpResponse $response)
    {
        return $this->executeDeleteItems(
            $request,
            $response,
            $this->postRepository,
            POST_MODULE_NAME
        );
    }

    /**
     * @param Request $request
     * @param BaseHttpResponse $response
     * @return BaseHttpResponse
     * @throws Throwable
     */
    public function getWidgetRecentPosts(
        Request $request,
        BaseHttpResponse $response
    ) {
        $limit = (int) $request->input("paginate", 10);
        $limit = $limit > 0 ? $limit : 10;

        $posts = $this->postRepository->advancedGet([
            "with" => ["slugable"],
            "order_by" => ["created_at" => "desc"],
            "paginate" => [
                "per_page" => $limit,
                "current_paged" => (int) $request->input("page", 1),
            ],
        ]);

        return $response->setData(
            view(
                "addons/blog::posts.widgets.posts",
                compact("posts", "limit")
            )->render()
        );
    }
}
