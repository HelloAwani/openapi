<?php

namespace Service\Providers;

use Illuminate\Support\ServiceProvider;

class InterfaceBindingServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('Service\Interfaces\ItemCategory','Service\Repositories\Eloquent\ItemCategory');
        $this->app->bind('Service\Interfaces\Item','Service\Repositories\Eloquent\Item');
        $this->app->bind('Service\Interfaces\Branch','Service\Repositories\Eloquent\Branch');
        $this->app->bind('Service\Interfaces\Shift','Service\Repositories\Eloquent\Shift');
        $this->app->bind('Service\Interfaces\Permission','Service\Repositories\Eloquent\Permission');
        $this->app->bind('Service\Interfaces\UserType','Service\Repositories\Eloquent\UserType');
        $this->app->bind('Service\Interfaces\UserTypePermission','Service\Repositories\Eloquent\UserTypePermission');
        $this->app->bind('Service\Interfaces\Staff','Service\Repositories\Eloquent\Staff');
        $this->app->bind('Service\Interfaces\Meta','Service\Repositories\Eloquent\Meta');
        $this->app->bind('Service\Interfaces\Space','Service\Repositories\Eloquent\Space');
        $this->app->bind('Service\Interfaces\SubService','Service\Repositories\Eloquent\SubService');
        $this->app->bind('Service\Interfaces\Discount','Service\Repositories\Eloquent\Discount');
        $this->app->bind('Service\Interfaces\General','Service\Repositories\Eloquent\General');
        $this->app->bind('Service\Interfaces\ServiceUsage','Service\Repositories\Eloquent\ServiceUsage');
        $this->app->bind('Service\Interfaces\ItemConversion','Service\Repositories\Eloquent\ItemConversion');
    }
}
