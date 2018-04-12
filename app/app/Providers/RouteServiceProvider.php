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
        $this->mapWebRoutes();



        //add Device routes
        $this->mapApiDeviceRoutes("v1");
        $this->mapApiDeviceRoutes("v2");
        $this->mapApiDeviceRoutes("v3");

        //
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::group([
            'middleware' => 'api',
            'namespace' => $this->namespaceWeb,
            'prefix' => 'web',
        ], function ($router) {
            require base_path('routes/web.php');
        });
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
//    protected function mapApiRoutes()
//    {
//        Route::group([
//            'middleware' => 'api',
//            'namespace' => $this->namespaceWeb,
//            'prefix' => 'web',
//        ], function ($router) {
//            require base_path('routes/api.php');
//        });
//    }
    
    protected function mapApiDeviceRoutes($version)
    {
        $name = "Service\Http\Controllers\Device\\".$version;
        $this->device_version = $version;
        Route::group([
            'namespace' => $name,
            'prefix' => 'device',
        ], function ($router) {
            require base_path('routes/device/'.$this->device_version.'.php');
        });
    }
}
