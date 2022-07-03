<?php

namespace BlackCMS\Blog\Http\Controllers\API;

use App\Http\Controllers\Controller;
use BlackCMS\Base\Enums\BaseStatusEnum;
use BlackCMS\Base\Http\Responses\BaseHttpResponse;
use BlackCMS\Blog\Http\Resources\CategoryResource;
use BlackCMS\Blog\Http\Resources\ListCategoryResource;
use BlackCMS\Blog\Models\Category;
use BlackCMS\Blog\Repositories\Interfaces\CategoryInterface;
use BlackCMS\Blog\Supports\FilterCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use SlugHelper;

class CategoryController extends Controller
{
    /**
     * @var CategoryInterface
     */
    protected $categoryRepository;

    /**
     * CategoryController constructor.
     * @param CategoryInterface $categoryRepository
     */
    public function __construct(CategoryInterface $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * List categories
     *
     * @group Blog
     *
     * @param Request $request
     * @param BaseHttpResponse $response
     * @return BaseHttpResponse
     */
    public function index(Request $request, BaseHttpResponse $response)
    {
        $data = $this->categoryRepository->advancedGet([
            "with" => ["slugable"],
            "condition" => ["status" => BaseStatusEnum::PUBLISHED],
            "paginate" => [
                "per_page" => (int) $request->input("per_page", 10),
                "current_paged" => (int) $request->input("page", 1),
            ],
        ]);

        return $response
            ->setData(ListCategoryResource::collection($data))
            ->toApiResponse();
    }

    /**
     * Filters categories
     *
     * @group Blog
     *
     * @param Request $request
     * @param BaseHttpResponse $response
     * @return BaseHttpResponse
     */
    public function getFilters(Request $request, BaseHttpResponse $response)
    {
        $filters = FilterCategory::setFilters($request->input());
        $data = $this->categoryRepository->getFilters($filters);

        return $response
            ->setData(CategoryResource::collection($data))
            ->toApiResponse();
    }

    /**
     * Get category by slug
     *
     * @group Blog
     * @queryParam slug Find by slug of category.
     * @param string $slug
     * @param BaseHttpResponse $response
     * @return BaseHttpResponse|JsonResponse
     */
    public function findBySlug(string $slug, BaseHttpResponse $response)
    {
        $slug = SlugHelper::getSlug(
            $slug,
            SlugHelper::getPrefix(Category::class),
            Category::class
        );

        if (!$slug) {
            return $response
                ->setError()
                ->setCode(404)
                ->setMessage("Not found");
        }

        $category = $this->categoryRepository->getCategoryById(
            $slug->reference_id
        );

        if (!$category) {
            return $response
                ->setError()
                ->setCode(404)
                ->setMessage("Not found");
        }

        return $response
            ->setData(new ListCategoryResource($category))
            ->toApiResponse();
    }
}
