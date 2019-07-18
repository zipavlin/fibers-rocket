<?php
/*
|--------------------------------------------------------------------------
| Fibers make:model command [php artisan fibers:make:model Name]
|--------------------------------------------------------------------------
|
| This command will create a new model and optionally continue to
| creating a migration. It tries to speed up model creation by automatically
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
| TODO: [uuid, softdelete, image, images, files, media] attribute should set model appropriately
| Example:
id
title -> string (255)
published -> boolean, hidden
published_at -> date, format:d-m-Y
place -> relationship (belongs-to-many), eager
rating -> relationship (belongs-to-many), pivot:title|amount, eager
user -> relationship (has-one)
review -> relationship (has-one), trough:Place
tags -> relationship (has-many), morph:taggable
timestamps
soft-deletes
*/

namespace Fibers\Rocket\Commands;

use Fibers\Rocket\Command;
use Fibers\Helper\Facades\ModelHelper;
use Fibers\Helper\Facades\ModelsHelper;
use Fibers\Helper\Facades\TemplateHelper;
use Illuminate\Support\Str;

class Model extends Command
{
    protected $signature =  'fibers:make:model
                            {title : Title of model}
                            {--M|migration : Create migration file as well }
                            {--input= : Array of attributes (used mostly when calling from other commands)}';
    protected $description = 'Create Laravel model';

    private $model_ignored = ["id", "uuid", "created_at", "updated_at", "deleted_at", "timestamps", "timestampstz", "softdeletes", "softdeletestz"];
    private $model_hidden = ["password", "remember_token"];
    private $model_dates = ["date", "datetime", "datetimetz", "time", "timetz", "year", "timestamp", "timestamptz"];
    private $model_fields;
    private $model_title;

    /**
     * Command closure
     * Get user input, normalize it, parse it to model data, create model file
     */
    public function handle()
    {
        parent::handle();

        // collect title
        $this->model_title = Str::ucfirst($this->argument("title"));

        // get input about models attributes
        $this->model_fields = $this->option('input') ?? $this->attributeInput("model");

        // create model
        $this->success = $this->createModel();

        // continue to other commands
        $this->continue("migration", ["title" => $this->model_title, "--input" => $this->model_fields, "--ignore" => ['model']]);
    }

    /**
     * Collect data and create new model file
     * @return bool
     */
    private function createModel(): bool
    {
        // prepare data
        $namespace = ModelsHelper::namespace();
        $use = collect();
        $fillable = collect();
        $hidden = collect();
        $dates = collect();
        $casts = collect();
        $relationships = collect();
        $with = collect();
        $dir = ModelsHelper::dir();
        $path = ($dir ? $dir . DIRECTORY_SEPARATOR : '') . $this->model_title . '.php';

        // iterate input rows and populate data
        foreach ($this->model_fields as $item) {
            // add item to use if name or key exists in config.fibers.traits (if type is mapped to appropriate trait class)
            if (array_key_exists($item->type, config('fibers.traits'))) {
                $use->push($item->type);
            } elseif (array_key_exists($item->name, config('fibers.traits'))) {
                $use->push($item->name);
            }

            // add item to fillable
            if (($item->type === "relationship" or $item->options->contains("fillable") or !$item->options->contains("hidden")) and !in_array($item->name, $this->model_ignored)) {
                if ($item->type === "relationship") $fillable->push($item->name . "_id");
                else $fillable->push($item->name);
            }

            // add item to hidden
            if (($item->type !== "relationship" and $item->options->contains("hidden")) or in_array($item->name, $this->model_hidden)) {
                $hidden->push($item->name);
            }

            // add item to dates and cast date types
            if (in_array($item->type, $this->model_dates)) {
                $format = preg_grep("/format:.*/", $item->options->toArray()); $format = count($format) ? preg_replace("/format:(.*)/", "$1", $format[0]) : null;
                // set cast to date or datetime
                if (in_array($item->type, ["date", "datetime", "datetimetz", "time", "timetz", "year"])) {
                    $type = $item->type === "date" ? "date" : "datetime";
                    if ($format) $casts->put($item->name, "{$type}:{$format}");
                    else $casts->put($item->name, "{$type}");
                }

                // cast to timestamp
                elseif (in_array($item->type, ["timestamp", "timestamptz"])) $casts->put($item->name, "timestamp");

                // add to dates array
                $dates->push($item->name);
            }

            // cast remaining types
            else {
                switch ($item->type) {
                    case "integer":case "smallinteger":case "mediuminteger":case "biginteger":case "unsignedinteger":case "unsignedbiginteger":case "unsignedmediuminteger":case "unsignedsmallinteger":case "unsignedtinyinteger":  $casts->put($item->name, "integer"); break;
                    case "boolean":case "tinyinteger": $casts->put($item->name, "boolean"); break;
                    case "char":case "enum":case "set":case "linestring":case "macaddress": case "ipaddress": case "longtext":case "mediumtext":case "multilinestring":case "string":case "text":case "remembertoken":case "uuid": $casts->put($item->name, "string"); break;
                    case "geometry":case "json":case "jsonb":case "point":case "polygon":case "positions":case "multipoint":case "multipolygon": $casts->put($item->name, "collection"); break;
                    case "geometrycollection": $casts->put($item->name, "collection"); break;
                    case "float": $casts->put($item->name, "float"); break;
                    case "double": $casts->put($item->name, "double"); break;
                    case "decimal":case "unsigneddecimal": if ($item->options->count() == 2) $casts->put($item->name, "decimal:{$item->options->get(1)}"); break;
                }
            }

            // collect relationships
            if ($item->type === "relationship") {
                // add to eager loading array
                if ($item->options->model->contains('eager')) $with->push($item->name);

                // build relationship and add to array
                $relationships->push($this->handleRelationship($item));
            }
        }

        // prepare import and use
        $imports = $use->map(function ($item) {
            return "use " . config('fibers.traits')[$item] . ";";
        })->join("\n");
        $use = $use->map(function ($item) {
            return class_basename(config('fibers.traits')[$item]);
        })->join(", ");

        // write template
        $this->checkFileExist(app_path($path), function ($fullpath) use ($path, $namespace, $imports, $use, $fillable, $hidden, $dates, $casts, $with, $relationships) {
            TemplateHelper::fromFile(FIBERS_ROCKET."/templates/model.stub")->replace([
                "namespace" => $namespace,
                "name" => $this->model_title,
                "imports" => Str::finish($imports, "\n"),
                "use" => !blank($use) ? "use $use;\n" : "",
                "fillable:array,conditional" => $fillable,
                "hidden:array,conditional" => $hidden,
                "dates:array,conditional" => $dates,
                "casts:array,conditional" => $casts,
                "with:array,conditional" => $with,
                "relationships" => $relationships
            ])->tofile($fullpath);

            // output to user
            $this->infoDelayed("Model created [app/".str_replace("\\", "/", $path)."]");
        });

        return true;
    }

