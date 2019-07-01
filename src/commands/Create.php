<?php
/*
|--------------------------------------------------------------------------
| Fibers create command [php artisan fibers:create <Title> [Options]]
|--------------------------------------------------------------------------
|
| This command will create a (full) mvc package of files. It runs multiple
| Rocket commands in sequence and is therefore the fastest way to scaffold
| a new model with all connected Laravel parts. Keep in mind that all parts
| can also be scaffolded separately using relevant commands.
|
*/

namespace Fibers\Rocket\Commands;

use Fibers\Rocket\Command;
use Illuminate\Support\Str;

class Create extends Command
{
    protected $signature =  'fibers:create
                            {title : Title of model}
                            {--mvc : Create a minimal model + view + controller scaffolding }
                            {--all : Create a full model + view + controller + guard + route + (admin) scaffolding }
                            {--M|model : Create model file as well }
                            {--C|controller : Create controller file as well }
                            {--L|layout : Create layout view files as well }
                            {--G|guard : Create and use guarded request }
                            {--R|route : Add controller to routes }
                            {--A|admin : Scaffold admin interface }
                            {--paginated : Create a paginated index }
                            {--bootstrap : Use bootstrap in layout creation }
                            {--only= : Comma separated collection of controller methods }
                            {--except= : Comma separated collection of ignored controller methods }';
    protected $description = 'Create Laravel model';

    protected $create_title;
    protected $create_attributes;
    protected $create_commands;
    protected $create_paginated;
    protected $create_bootstrap;
    protected $create_only_actions;
    protected $create_except_actions;
    protected $create_skip_questions = false;

    /**
     * Command closure
     * Get user input, normalize it, parse it to model data, create model file
     */
    public function handle()
    {
        parent::handle();

        // collect input
        $this->collectInput();

        // run commands
        $this->runCommand("model", [
            "--input" => $this->create_attributes,
            "--silent" => true,
            "--force" => true,
            "--migration" => true,
        ]);
        $this->runCommand("controller", [
            '--paginated' => $this->create_paginated,
            '--target' => $this->create_title,
            '--only' => $this->create_only_actions->join(','),
            '--except' => $this->create_except_actions->join(','),
            '--guard' => $this->create_commands->search('guard'),
            '--route' => $this->create_commands->search('route'),
            '--ignore' => ['layout', 'model', 'guard', 'route']
        ]);
        $this->runCommand("guard");
        $this->runCommand("route", [
            '--target' => $this->create_title,
            '--only' => $this->create_only_actions->join(','),
            '--except' => $this->create_except_actions->join(','),
            '--silent' => true,
            '--ignore' => ['controller']
        ]);
        $this->runCommand("layout", [
            '--paginated' => $this->create_paginated,
            '--bootstrap' => $this->create_bootstrap,
            '--target' => $this->create_title,
            '--only' => $this->create_only_actions->join(','),
            '--except' => $this->create_except_actions->join(','),
            '--silent' => true,
            '--ignore' => ['controller']
        ]);

        if ($this->option("all") or $this->option('mvc')) {
            $this->infoDelayed("Visit ".url(Str::slug($this->create_title)), "info");
        }
    }

    private function runCommand($name, $options = [])
    {
        $prompt = $prompt ?? "Would you like to create $name?";
        if (in_array($name, $this->create_commands->toArray()) or (!$this->create_skip_questions and !$this->silent and $this->confirm($prompt, "yes"))) {
            $this->call("fibers:$name", array_merge([
                "title" => $this->create_title,
                "--force" => $this->force,
                "--silent" => $this->silent
            ], $options));
        }
    }

    private function collectInput (): void
    {
        // set title
        $this->create_title = Str::studly(class_basename($this->argument("title")));

        // collect input
        $this->create_attributes = $this->attributeInput("model");

        // set options
        $this->setOptions();

        // set bootstrap option
        if ($this->create_commands->search('layout')) {
            $this->create_bootstrap = $this->option('bootstrap') ?: (config('fibers.bootstrap') === true ?: (!$this->silent ? $this->confirm("Do you want to use create bootstrap based views?") : false));
        }

        // set paginated option
        if ($this->create_commands->search('layout') or $this->create_commands->search('controller')) {
            $this->create_paginated = $this->option('paginated') ?: (config('fibers.paginated') === true ?: (!$this->silent ? $this->confirm("Do you want your controller/index view to be paginated?") : false));
        }

        // set only actions
        $this->create_only_actions = collect();
        if ($this->create_commands->search('layout') or $this->create_commands->search('controller') or $this->create_commands->search('route')) {
            $this->create_only_actions = $this->getOnlyActions($this->create_title);
        } elseif (!$this->create_commands->count() and !$this->silent) {
            $this->create_only_actions = collect(explode(",", $this->ask("What resource actions would you like to include? [<comment>comma separated</comment>]")))->map(function ($item) {
                return trim($item);
            })->filter(function ($item) {
                return !blank($item);
            });
        }

        // set except actions
        $this->create_except_actions = collect();
        if ($this->create_commands->search('layout') or $this->create_commands->search('controller') or $this->create_commands->search('route')) {
            $this->create_except_actions = $this->getExceptActions($this->create_title);
        } elseif (!$this->create_commands->count() and !$this->silent) {
            $this->create_except_actions = collect(explode(",", $this->ask("What resource actions would you like to exclude? [<comment>comma separated</comment>]")))->map(function ($item) {
                return trim($item);
            })->filter(function ($item) {
                return !blank($item);
            });
        }
    }

    private function setOptions (): void
    {
        $this->create_commands = collect();
        if ($this->option('all')) {
            $this->create_commands->push('model');
            $this->create_commands->push('controller');
            $this->create_commands->push('layout');
            $this->create_commands->push('guard');
            $this->create_commands->push('route');
            $this->create_commands->push('admin');
            $this->create_skip_questions = true;
        }
        elseif ($this->option('mvc')) {
            $this->create_commands->push('model');
            $this->create_commands->push('controller');
            $this->create_commands->push('layout');
            $this->create_skip_questions = true;
        }
        else {
            if ($this->option('model')) $this->create_commands->push('model');
            if ($this->option('controller')) $this->create_commands->push('controller');
            if ($this->option('layout')) $this->create_commands->push('layout');
            if ($this->option('guard')) $this->create_commands->push('guard');
            if ($this->option('route')) $this->create_commands->push('route');
            if ($this->option('admin')) $this->create_commands->push('admin');
        }
        $this->create_commands = $this->create_commands->unique();
    }
}
