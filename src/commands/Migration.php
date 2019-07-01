<?php
/*
|--------------------------------------------------------------------------
| Fibers migration command [php artisan fibers:migration <Title> [Options]]
|--------------------------------------------------------------------------
|
| This command will create a new migration and optionally continue to
| creating a model. It tries to speed up migration creation by automatically
| filling usual boilerplate parts.
|
| Input should be entered as one attribute per line. End it with writing !q, :exit, or empty line:
| attributeName -> attributeType (arguments), options
|
| Possible attribute types are Laravel database column types and (additionally)
| select (enum), multiselect (set), array|collection|doc (json) and relationship.
| Some shortcuts exist for frequently used types as (string - s, integer - i,
| relationship - r, json - j, float - f).
|
| Arguments and Options are mostly used in creating a migration, with exception in relationship type
| where options are used in model's relationship function. Possible relationship arguments are:
| has-one (ho), has-many (hm), belong-to (bo), belong-to-many (bm).
| Options include: morph:model, trough:Model, delete:cascade, pivot:column1|column2, as:name, timestamps, eager
| the rest will be passed as relationship function arguments, so they can be used to customize relationship,
| like defining a different foreign_key, table, ...
|
| Example:
id
title -> string (255), nullable, unique, default:something
published -> boolean, hidden, primary
published_at -> date, format:d-m-Y
type -> select (option1, option2, option3)
place -> relationship (belongs-to), eager
rating -> relationship (belongs-to-many), pivot:title|amount, eager
user -> relationship (has-one)
review -> relationship (has-one), trough:Place
tags -> relationship (has-many), morph:taggable
number -> float (2, 8)
timestamps
soft-deletes
*/

namespace Fibers\Rocket\Commands;

