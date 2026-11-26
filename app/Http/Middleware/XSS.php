<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Artisan;
use Wikigods\LaravelInstaller\Helpers\MigrationsHelper;

class XSS
{
    use MigrationsHelper;

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if(\Auth::check())
        {
            \App::setLocale(\Auth::user()->lang);

            if(\Auth::user()->type == 'admin')
            {
                $numberOfUpdatesPending = count($this->getPendingMigrations());

                if($numberOfUpdatesPending > 0)
                {
                    Artisan::call('cache:forget spatie.permission.cache');
                    Artisan::call('cache:clear');

                    return redirect()->route('LaravelUpdater::welcome');
                }
            }
        }

        $input = $request->all();
        array_walk_recursive(
            $input, function (&$input){
            if(is_string($input))
            {
                $input = strip_tags($input);
            }
        }
        );
        $request->merge($input);

        return $next($request);
    }
}
