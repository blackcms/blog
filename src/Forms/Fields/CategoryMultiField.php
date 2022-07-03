<?php

namespace BlackCMS\Blog\Forms\Fields;

use Kris\LaravelFormBuilder\Fields\FormField;

class CategoryMultiField extends FormField
{
    /**
     * {@inheritDoc}
     */
    protected function getTemplate()
    {
        return "addons/blog::categories.categories-multi";
    }
}
