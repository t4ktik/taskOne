<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
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
        // Chatify 1.0 builds the attachment path from the URL segment.
        // Reject names that leave the attachments directory.
        Event::listen(\Illuminate\Routing\Events\RouteMatched::class, function ($event) {
            if($event->route->getName() !== config('chatify.attachments.route'))
            {
                return;
            }

            $fileName = $event->route->parameter('fileName');
            $folder   = storage_path('app/public/' . trim(config('chatify.attachments.folder'), '/'));

            if(!is_string($fileName) || $fileName === '' || basename($fileName) !== $fileName)
            {
                abort(404);
            }

            $path = $folder . DIRECTORY_SEPARATOR . $fileName;
            $realFolder = realpath($folder);
            $realPath   = realpath($path);

            if($realFolder === false || $realPath === false || strpos($realPath, $realFolder . DIRECTORY_SEPARATOR) !== 0)
            {
                abort(404);
            }
        });
    }
}
