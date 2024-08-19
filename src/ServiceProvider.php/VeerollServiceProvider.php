<?php

namespace Vendor\Veeroll;

use Illuminate\Support\ServiceProvider;
use Vendor\Veeroll\Services\VideoService;

class VeerollServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('veeroll', function () {
            return new VideoService();
        });
    }

    public function boot()
    {
        // Register other resources like routes, views, etc.
    }
}
