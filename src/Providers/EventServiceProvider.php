<?php

namespace BlackCMS\Blog\Providers;

use BlackCMS\Theme\Events\RenderingSiteMapEvent;
use BlackCMS\Blog\Listeners\RenderingSiteMapListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        RenderingSiteMapEvent::class => [RenderingSiteMapListener::class],
    ];
}
