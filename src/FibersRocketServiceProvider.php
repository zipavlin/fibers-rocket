<?php

namespace Fibers\Rocket;

use Fibers\Rocket\Commands\Create;
use Illuminate\Support\ServiceProvider;
use Fibers\Rocket\Commands\App;
use Fibers\Rocket\Commands\Controller;
use Fibers\Rocket\Commands\Guard;
use Fibers\Rocket\Commands\Language;
use Fibers\Rocket\Commands\Layout;
use Fibers\Rocket\Commands\Route;
use Fibers\Rocket\Commands\Migration;
use Fibers\Rocket\Commands\Model;
use Fibers\Rocket\Commands\Pivot;

class FibersRocketServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                App::class,
                Model::class,
                Migration::class,
                Pivot::class,
                Controller::class,
                Guard::class,
                Route::class,
                Layout::class,
                Language::class,
                Create::class,
            ]);
        }
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // define root folder
        define('FIBERS_ROCKET', __DIR__);
    }
}
