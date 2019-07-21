<?php
/*
|--------------------------------------------------------------------------
| Fibers make:controller command [php artisan fibers:make:controller <Title> [Options]]
|--------------------------------------------------------------------------
|
| This command will create a new controller and optionally continue to
| creating a guard, route, layout and/or model. It tries to speed up controller creation by automatically
| filling usual boilerplate parts.
|
| TODO: add policy
| TODO: add option for breadcrumbs
*/

namespace Fibers\Rocket\Commands;

use Fibers\Rocket\Command;
use Fibers\Helper\Facades\ModelsHelper;
use Fibers\Helper\Facades\TemplateHelper;
use Illuminate\Support\Str;

class Controller extends Command
{
    protected $signature =  'fibers:make:controller
                            {title : Title of controller}
                            {--G|guard : Create and use guarded request }
                            {--R|route : Add controller to routes }
                            {--L|layout : Create layout view files as well }
                            {--M|model : Create model file as well }
                            {--paginated : Create a paginated index }
                            {--target= : Target model }
                            {--only= : Comma separated collection of controller methods }
                            {--except= : Comma separated collection of ignored controller methods }';
    protected $description = 'Create Laravel model';

    private $controller_title; // ClassTitle
    private $controller_classname; // ClassTitleController
    private $controller_variable; // classTitle
    private $controller_only_actions; // only methods
    private $controller_except_actions; // except methods
    private $controller_model; // target model
    private $controller_request; // bool value if FormRequest is present/will be created
    private $controller_rules; // collection of rules if FormRequest is not used
    private $controller_properties = []; // private properties to attach to controller
    private $controller_import = []; // import statements to attach to controller

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
        $this->success = $this->createController();

