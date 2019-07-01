<?php
/*
|--------------------------------------------------------------------------
| Fibers pivot command [php artisan fibers:pivot ModelName1 ModelName2]
|--------------------------------------------------------------------------
|
| This command will create a pivot table from two related models.
|
*/

namespace Fibers\Rocket\Commands;

use Fibers\Rocket\Command;
use Fibers\Helper\Facades\ModelHelper;
use Fibers\Helper\Facades\ModelsHelper;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class Pivot extends Command
{
    protected $signature =  'fibers:pivot
                            {model* : Name of models}
                            {--column=* : Additional pivot columns}';
    protected $description = 'Create Laravel pivot table migration';
    protected $files;
    protected $composer;
    protected $models;
    protected $columns;

    /**
     * Command closure
     * Get user input, normalize it, parse it to model data, create model file
     */
    public function handle(): void
    {
        $this->models = collect($this->argument("model"))->map(function ($model) { return ModelsHelper::class($model); });

        // build table name (both models, sort)
        $table = Str::lower($this->models->map(function ($model) { return class_basename($model); })->sort()->join("_"));

        // check if pivot table exists
        if (!Schema::hasTable($table)) {
            // get migration files
            $migrations = collect(glob(base_path("database/migrations") . "/*.{php,PHP}", GLOB_BRACE))->map(function ($filepath) { return basename($filepath); })->toArray();

            // check if pivot table migration exists
            if (!count(preg_grep("/\d{4}_\d{2}_\d{2}_\d{6}_create_{$table}_table\.php/", $migrations))) {
                // try to get pivot columns
                $this->columns = count($this->option("column")) ? $this->option("column") : $this->getColumns();

                // prepare input
                $input = $this->normalizeAttributes(
                    // add id and timestamp columns
                    collect(['id', 'timestamps'])
                        // attach pivot columns
                        ->concat($this->columns->map(function ($item) { return "$item -> string"; }))
                        // attach relationship columns
                        ->concat($this->models->map(function ($model) {
                            $name = Str::lower(class_basename($model));
                            return "$name -> relationship (belongs-to-one), model:$model, delete:cascade";
                        })
                    ));

                // prepare arguments
                $arguments = ["name" => $table, "--input" => $input, "--table" => $table, "--silent" => true];

                // check if both relevant migration files exists else push this one to the end so we don't break migration
                $tables = $this->models->map(function ($model) {
                    if ($m = ModelsHelper::get($model)) {
                        return $m->table();
                    } else {
                        return ModelsHelper::table($model);
                    }
                })->join("|");
                if (count(preg_grep("/\d{4}_\d{2}_\d{2}_\d{6}_create_({$tables})_table\.{php,PHP}/", $migrations)) < 2) {
                    $arguments["--last"] = true;
                }

                // run migration command to create new migration
                $this->call('fibers:migration', $arguments);
            }
        }
    }

    private function getColumns ()
    {
        return $this->models
            // get only existing models
            ->filter(function ($model) {
                return ModelsHelper::exists($model);
            })
            // get pivot columns
            ->map(function ($currentModel) {
                // get other model
                $otherModel = $this->models->first(function ($model) use ($currentModel) { return $model !== $currentModel; });

                // if other method exists
                if ($otherModel) {
                    // get arrays of pivot columns
                    return ModelHelper::fromClass($currentModel)->relationships()->where('related', $otherModel)->map(function ($relationship) {
                        return $relationship->pivot;
                    })->collapse()->unique();
                } else {
                    return [];
                }
            })
            // flatten inner arrays
            ->collapse()
            // pick unique
            ->unique();
    }
}
