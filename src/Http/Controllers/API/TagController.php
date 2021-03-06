<?php

namespace BlackCMS\Blog\Http\Controllers\API;

use App\Http\Controllers\Controller;
use BlackCMS\Base\Enums\BaseStatusEnum;
use BlackCMS\Base\Http\Responses\BaseHttpResponse;
use BlackCMS\Blog\Http\Resources\TagResource;
use BlackCMS\Blog\Repositories\Interfaces\TagInterface;
use Illuminate\Http\Request;

class TagController extends Controller
{
    /**
     * @var TagInterface
     */
    protected $tagRepository;

    /**
     * AuthenticationController constructor.
     *
     * @param TagInterface $tagRepository
     */
    public function __construct(TagInterface $tagRepository)
    {
        $this->tagRepository = $tagRepository;
    }

    /**
     * List tags
     *
     * @group Blog
     *
     * @param Request $request
     * @param BaseHttpResponse $response
     * @return BaseHttpResponse
     */
    public function index(Request $request, BaseHttpResponse $response)
    {
        $data = $this->tagRepository->advancedGet([
            "with" => ["slugable"],
            "condition" => ["status" => BaseStatusEnum::PUBLISHED],
            "paginate" => [
                "per_page" => (int) $request->input("per_page", 10),
                "current_paged" => (int) $request->input("page", 1),
            ],
        ]);

        return $response
            ->setData(TagResource::collection($data))
            ->toApiResponse();
    }
}
