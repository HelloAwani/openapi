<?php

namespace Service\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        //

        parent::boot();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapRoute("v1");
    }

    protected function mapRoute($version)
    {
        $name = "Service\Http\Controllers\\".$version;
        $this->api_version = $version;
        
        Route::group([
            'namespace' => $name,
            'prefix' => $version,
        ], function ($router) {
            require base_path('routes\\'.$this->api_version.'.php');
        });
        
    }
}
