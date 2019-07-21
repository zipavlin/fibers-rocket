<?php
/*
|--------------------------------------------------------------------------
| Fibers make:route command [php artisan fibers:make:route <Title> [Options]]
|--------------------------------------------------------------------------
|
| This command will create a new model and optionally continue to
| creating a migration. It tries to speed up model creation by automatically
| filling usual boilerplate parts.
|
| TODO: add docs
*/

namespace Fibers\Rocket\Commands;

use Fibers\Rocket\Command;
use Fibers\Helper\Facades\TemplateHelper;
use Illuminate\Support\Str;

class Route extends Command
{
    protected $signature =  'fibers:make:route
                            {title : Title of route}
                            {--C|controller : Create controller file as well }
                            {--target= : Target model }
                            {--only= : Comma separated collection of controller methods }
                            {--except= : Comma separated collection of ignored controller methods }';
    protected $description = 'Create Laravel model';

    private $route_title; // ClassTitle
    private $route_target;
    private $route_only_actions; // only methods
    private $route_except_actions; // except methods
    private $route_controller;
    private $route_file;

    /**
     * Command closure
     * Get user input, normalize it, parse it to model data, create model file
     */
    public function handle()
    {
        parent::handle();

        // collect input
        $this->collectInput();

        // create controller
        $this->success = $this->createRoute();

        // continue to other commands
        $this->continue("controller", [
            "title" => $this->route_title,
            "--target" => $this->route_target,
            "--ignore" => ['route'],
            "--only" => !blank($this->route_only_actions) ? $this->route_only_actions->join(",") : false,
            "--except" => !blank($this->route_except_actions) ? $this->route_except_actions->join(",") : false
        ]);
    }

    /**
     * Collect data and create new controller file
     * @return bool
     */
    private function createRoute(): bool
    {
        // build route string
        $route = "Route::resource('{$this->route_title}', '{$this->route_controller}')";
        $route .= $this->actions();
        $route .= $this->parameters();
        $route .= ";";

        // append to config(fibers-rocket.routes).php routes
        $this->files->append("routes/{$this->route_file}", "\n" . $route);

        // output to user
        $this->infoDelayed("Route added [routes/{$this->route_file}]");

        return true;
    }

    private function collectInput()
    {
        // collect routes file
        $this->route_file = config('fibers.routes').'.php';

        // collect title
        $this->route_title = Str::slug(Str::singular($this->argument('title')));

        // collect target
        $this->route_target = Str::snake(class_basename($this->getTarget($this->route_title)));

        // collect controller
        $this->route_controller = Str::finish(Str::studly($this->route_target), "Controller");

        // collect only & except collection
        $this->route_only_actions = $this->getOnlyActions($this->route_target);
        $this->route_except_actions = $this->getExceptActions($this->route_target);
    }

    private function actions () {
        // collect actions
        $actionPossibilities = collect(['index', 'show', 'create', 'store', 'edit', 'update', 'destroy']);
        $actions = $actionPossibilities
            ->when($this->route_only_actions->count(), function ($collection) {
                return $collection->intersect($this->route_only_actions)->values();
            })
            ->diff($this->route_except_actions);

        // try to decide if we should use 'only', 'except' or none
        if ($actions->count() === $actionPossibilities->count()) {
            return "";
        }
        // show only
        elseif ($actions->count() <= 4) {
            return "->only(" . TemplateHelper::array($actions) . ")";
        }
        // show except
        else {
            return "->except(" . TemplateHelper::array($actions) . ")";
        }
    }

    private function parameters ()
    {
        return "->parameters(['{$this->route_title}' => '{$this->route_target}'])";
    }
}
