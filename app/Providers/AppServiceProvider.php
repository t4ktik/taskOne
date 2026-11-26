<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::useBootstrapFour();

        $this->app->booted(function (){
            $routes = Route::getRoutes();
            $routes->refreshNameLookups();
            $route = $routes->getByName((string) config('chatify.attachments.route'));

            if($route)
            {
                $route->middleware('chat.attachment');
            }
        });
    }
}
