<?php

namespace BlackCMS\Blog\Repositories\Eloquent;

use BlackCMS\Base\Enums\BaseStatusEnum;
use BlackCMS\Blog\Repositories\Interfaces\TagInterface;
use BlackCMS\Support\Repositories\Eloquent\RepositoriesAbstract;

class TagRepository extends RepositoriesAbstract implements TagInterface
{
    /**
     * {@inheritDoc}
     */
    public function getDataSiteMap()
    {
        $data = $this->model
            ->with("slugable")
            ->where("status", BaseStatusEnum::PUBLISHED)
            ->orderBy("created_at", "desc");

        return $this->applyBeforeExecuteQuery($data)->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getPopularTags(
        $limit,
        array $with = ["slugable"],
        array $withCount = ["posts"]
    ) {
        $data = $this->model
            ->with($with)
            ->withCount($withCount)
            ->orderBy("posts_count", "DESC")
            ->limit($limit);

        return $this->applyBeforeExecuteQuery($data)->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getAllTags($active = true)
    {
        $data = $this->model;
        if ($active) {
            $data = $data->where("status", BaseStatusEnum::PUBLISHED);
        }

        return $this->applyBeforeExecuteQuery($data)->get();
    }
}
