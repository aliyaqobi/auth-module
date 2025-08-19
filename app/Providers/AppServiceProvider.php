<?php

namespace App\Providers;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
            $this->app['request']->server->set('HTTPS', 'on');
        }

        JsonResource::withoutWrapping();
    }
}