    /**
     * Build a relationship function string
     * @param $item
     * @param string $type
     * @return string
     */
    private function handleRelationship($item): string
    {
        $type = $item->options->type;

        // find model's class from name
        if ($item->options->target) {
            $model = $item->options->target->class();
        } elseif ($model = ModelsHelper::get($item->name)) {
            $model = $model->class();
        } else {
            $model = ModelsHelper::class($item->name);
        }

        // check if model exists and has set relationship
        if (!ModelsHelper::exists($model)) {
            $this->infoDelayed("Model $model used as a relationship does not exist", "error");
        } else {
            $relationship = ModelHelper::fromClass($model)->relationships()->where("related", ModelsHelper::class($this->model_title));
            if (!$relationship->count()) {
                $this->infoDelayed("Dont forget to set appropriate $type relationship in your $model model", "info");
            }
        }

        // prepare output
        $output =   "\tpublic function " . $item->name . " ()\n\t{\n\t\t";
        if ($type === "has-one" or $type === "has-many") {
            $variant = ucfirst(Str::after($type, "has-"));
            if ($trough = $item->options->trough) {
                if ($troughModel = ModelsHelper::get($trough)) {
                    $troughInfo = $troughModel->collect('name', 'primary');
                    $troughForeign = $troughInfo->primary;
                    $troughLocal = Str::snake(Str::singular($troughInfo->name))."_".$troughInfo->primary;
                }
                $output .= $this->replace(
                    'return $this->has{{variant}}Trough("{{model}}", "{{trough}}"{{arguments}});',
                    ["variant" => $variant, "model" => $model, "trough" => $trough, "arguments" => $this->args([$item->options->local, $troughLocal ?? null, ($troughLocal ?? null) ? $item->options->foreign : null, $troughForeign ?? null])]);
            }
            elseif ($morph = $item->options->model->option("morph")) {
                $output .= $this->replace(
                    'return $this->morph{{variant}}("{{model}}", "{{morph}}");',
                    ["variant" => $variant, "model" => $model, "morph" => $morph]);
            }
            else {
                $output .= $this->replace(
                    'return $this->has{{variant}}("{{model}}"{{arguments}});',
                    ["variant" => $variant, "model" => $model, "arguments" => $this->args([$item->options->local, $item->options->foreign])]);
            }
        }
        elseif ($type === "belongs-one") {
            $output .= $this->replace(
                'return $this->belongsTo("{{model}}"{{arguments}});',
                ["model" => $model, "arguments" => $this->args([$item->options->local, $item->options->foreign])]);
        }
        elseif ($type === "belongs-many") {
            $output .= $this->replace(
                'return $this->belongsToMany("{{model}}"{{arguments}})',
                ["model" => $model, "arguments" => $this->args([$item->options->table, $item->options->local, $item->options->foreign])]);
            // set additional optional parameters on many-to-many relationship
            if ($pivot = $item->options->pivot) {
                $output .= '->withPivot('.$pivot->quotes()->join(", ").')';
            }
            if ($as = $item->options->model->option("as")) {
                $output .= '->as('.$as.')';
            }
            if ($trough = $item->options->trough) {
                $output .= '->using('.TemplateHelper::string($trough).')';
            }
            if ($item->options->model->contains("timestamps")) {
                $output .= '->withTimestamps()';
            }
            $output .= ';';
        }
        $output .= "\n\t}";

        // return
        return $output;
    }

    /**
     * Helper method for building arguments
     * @param array $arguments
     * @return string
     */
    private function args(array $arguments): string
    {
        return collect($arguments)->filter()->map(function ($item) {
            return TemplateHelper::string($item);
        })->whenNotEmpty(function ($collection) {
            return Str::start($collection->join(", "), ", ");
        }, function () {
            return "";
        });
    }
}
