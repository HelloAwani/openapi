















































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
    protected $namespace = 'Service\Http\Controllers';
    protected $namespaceWeb = 'Service\Http\Controllers\Web';



    protected $device_version = null;

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
        $this->map_routes("auth", "Auth", "v1");
        $this->map_routes("business", "Business", "v1");
        $this->map_routes("fnb", "FNB", "v1");
        $this->map_routes("bfnb", "BFNB", "v1");
        $this->map_routes("retail", "Retail", "v1");
        $this->map_routes("utils", "Utils", "v1");
        $this->map_routes("opentrans", "OpenTransaction", "v1");
    }

    protected function map_routes($prefix, $folder,$version)
    {
        $name = "Service\Http\Controllers\\".$folder."\\".$version;
        $this->app_version = $version;
        $this->app_prefix = $prefix;
        Route::group([
            'namespace' => $name,
            'prefix' => $this->app_prefix,
        ], function ($router) {
            require base_path("routes/".$this->app_prefix."/".$this->app_version.'.php');
        });
    }
}