<?php

namespace BlackCMS\Blog\Models;

use BlackCMS\Base\Models\BaseModel;

class PostTranslation extends BaseModel
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = "posts_translations";

    /**
     * @var array
     */
    protected $fillable = [
        "lang_code",
        "posts_id",
        "name",
        "description",
        "content",
    ];

    /**
     * @var bool
     */
    public $timestamps = false;
}