use Fibers\Rocket\Command;
use Fibers\Helper\Facades\ModelsHelper;
use Fibers\Helper\Facades\TemplateHelper;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class Migration extends Command
{
    protected $signature =  'fibers:migration
                            {title : Title of migration}
                            {--L|last : Set migration file\'s name so it is migrated at the end }
                            {--M|model : Create model file as well }
                            {--table= : Set table name }
                            {--input= : Array of attributes (used mostly when calling from other commands)}';
    protected $description = 'Create Laravel migration';

    private $migration_columns = [
        ["method" => "bigIncrements", "arguments" => 0],
        ["method" => "bigInteger", "arguments" => 0],
        ["method" => "binary", "arguments" => 0],
        ["method" => "boolean", "arguments" => 0],
        ["method" => "char", "arguments" => 1, "castArguments" => "integer"],
        ["method" => "date", "arguments" => 0],
        ["method" => "dateTime", "arguments" => 0],
        ["method" => "dateTimeTz", "arguments" => 0],
        ["method" => "decimal", "arguments" => 2, "castArguments" => "integer"],
        ["method" => "double", "arguments" => 2, "castArguments" => "integer"],
        ["method" => "enum", "arguments" => 1, "castArguments" => "array"],
        ["method" => "float", "arguments" => 2, "castArguments" => "integer"],
        ["method" => "geometry", "arguments" => 0],
        ["method" => "geometryCollection", "arguments" => 0],
        ["method" => "increments", "arguments" => 0],
        ["method" => "integer", "arguments" => 0],
        ["method" => "ipAddress", "arguments" => 0],
        ["method" => "json", "arguments" => 0],
        ["method" => "jsonb", "arguments" => 0],
        ["method" => "lineString", "arguments" => 0],
        ["method" => "longText", "arguments" => 0],
        ["method" => "macAddress", "arguments" => 0],
        ["method" => "mediumIncrements", "arguments" => 0],
        ["method" => "mediumInteger", "arguments" => 0],
        ["method" => "mediumText", "arguments" => 0],
        ["method" => "morphs", "arguments" => 0],
        ["method" => "multiLineString", "arguments" => 0],
        ["method" => "multiPoint", "arguments" => 0],
        ["method" => "multiPolygon", "arguments" => 0],
        ["method" => "nullableMorphs", "arguments" => 0],
        ["method" => "nullableTimestamps", "arguments" => 0],
        ["method" => "point", "arguments" => 0],
        ["method" => "polygon", "arguments" => 0],
        ["method" => "rememberToken", "arguments" => 0, "nameless" => true],
        ["method" => "set", "arguments" => 1, "castArguments" => "array"],
        ["method" => "smallIncrements", "arguments" => 0],
        ["method" => "smallInteger", "arguments" => 0],
        ["method" => "softDeletes", "arguments" => 0, "nameless" => true],
        ["method" => "softDeletesTz", "arguments" => 0, "nameless" => true],
        ["method" => "string", "arguments" => 1, "castArguments" => "integer"],
        ["method" => "text", "arguments" => 0],
        ["method" => "time", "arguments" => 0],
        ["method" => "timeTz", "arguments" => 0],
        ["method" => "timestamp", "arguments" => 0],
        ["method" => "timestampTz", "arguments" => 0],
        ["method" => "timestamps", "arguments" => 0, "nameless" => true],
        ["method" => "timestampsTz", "arguments" => 0, "nameless" => true],
        ["method" => "tinyIncrements", "arguments" => 0],
        ["method" => "tinyInteger", "arguments" => 0],
        ["method" => "unsignedBigInteger", "arguments" => 0],
        ["method" => "unsignedDecimal", "arguments" => 2, "castArguments" => "integer"],
        ["method" => "unsignedInteger", "arguments" => 0],
        ["method" => "unsignedMediumInteger", "arguments" => 0],
        ["method" => "unsignedSmallInteger", "arguments" => 0],
        ["method" => "unsignedTinyInteger", "arguments" => 0],
        ["method" => "uuid", "arguments" => 0],
        ["method" => "year", "arguments" => 0],
    ];
    private $migration_options = ["after", "autoIncrement", "charset", "collation", "comment", "default", "first", "nullable", "unsigned", "useCurrent", "unique", "primary", "index"];
    private $migration_fields;
    private $migration_title;
    private $migration_table;
    private $migration_name;
    private $migration_classname;
    private $migration_filename;

    /**
     * Migration constructor - prepare 'columns' and 'options'
     */
    public function __construct(Filesystem $files, Composer $composer)
    {
        parent::__construct($files, $composer);

        // prepare collection of columns
        $this->migration_columns = collect($this->migration_columns)->mapWithKeys(function ($item) {
            if (!isset($item["nameless"])) $item["nameless"] = false;
            if (!isset($item["castArguments"])) $item["castArguments"] = null;
            return [strtolower($item["method"]) => (Object) $item];
        });

        // prepare options
        $this->migration_options = collect($this->migration_options);
    }

    /**
     * Command closure
     * Get user input, normalize it, parse it to model data, create model file
     */
    public function handle(): void
    {
        parent::handle();

        // collect input
        $this->collectInput();

        // create migration
        $this->success = $this->createMigration();

        // migrate file if needed
        if ($this->success and ($this->force or (!$this->silent and $this->confirm("Would you like to migrate?", "yes")))) {
            $this->callSilent('migrate', ['--path' => $this->migration_filename]);
            $this->infoDelayed("Migration migrated [$this->migration_filename]", "success");
        }

        // continue to other
        $this->continue("model", function () {
            if (!ModelsHelper::exists(Str::studly(class_basename($this->migration_title)))) {
                if ($this->confirm("Do you want to create model file for this migration?", "yes")) {
                    $this->call("fibers:model", ["title" => $this->migration_title, "--input" => $this->migration_fields, "--silent" => $this->silent, "--force" => $this->force, '--ignore' => ['migration']]);
                }
            }
        });
    }

    /**
     * Create migration
     * @return bool
     */
    private function createMigration(): bool
    {
        // check if table already exist
        if (Schema::hasTable($this->migration_name)) {
            $this->infoDelayed("Table $this->migration_table already exists so migration was not created.", "error");
            return false;
        }

        // write template
        $this->checkFileExist(base_path("$this->migration_filename"), function ($path) {
            TemplateHelper::fromFile(FIBERS_ROCKET."/templates/migration.stub")->replace([
                "name" => $this->migration_classname,
                "table:string" => $this->migration_table,
                "columns" => $this->migration_columns,
            ])->tofile($path);

            // output to user
            $this->infoDelayed("Migration created [$this->migration_filename]");
        });

        return true;
    }

    /**
     * Collect input
     * @throws \Exception
     */
    private function collectInput (): void
    {
        // collect title
        $this->migration_title = $this->argument("title");

        // get input about models attributes
        $this->migration_fields = $this->option('input') ?? $this->attributeInput("migration");

        // collect name and table
        if (!$this->option("table")) {
            $name = $this->migration_title;
            $table = Str::snake($name);
        } else {
            $table = $this->option("table");
            $name = Str::studly($table);
        }
        $this->migration_table = Str::plural($table);
        $this->migration_name = Str::plural($name);

        // set classname
        $this->migration_classname = "Create{$name}Table";

        // set filename
        $this->migration_filename = "database/migrations/".$this->filename($table);

        // prepare fields (either handle relationship or normal column)
        $this->migration_columns = $this->migration_fields->map(function ($item) use ($name) {
            return $item->type === "relationship" ? $this->handleRelationship($item, $name) : ($item->type === "uuid" ? $this->handleUuid($item) : $this->handleColumn($item));
        })->filter()->values()->reduce(function ($carry, $item) {
            $carry .= $item."\n";
            return $carry;
        }, "");
    }

    /**
     * Prepare columns connected with relationships
     * @param $item
     * @param $name
     * @return string|null
     */
    private function handleRelationship($item, $name)
    {
        if ($item->options->type === 'belongs-one') {
            // check type of primary on target model
            $model = $item->options->target ?? ModelsHelper::get($item->name);
            if ($model) {
                $info = $model->collect('primary', 'attributes');
                $primary = $info->attributes->get($info->primary);
                if ($primary) {
                    switch ($primary->type) {
                        case "tinyint":
                            $method = "unsignedTinyInteger";
                            break;
                        case "smallint":
                            $method = "unsignedSmallInteger";
                            break;
                        case "int":
                            $method = "unsignedInteger";
                            break;
                        case "mediumint":
                            $method = "unsignedMediumInteger";
                            break;
                        case "bigint":
                            $method = "unsignedBigInteger";
                            break;
                        case "char":
                            $method = "char";
                            break; // uuid
                        default:
                            $method = "unsignedBigInteger";
                            break;
                    }
                    if ($primary->type === "char") {
                        $length = $primary->arguments->get(0);
                        if ($length) $length = ", $length";
                    }
                } else {
                    $method = "unsignedBigInteger";
                }
            } else {
                $method = "unsignedBigInteger";
            }
            $export = "\t\t\t\$table->$method('{$item->options->local}'".($length ?? "").")->index()->nullable();";
            $export .= "\n\t\t\t\$table->foreign('{$item->options->local}')->references('{$item->options->foreign}')->on('{$item->options->table}')";
            if ($update = $item->options->mutation->option("update")) $export .= "->onUpdate('$update')";
            if ($delete = $item->options->mutation->option("delete")) $export .= "->onDelete('$delete')";
            $export .= ";";
            return $export;
        }
        elseif ($item->options->type === 'belongs-many') {
            // collect both models
            $model = Str::snake(class_basename(ModelsHelper::class($name)));
            $target = Str::snake(class_basename($item->options->target ?? ModelsHelper::class($item->name)));

            // run command to create pivot table
            $this->callDelayed('fibers:pivot', ["model" => [$model, $target]]);
        }
        return null;
    }

    /**
     * Prepare uuid column
     * @return string
     */
    private function handleUuid()
    {
        return "\t\t\t\$table->uuid('id')->nullable()->primary();";
    }

    /**
     * Prepare standard columns
     * @param $item
     * @return string|null
     */
    private function handleColumn($item)
    {
        $column = $this->migration_columns->get($item->type);
        if ($column) {
            $name = $column->nameless ? "" : "'{$item->name}'{$this->args($item->arguments, $column)}";
            return "\t\t\t\$table->{$column->method}({$name}){$this->opts($item->options, ($item->type === "enum" or $item->type === "set"))};";
        } else {
            return null;
        }
    }

    /**
     * Get filename for migration file
     * @param $table
     * @return string
     */
    private function filename ($table)
    {
        if ($this->option("last")) {
            // collect migration files
            $migrations = collect(glob(base_path("database/migrations") . "/*.{php,PHP}", GLOB_BRACE))->map(function ($filepath) { return basename($filepath); })->toArray();

            // check how many last migration files we already have
            $count = strval(count(preg_grep("/9999_99_99_\d{6}_create_(?:.*)_table\.{php,PHP}/", $migrations)) + 1);

            // set timestamp
            $timestamp = "9999_99_99_" . str_repeat("0", 6 - strlen($count)) . $count;

            // generate filename for migration
            return "{$timestamp}_create_{$table}_table.php";

        }
        $now = now();
        return $now->format('Y_m_d').'_'.substr($now->timestamp, -6).'_create_'.$table.'_table.php';
    }

    /**
     * Get normalized name
     * @return string
     */
    private function name()
    {
        $name = Str::camel(str_replace("create_", "", str_replace("_table", "", $this->migration_title)));
        if (!class_exists($modelName = ModelsHelper::class($name))) {
            $model = ModelsHelper::search($name);
            $name = $this->ask("Model $name could not be found. Please write what name you want to use for this migration", $model->name());
        }
        return ucfirst(Str::camel(Str::plural($name)));
    }

    /**
     * Helper method for building arguments
     * @param $arguments
     * @param $columns
     * @return string
     */
    protected function args($arguments, $columns): string
    {
        $arguments = $arguments->filter(function ($item) {
            return !blank($item);
        });
        if ($arguments->count()) {
            if ($columns->castArguments === "array") {
                return Str::start(TemplateHelper::array($arguments), ", ");
            } else {
                $isInteger = $columns->castArguments === "integer";
                return Str::start($arguments->take($columns->arguments)->map(function ($item) use ($isInteger) {
                    return $isInteger ? TemplateHelper::integer($item) : TemplateHelper::string($item);
                })->join(", "), ", ");
            }
        } else {
            return "";
        }
    }

    /**
     * Helper method for building options
     * @param $options
     * @param bool $simple
     * @return mixed
     */
    private function opts($options, $simple = false)
    {
        return $this->migration_options->reduce(function ($carry, $item) use ($options, $simple) {
            if ($args = $options->option($item)) $carry .= "->$item(" . ($args === true ? "" : (!$simple ? TemplateHelper::string($args) : $args)) . ")";
            return $carry;
        }, "");
    }
}
