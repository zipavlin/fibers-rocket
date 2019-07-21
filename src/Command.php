<?php

namespace Fibers\Rocket;

use Closure;
use Fibers\Helper\Command as BaseCommand;
use Fibers\Helper\Facades\ModelsHelper;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Command extends BaseCommand
{

    /**
     * Collect attribute input
     * @param string $title
     * @return Collection
     * @throws \Exception
     */
    protected function attributeInput(string $title): Collection
    {
        $title = Str::title($title);

        // get input
        $input = $this->editor("Please enter your model's attributes", ['one attribute per line']);

        // parse input
        $input = $this->normalizeAttributes($input);

        // output table
        if (!blank($input)) {
            if (!$this->option("silent")) {
                $this->info("\n Model will be created with following attributes:\n");
                $this->table(
                    ["name", "type", "arguments", "options"],
                    $input->values()->map(function ($row) {
                        return collect($row)->map(function ($column, $key) use ($row) {
                            if (!isset($column)) return null;
                            if (is_string($column)) return $column;
                            if (is_array($column)) return implode(", ", $column);
                            if ($row->type === "relationship") {
                                if ($key === "arguments") return $row->options->type;
                                if ($key === "options") return $row->options->model->join(", ");
                            }
                            return $column->join(", ");
                        })->values()->toArray();
                    })->toArray()
                );

                // confirm
                if (!$this->confirm("Do you want to continue?", "yes")) die();
            }

            // return
            return $input;
        }
        else throw new \Exception("$title attributes input missing.");
    }

    /**
     * Normalize relationship attribute if user made a typo
     * @param $key
     * @param $item
     * @return object
     * @throws \Exception
     */
    protected function normalizeRelationshipAttribute($key, $item)
    {
        // get type
        $type = $item->arguments->pull(0);

        // normalize relationship type
        switch ($type) {
            case "has-one":case "ho":case "hasone":case "one": $normalized = "has-one"; break;
            case "has-many":case "hm":case "hasmany":case "many": $normalized = "has-many"; break;
            case "belongs-to-one":case "bo":case "belongstoone":case "belongs-to":case "belongs-one":case "belongsto":case "belongs":case "one-many": $normalized = "belongs-one"; break;
            case "belongs-to-many":case "bm":case "belongstomany":case "belongs-many": $normalized = "belongs-many"; break;
            default: throw new \Exception("Attribute {$key} must have relationship type set.");
        }

        // divide options into relevant for model, migration, both
        $options = (Object) [
            "type" => $normalized,
            "model" => collect(),
            "relationship" => collect(),
            "trough" => null,
            "pivot" => null,
            "target" => null,
            "foreign" => null,
            "local" => null,
            "table" => null,
        ];
        // get model options
        $options->model = $item->options->intersect(array_merge(['eager', 'hidden', 'fillable', 'format', 'timestamps'], preg_grep("/(morph:|as:)[\w|]+/", $item->options->toArray())))->values();

        // get relationship options
        $options->mutation = $item->options->intersect(array_merge(['autoIncrement', 'first', 'nullable', 'unsigned', 'useCurrent', 'unique', 'primary', 'index'], preg_grep("/(after:|charset:|collation:|comment:|delete:|default:|update:)[\w|]+/", $item->options->toArray())))->values();

        // get shared options
        $shared = $item->options->intersect(preg_grep("/(trough:|pivot:|model:|local:|foreign:|table:)[\w|]+/", $item->options->toArray()))->values();
        $options->trough = $shared->option('trough') ? ModelsHelper::class($shared->option('trough')) : null;
        $options->pivot = $shared->option('pivot');
        $options->target = $shared->option('model') ? ModelsHelper::get($shared->option('model')) : null;

        $options->foreign = $shared->option('foreign');
        $options->table = $shared->option('table');
        $options->local = $shared->option('local');

        // get rest of options that are
        $item->arguments = $item->options->diff($options->model)->diff($options->relationship)->diff($shared)->values();

        // set foreign, local and table if not set based on model
        if ($options->type !== "belongs-many") {
            $model = $options->target ?? ModelsHelper::get($key);
            if ($model and $info = $model->collect('primary', 'table')) {
                if (!$options->foreign) $options->foreign = $info->primary;
                if (!$options->table) $options->table = $info->table;
                if (!$options->local) $options->local = Str::snake(Str::singular($model->name())) . "_" . $info->primary;
            } elseif (!$options->local) $options->local = Str::snake(Str::singular($key)) . "_id";
        }

        // set item options
        $item->options = $options;

        return $item;
    }

    /**
     * Normalize attribute type if user made a typo
     * @param string $type
     * @return string
     */
    protected function normalizeAttributeType(string $type): string
    {
        $type = strtolower(str_replace(["-", " "], "", $type));
        switch ($type) {
            case "str": case "s": $type = "string"; break;
            case "relation": case "r": $type = "relationship"; break;
            case "int": case "i": $type = "integer"; break;
            case "bigint":case "bi": $type = "biginteger"; break;
            case "array": $type = "json"; break;
            case "collection": $type = "json"; break;
            case "doc": $type = "json"; break;
            case "increment": $type = "increments"; break;
            case "inc": $type = "increments"; break;
            case "bigincrement": $type = "bigincrements"; break;
            case "biginc": $type = "bigincrements"; break;
            case "ip": $type = "ipAddress"; break;
            case "bool":case "b": $type = "boolean"; break;
            case "select": $type = "enum"; break;
            case "multiselect": $type = "set"; break;
            case "geo": $type = "geometry"; break;
            case "morph": $type = "morhps"; break;
            case "j": $type = "json"; break;
            case "f": $type = "float"; break;
            case "t":case "txt": $type = "text"; break;
        }
        return $type;
    }

    /**
     * Parse and normalize attribute input
     * @param Collection $input
     * @return Collection
     * @throws \Exception
     */
    protected function normalizeAttributes(Collection $input): Collection
    {
        $export = collect();

        foreach ($input as $field) {
            // split row
            $a = collect(explode("->", $field))->map(function ($item) { return trim($item); });

            // normalize name
            $key = preg_replace("/-/", "", strtolower($a[0]));

            // try to parse rows where type is set
            if (isset($a[1])) {
                // try to parse input
                preg_match("/(\w+)(?:\s*\((.+)\))?(?:,\s*(.*))*/", $a[1], $matches);

                // break if not possible
                if (!$matches) return new \Exception("Input cannot be parsed");

                // create and populate attribute data
                $item = (Object) [
                    "name" => $key,
                    "type" => $this->normalizeAttributeType($matches[1]),
                    "arguments" => isset($matches[2]) ? collect(explode(",", $matches[2]))->map(function ($i) { return trim($i); }) : collect(),
                    "options" => isset($matches[3]) ? collect(explode(",", $matches[3]))->map(function ($i) { return trim($i); }) : collect()
                ];

                // normalize relationship
                if ($item->type === "relationship") {
                    $item = $this->normalizeRelationshipAttribute($key, $item);
                }
            }

            // try to populate auto-columns
            // TODO: some special properties should also be checked here. Used in model creation for adding use statements
            else {
                switch ($key) {
                    case "id": $item = (Object) ["name" => $key, "type" => "bigincrements", "arguments" => collect(), "options" => collect()]; break;
                    case "uuid": $item = (Object) ["name" => $key, "type" => "uuid", "arguments" => collect(), "options" => collect()]; break;
                    case "timestamps": $item = (Object) ["name" => "timestamps", "type" => "timestamps", "arguments" => collect(), "options" => collect()]; break;
                    case "timestampstz": $item = (Object) ["name" => $key, "type" => "timestampstz", "arguments" => collect(), "options" => collect()]; break;
                    case "nullabletimestamps": $item = (Object) ["name" => $key, "type" => "nullabletimestamps", "arguments" => collect(), "options" => collect()]; break;
                    case "softdelete":case "softdeletes":case "delete": $item = (Object) ["name" => "softdeletes", "type" => "softdeletes", "arguments" => collect(), "options" => collect()]; break;
                    case "softdeletetz":case "softdeletestz":case "deletetz": $item = (Object) ["name" => $key, "type" => "softdeletestz", "arguments" => collect(), "options" => collect()]; break;
                    case "remember":case "token":case "remembertoken": $item = (Object) ["name" => $key, "type" => "remembertoken", "arguments" => collect(), "options" => collect()]; break;
                    default: $item = null;
                }
            }

            // map with keys
            if ($key and $item) $export->put($key, $item);
        };
        return $export;
    }

    /**
     * Collect comma separated input
     * @param $name
     * @param $question
     * @return Collection
     */
    protected function inputCollection(string $name, string $question): Collection
    {
        $input = $this->option($name) ?? ($this->silent ? "" : $this->ask("$question [<comment>comma separated</comment>]"));
        return collect(is_array($input) ? $input : explode(",", $input))
            ->map(function ($item) {
                return trim($item);
            })
            ->filter(function ($item) {
                return !blank($item);
            });
    }

    /**
     * Check if file already exists
     * @param string $path
     * @param Closure $closure
     */
    protected function checkFileExist(string $path, Closure $closure)
    {
        if (file_exists($path)) {
            if ($this->option("force") or (!$this->silent and $this->confirm("File '".realpath($path)."' already exists. Do you want to overwrite it?", "no"))) {
                $closure($path);
            }
        } else {
            $closure($path);
        }
    }

    /**
     * Get target model classname
     * @param $title
     * @return string
     */
    protected function getTarget (string $title): string
    {
        if ($this->option('target')) {
            return ModelsHelper::class($this->option('target'));
        } else {
            if ($this->silent) {
                return ModelsHelper::class($title);
            } else {
                return $this->ask("What model this belongs to?", ModelsHelper::search($title)->class());
            }
        }
    }

    /**
     * Get target's controller 'only' actions
     * @param $target
     * @return string
     */
    protected function getOnlyActions(string $target = null)
    {
        // get input
        if ($this->option("only") === false) {
            $input = [];
        }
        elseif (!($input = $this->option("only"))) {
            // try to get existing actions
            $input = $this->getControllerActions($target)->join(",");
            if (!$this->silent) {
                $input = $this->ask("What resource actions would you like to include? [<comment>comma separated</comment>]", $input);
            }
        }
        return collect(is_array($input) ? $input : explode(",", $input))
            ->map(function ($item) {
                return trim($item);
            })
            ->filter(function ($item) {
                return !blank($item);
            });
    }

    /**
     * Get target's controller 'except' actions
     * @param $target
     * @return string
     */
    protected function getExceptActions(string $target = null)
    {
        // get input
        if ($this->option("except") === false) {
            $input = [];
        }
        elseif (!($input = $this->option("except"))) {
            // try to get existing actions
            $input = $this->getControllerActions($target)->diff(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'])->join(",");
            if (!$this->silent) {
                $input = $this->ask("What resource actions would you like to exclude? [<comment>comma separated</comment>]", $input);
            }
        }
        return collect(is_array($input) ? $input : explode(",", $input))
            ->map(function ($item) {
                return trim($item);
            })
            ->filter(function ($item) {
                return !blank($item);
            });
    }

    /**
     * Collect target's controller actions
     * @param string $target
     * @return Collection
     */
    private function getControllerActions (string $target): Collection
    {
        if ($target and file_exists(app_path("Http/Controllers/{$target}Controller"))) {
            return collect(array_intersect(
                array_diff(
                    get_class_methods($target), get_class_methods(get_parent_class($target)
                )), ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']));
        } else {
            return collect();
        }
    }
}
