<?php
/*
|--------------------------------------------------------------------------
| Fibers make:layout command [php artisan fibers:make:layout <Title> [Options]]
|--------------------------------------------------------------------------
|
| This command will create layout views for a model.
|
*/

namespace Fibers\Rocket\Commands;

use Fibers\Rocket\Command;
use Fibers\Helper\Facades\ModelsHelper;
use Fibers\Helper\Facades\TemplateHelper;
use Fibers\Helper\Facades\ViewHelper;
use Illuminate\Support\Str;

class Layout extends Command
{
    protected $signature =  'fibers:make:layout
                            {title : Title of layout folder}
                            {--C|controller : Create controller file as well }
                            {--bootstrap : Use bootstrap template }
                            {--paginated : Use pagination in index view }
                            {--only= : Comma separated collection of controller methods }
                            {--except= : Comma separated collection of ignored controller methods }
                            {--target= : Target model }';
    protected $description = 'Create Laravel layout view files';

    private $layout_title;
    private $layout_target;
    private $layout_controller;
    private $layout_bootstrap;
    private $layout_path;
    private $layout_error_helper;
    private $layout_default;
    private $layout_only_actions; // only methods
    private $layout_except_actions; // except methods

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
        $this->success = $this->createLayout();

        // continue to other commands
        $this->continue("controller", [
            "title" => $this->layout_title,
            "--paginated" => $this->option("paginated"),
            "--only" => !blank($this->layout_only_actions) ? $this->layout_only_actions->join(",") : false,
            "--except" => !blank($this->layout_except_actions) ? $this->layout_except_actions->join(",") : false,
            "--target" => $this->option("target"),
            "--ignore" => ['layout']
        ]);
    }

    /**
     * Collect data and create new controller file
     * @return bool
     */
    private function createLayout(): bool
    {
        // collect actions
        $views = collect(['index', 'show', 'create', 'store', 'edit', 'update', 'destroy'])
            ->when($this->layout_only_actions->count(), function ($collection) {
                return $collection->intersect($this->layout_only_actions)->values();
            })
            ->diff($this->layout_except_actions)
            ->reduce(function ($carry, $item) {
                // add partials as needed
                if (in_array($item, ["create", "edit"])) $carry->push("_form");
                elseif ($item === "index") $carry->push("_item");
                // add views as needed
                if (in_array($item, ["index", "show", "create", "edit"])) $carry->push($item);
                // return
                return $carry;
            }, collect())
            ->unique();
        
        // create templates
        $views->each(function ($view) {
            if (method_exists($this, $view)) {
                $this->{$view}();
            }
        });

        // output to user
        $this->infoDelayed("Layout views created [resources/views/{$this->layout_title}/]");

        return true;
    }

    /**
     * Collect input and store it in private properties
     */
    private function collectInput(): void
    {
        // collect title
        $this->layout_title = Str::snake(Str::singular($this->argument('title')));

        // set directory path
        $this->layout_path = ViewHelper::path().DIRECTORY_SEPARATOR.$this->layout_title;

        // should be use bootstrap version of templates
        $this->layout_bootstrap = $this->option('bootstrap');

        // get laravel version and decide if new error blade helper is supported
        $this->layout_error_helper = (version_compare(app()->version(), '5.8.13') >= 0);

        // collect target
        $this->layout_target = $this->getTarget($this->layout_title);

        // collect only & except collection
        $this->layout_only_actions = $this->getOnlyActions($this->layout_target);
        $this->layout_except_actions = $this->getExceptActions($this->layout_target);

        // set default layout
        $this->layout_default = ViewHelper::layoutsName().".".ViewHelper::main();

        // collect controller
        $this->layout_controller = Str::finish(Str::studly($this->layout_target), "Controller");
    }

    /**
     * Get view template stub
     * @param $name
     * @return string
     */
    private function template (string $name)
    {
        return config('fibers.templates.index') ?: (FIBERS_ROCKET."/templates/views/pages/$name".($this->layout_bootstrap ? '_bootstrap' : '').".stub");
    }

    private function writeTemplate (string $from, string $to, array $changes = [])
    {
        $this->checkFileExist("{$this->layout_path}/$to.blade.php", function ($path) use ($from, $changes) {
            TemplateHelper::fromFile($this->template($from))->replace(array_merge($changes, [
                "title" => $this->layout_title,
                "layout" => $this->layout_default,
            ]))->tofile($path);
        });
    }

    /**
     * Template helper that writes 'index' view
     */
    private function index(): void
    {
        $this->writeTemplate("index", "index", [
            "pagination" => $this->option('paginated') ? ($this->layout_bootstrap ? "<div style=\"margin-bottom: -1rem;\">{{\$items->links()}}</div>" : "{{\$items->links()}}") : "",
        ]);
    }

     /**
     * Template helper that writes 'show' view
     */
    private function show(): void
    {
        $this->writeTemplate("show", "show");
    }

     /**
     * Template helper that writes 'create' view
     */
    private function create(): void
    {
        $this->writeTemplate("create", "create");
    }

     /**
     * Template helper that writes 'edit' view
     */
    private function edit(): void
    {
        $this->writeTemplate("edit", "edit");
    }

     /**
     * Template helper that writes '_form' view partial
     */
    private function _form(): void
    {
        // get fields
        if ($model = ModelsHelper::get($this->layout_target)) {
            $fields = $model->fields(true)->map(function ($field) {
                // populate field
                $class = $this->errorClass($field->name);
                $errors = $this->errorField($field->name);
                switch ($field->type) {
                    case "input": return $this->fieldInput($field, $class, $errors);
                    case "textarea": return $this->fieldTextarea($field, $class, $errors);
                    case "select": return $this->fieldSelect($field, $class, $errors);
                    case "relationship": return $this->fieldRelationship($field, $class, $errors);
                    default: return null;
                }
            })->values()->filter()->join("\n");
        } else {
            $fields = "<!-- input your fields here -->";
        }
        
        // create template
        $this->writeTemplate("form", "_form", [
            "content" => $fields,
            "errors" => $this->errorForm()
        ]);
    }

     /**
     * Template helper that writes '_item' view partial
     */
    private function _item(): void
    {
        // find first few fields
        if ($model = ModelsHelper::get($this->layout_target)) {
            $attributes = $model
                ->attributes()
                ->filter(function ($item, $key) {
                    return !in_array($key, ['id', 'created_at', 'updated_at']) and in_array($item->type, ['varchar', 'text']);
                })
                ->keys()
                ->take(3)
                ->map(function ($attribute) {
                    return "{{\$item->$attribute}}";
                })
                ->join(" ");
        } else {
            $attributes = "";
        }

        // write template
        $this->writeTemplate("item", "_item", [
            "content" => $attributes
        ]);
    }

    /**
     * Create error class string based on laravel version and use of bootstrap
     * @param $name
     * @return string
     */
    private function errorClass(string $name): string
    {
        $class = $this->layout_bootstrap ? "is-invalid" : "invalid";
        return $this->layout_error_helper ? "@error('{$name}') $class @enderror" : "{{\$errors->has('{$name}') ? '$class' : ''}}";
    }

    /**
     * Create input field errors HMTL string based on laravel version and use of bootstrap
     * @param $name
     * @return string
     */
    private function errorField(string $name): string
    {
        $class = $this->layout_bootstrap ? ($this->layout_error_helper ? "alert alert-danger" : "invalid-feedback") : "errors";
        return $this->layout_error_helper ? "@error('$name') <div class=\"$class\">{{\$message}}</div> @enderror" : "@if(\$errors->has('$name'))<span class=\"$class\">{{implode(\"<br>\", \$errors->get('$name'))}}</span>@endif";
    }

    /**
     * Create form errors HMTL string
     * @return string
     */
    private function errorForm(): string
    {
        return "@if(\$errors->count())\n\t<ul>\n\t\t@foreach(\$errors->all() as \$error)\n\t\t\t<li>{{\$error}}</li>\n\t\t@endforeach\n\t</ul>\n@endif";
    }

    /**
     * Create 'input' HTML element based on bootstrap usage
     * @param $field
     * @param string $class
     * @param string $errors
     * @return string
     */
    private function fieldInput($field, string $class, string $errors): string
    {
        if ($this->layout_bootstrap) {
            return "<div class=\"form-group\">\n\t<label for=\"{$field->info->id}\">{$field->info->title}</label>\n\t<input type=\"{$field->args}\" name=\"{$field->info->name}\" value=\"{{\$item ? (\$item->{$field->info->name} ?: '') : old('{$field->info->name}')}}\" class=\"form-control $class\" id=\"{$field->info->id}\"{$field->info->end}>\n\n\t$errors\n</div>";
        } else {
            return "<label for=\"{$field->info->id}\">{$field->info->title}</label>\n<input type=\"$field->args\" name=\"{$field->info->name}\" value=\"{{\$item ? (\$item->{$field->info->name} ?: '') : old('{$field->info->name}')}}\" class=\"$class\" id=\"{$field->info->id}\"{$field->info->end}>\n$errors";
        }
    }

    /**
     * Create 'textarea' HTML element based on bootstrap usage
     * @param $field
     * @param string $class
     * @param string $errors
     * @return string
     */
    private function fieldTextarea($field, string $class, string $errors): string
    {
        if ($this->layout_bootstrap) {
            return "<div class=\"form-group\">\n\t<label for=\"{$field->info->id}\">{$field->info->title}</label>\n\t<textarea name=\"{$field->info->name}\" class=\"form-control $class\" id=\"{$field->info->id}\"{$field->info->end}>{{\$item ? (\$item->{$field->info->name} ?: '') : old('{$field->info->name}')}}</textarea>\n\n\t$errors\n</div>";
        } else {
            return "<label for=\"{$field->info->id}\">{$field->info->title}</label>\n<textarea name=\"{$field->info->name}\" class=\"$class\" id=\"{$field->info->id}\"{$field->info->end}>{{\$item ? (\$item->{$field->info->name} ?: '') : old('{$field->info->name}')}}</textarea>\n$errors";
        }
    }

    /**
     * Create 'select' HTML element based on bootstrap usage
     * @param $field
     * @param $class
     * @param $errors
     * @return string
     */
    private function fieldSelect($field, string $class, string $errors): string
    {
        $options = $field->options->map(function ($item) use ($field) {
            return "<option value=\"$item\" {{(\$item ? (\$item->{$field->info->name} ?: '') : old('{$field->info->name}')) === '$item' ? \"selected\" : \"\"}}>".Str::title($item)."</option>";
        })->when($field->default, function ($collection) use ($field) {
            return $collection->prepend("\n\t\t<option selected disabled>$field->default</option>");
        })->join("\n\t\t");
        $multiple = $field->args === "multiple" ? " multiple" : "";
        if ($this->layout_bootstrap) {
            return "<div class=\"form-group\">\n\t<label for=\"{$field->info->id}\">{$field->info->title}</label>\n\t<select name=\"{$field->info->name}\" class=\"form-control $class\" id=\"{$field->info->id}\"{$field->info->required}{$field->info->hidden}$multiple>$options</select>\n\n\t$errors\n</div>";
        } else {
            return "<label for=\"{$field->info->id}\">{$field->info->title}</label>\n<select name=\"{$field->info->name}\" class=\"$class\" id=\"{$field->info->id}\"{$field->info->required}{$field->info->hidden}$multiple>$options</select>\n$errors";
        }
    }

    /**
     * Create 'select' HTML element based on bootstrap usage
     * @param $field
     * @param $class
     * @param $errors
     * @return string
     */
    private function fieldRelationship($field, string $class, string $errors): string
    {
        $model = Str::start($field->related, "\\");
        $options = "\n\t@foreach($model::all() as \$option)\n\t\t<option value=\"{{\$option->id}}\" {{(\$item ? (\$item->{$field->info->name}_id ?: '') : old('type')) == \$option->id ? \"selected\" : \"\"}}>{{\$option->title}}</option>\n\t@endforeach\n";
        $multiple = $field->args === "multiple" ? " multiple" : "";
        if ($this->layout_bootstrap) {
            return "<div class=\"form-group\">\n\t<label for=\"{$field->info->id}\">{$field->info->title}</label>\n\t<select name=\"{$field->info->name}_id\" class=\"form-control $class\" id=\"{$field->info->id}\"{$field->info->required}{$field->info->hidden}$multiple>$options</select>\n\n\t$errors\n</div>";
        } else {
            return "<label for=\"{$field->info->id}\">{$field->info->title}</label>\n<select name=\"{$field->info->name}_id\" class=\"$class\" id=\"{$field->info->id}\"{$field->info->required}{$field->info->hidden}$multiple>$options</select>\n$errors";
        }
    }
}
