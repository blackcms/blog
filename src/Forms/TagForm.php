<?php

namespace BlackCMS\Blog\Forms;

use BlackCMS\Base\Enums\BaseStatusEnum;
use BlackCMS\Base\Forms\FormAbstract;
use BlackCMS\Blog\Http\Requests\TagRequest;
use BlackCMS\Blog\Models\Tag;

class TagForm extends FormAbstract
{
    /**
     * {@inheritDoc}
     */
    public function buildForm()
    {
        $this->setupModel(new Tag())
            ->setValidatorClass(TagRequest::class)
            ->withCustomFields()
            ->add("name", "text", [
                "label" => trans("core/base::forms.name"),
                "label_attr" => ["class" => "control-label required"],
                "attr" => [
                    "placeholder" => trans("core/base::forms.name_placeholder"),
                    "data-counter" => 120,
                ],
            ])
            ->add("description", "textarea", [
                "label" => trans("core/base::forms.description"),
                "label_attr" => ["class" => "control-label"],
                "attr" => [
                    "rows" => 4,
                    "placeholder" => trans(
                        "core/base::forms.description_placeholder"
                    ),
                    "data-counter" => 400,
                ],
            ])
            ->add("status", "customSelect", [
                "label" => trans("core/base::tables.status"),
                "label_attr" => ["class" => "control-label required"],
                "choices" => BaseStatusEnum::labels(),
            ])
            ->setBreakFieldPoint("status");
    }
}