        // continue to other commands
        $this->continue("route", [
            "title" => $this->controller_title,
            "--ignore" => ['controller'],
            "--target" => $this->controller_title,
            "--only" => !blank($this->controller_only_actions) ? $this->controller_only_actions->join(",") : false,
            "--except" => !blank($this->controller_except_actions) ? $this->controller_except_actions->join(",") : false
        ]);
        $this->continue("layout", [
            "title" => $this->controller_title,
            "--ignore" => ['controller'],
            "--paginated" => $this->option("paginated"),
            "--only" => !blank($this->controller_only_actions) ? $this->controller_only_actions->join(",") : false,
            "--except" => !blank($this->controller_except_actions) ? $this->controller_except_actions->join(",") : false,
            "--target" => $this->controller_title
        ]);
        $this->continue("model", function () {
            if (!ModelsHelper::exists(Str::studly(class_basename($this->controller_title)))) {
                if ($this->confirm("Do you want to create model file for this controller?", "yes")) {
                    $this->call("fibers:make:model", ["title" => $this->controller_title, "--silent" => $this->silent, "--force" => $this->force, '--ignore' => ['controller']]);
                }
            }
        });
        if ($this->controller_request and !in_array('guard', $this->option('ignore'))) {
            $this->call("fibers:make:guard", ["title" => $this->controller_title]);
        }
    }

    /**
     * Collect data and create new controller file
     * @return bool
     */
    private function createController(): bool
    {
        // collect actions
        $actions = collect(['index', 'show', 'create', 'store', 'edit', 'update', 'destroy'])
            ->when($this->controller_only_actions->count(), function ($collection) {
                return $collection->intersect($this->controller_only_actions)->values();
            })
            ->diff($this->controller_except_actions)
            ->prepend('construct')
            ->map(function ($item) {
                if (method_exists($this, $item)) {
                    return $this->{$item}();
                } else {
                    return null;
                }
            })
            ->filter();
        
        // write template
        $this->checkFileExist(app_path("Http/Controllers/$this->controller_classname.php"), function ($path) use ($actions) {
            TemplateHelper::fromFile(FIBERS_ROCKET."/templates/controller.stub")->replace([
                "import" => collect($this->controller_import)->map(function ($item) { return "use $item;"; })->join("\n"),
                "name" => $this->controller_classname,
                "properties" => Str::finish(collect($this->controller_properties)->map(function ($item, $key) { return "\tprivate \$$key = $item;"; })->join("\n"), "\n"),
                "actions" => $actions->join("\n\n")
            ])->tofile($path);

            // output to user
            $this->infoDelayed("Controller created [app/Http/Controllers/$this->controller_classname.php]");
        });

        return true;
    }

    private function collectInput()
    {
        // collect title
        $this->controller_classname = Str::finish(Str::studly(Str::singular(class_basename($this->argument('title')))), "Controller");
        $this->controller_title = Str::before($this->controller_classname, 'Controller');
        $this->controller_variable = Str::camel($this->controller_title);
        $this->controller_request = ($this->option('guard') or (!$this->silent and $this->confirm("Would you like to create FormRequest as well?", "yes")));

        // collect target
        $this->controller_model = $this->getTarget($this->controller_title);
        
        // push model to import
        array_push($this->controller_import, 'Illuminate\Routing\Controller');
        array_push($this->controller_import, 'Illuminate\Support\Facades\View');
        array_push($this->controller_import, 'Illuminate\Support\Str');
        array_push($this->controller_import, ModelsHelper::class($this->controller_title));

        // collect only & except collection
        $this->controller_only_actions = $this->inputCollection("only", "What resource actions would you like to include?");
        $this->controller_except_actions = $this->inputCollection("except", "What resource actions would you like to exclude?");

        // set request
        if ($this->controller_request) {
            $request = Str::finish($this->controller_title, "Request");
            array_push($this->controller_import, "App\Http\Requests\\$request as Request");
        } else {
            array_push($this->controller_import, "Illuminate\Http\Request");
        }

        // collect field rules if we are not using FormRequest
        if (!$this->controller_request) {
            if ($model = ModelsHelper::get($this->controller_title)) {
                $this->controller_rules = $model->rules();
            } else {
                $this->controller_rules = collect();
            }
            $this->controller_properties['rules'] = TemplateHelper::array($this->controller_rules);
        }

        // add title property
        $this->controller_properties['title'] = TemplateHelper::string(Str::title($this->controller_title));
        $this->controller_properties['route'] = TemplateHelper::string(Str::slug(Str::singular($this->controller_title)));
    }

    private function wrap(string $name, array $content, ...$arguments)
    {
        $args = collect();
        foreach ($arguments as $argument) {
            if ($argument === 'model') $args->push("{$this->controller_title} \${$this->controller_variable}");
            elseif ($argument === 'request') $args->push("Request \$request");
        }
        $args = $args->join(", ");
        return "\tpublic function $name($args)\n\t{\n".collect($content)->map(function ($item) { return "\t\t$item"; })->join("\n")."\n\t}";
    }

    private function construct($body = [])
    {
        return $this->wrap("__construct", array_merge($body, [
            "View::share(\"title\", (Object) [\"singular\" => Str::ucfirst(Str::singular(\$this->title)), \"plural\" => Str::ucfirst(Str::plural(\$this->title))]);",
            "View::share(\"route\", \$this->route);"
        ]));
    }

    private function index($body = [])
    {
        // check if model has 'created_at' column
        if ($model = ModelsHelper::get($this->controller_model) and $model->attributes()->contains("created_at")) {
            $args = "latest()" . ($this->option('paginated') ? "->paginate(16)" : "->get()");
        } else {
            $args = $this->option('paginated') ? "paginate(16)" : "all()";
        }
        return $this->wrap("index", array_merge($body, [
            "\$items = {$this->controller_title}::$args;",
            "return view('{$this->controller_variable}.index')->with('items', \$items);"
        ]));
    }

    private function create($body = [])
    {
        return $this->wrap("create", array_merge($body, [
            "return view('{$this->controller_variable}.create');",
        ]));
    }

    public function store($body = [])
    {
        // when FormRequest exists
        if ($this->controller_request) {
            return $this->wrap("store", array_merge($body, [
                "if (\$request->validated()) {",
                    "\t{$this->controller_title}::create(\$request->except(['_token', '_method']));",
                    "\treturn redirect()->action('{$this->controller_classname}@index');",
                "} else {",
                    "\treturn back()->withInput()->withErrors();",
                "}"
            ]), 'request');
        }
        // when FormRequest does not exist and rules are defined in controller
        else {
            return $this->wrap("store", array_merge($body, [
                "\$request->validate(\$this->rules);",
                "{$this->controller_title}::create(\$request->except(['_token', '_method']));",
                "return redirect()->action('{$this->controller_classname}@index');",
            ]), 'request');
        }
    }

    public function show($body = [])
    {
        return $this->wrap("show", array_merge($body, [
            "return view('{$this->controller_variable}.show')->with('item', \${$this->controller_variable});"
        ]), 'model');
    }

    public function edit($body = [])
    {
        return $this->wrap("edit", array_merge($body, [
            "return view('{$this->controller_variable}.edit')->with('item', \${$this->controller_variable});"
        ]), 'model');
    }

    public function update($body = [])
    {
        // when FormRequest exists
        if ($this->controller_request) {
            return $this->wrap("update", array_merge($body, [
                "if (\$request->validated()) {",
                    "\tforeach (\$request->except(['_token', '_method']) as \$attribute => \$input) {",
                        "\t\t\${$this->controller_variable}->{\$attribute} = \$input;",
                    "\t}",
                    "\t\${$this->controller_variable}->save();",
                    "\treturn view('{$this->controller_variable}.edit')->with('item', \${$this->controller_variable});",
                "} else {",
                    "\treturn back()->withInput()->withErrors();",
                "}"
            ]), 'request', 'model');
        }
        // when FormRequest does not exist and rules are defined in controller
        else {
            return $this->wrap("update", array_merge($body, [
                "\$request->validate(\$this->rules);",
                "foreach (\$request->except(['_token', '_method']) as \$attribute => \$input) {",
                    "\t\${$this->controller_variable}->{\$attribute} = \$input;",
                "}",
                "\${$this->controller_variable}->save();",
                "return view('{$this->controller_variable}.edit')->with('item', \${$this->controller_variable});"
            ]), 'request', 'model');
        }
    }

    public function destroy($body = [])
    {
        return $this->wrap("destroy", array_merge($body, [
            "\${$this->controller_variable}->delete();",
            "return redirect()->action('{$this->controller_classname}@index');"
        ]), 'model');
    }
}
