<?php
/*
|--------------------------------------------------------------------------
| Fibers ignite command [php artisan fibers:ignite <Title> [Options]]
|--------------------------------------------------------------------------
|
| Fibers Rocket - Ignite will bootstrap your fresh install of Laravel
| and deal with some (optional) common first steps
|
*/

namespace Fibers\Rocket\Commands;

use Fibers\Rocket\Command;
use Fibers\Helper\Facades\TemplateHelper;
use Fibers\Helper\Facades\UserHelper;
use Fibers\Helper\Facades\ViewHelper;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class Ignite extends Command
{
    protected $signature =  'fibers:ignite
                            {--A|auth : Set up Laravel auth }
                            {--M|models : Set up \'Models\' folder }
                            {--L|layouts : Set up layout views }
                            {--I|internationals : Set up your app languages }
                            {--B|blanks : Remove unnecessary dependencies }
                            {--C|configs : Publish this package config files }';
    protected $description = 'Prepare Laravel application for rapid prototyping';

    /**
     * Command closure
     * Get user input, normalize it, parse it to model data, create model file
     */
    public function handle()
    {
        parent::handle();

        $this->auth();

        $this->models();

        $this->layouts();

        $this->internationals();

        $this->blanks();

        $this->configs();
        // "Your app structure is now ready! Run php artisan fibers:create Name to get rocking!"
    }

    /**
     * Scaffold Laravel Auth and set MustVerifyMail
     */
    private function auth (): void
    {
        $this->prompt("Will you use authentication?", function () {
            $user = UserHelper::collect('class', 'model', 'implements', 'content', 'filepath');
            // create auth
            if (!$user->model) {
                $this->call('make:auth');
                $this->infoDelayed("Auth scaffolding created", "success");
            }
            // implement 'MustVerifyEmail' interface
            $this->prompt("Do you want new user to confirm their email addresses?", function () use ($user) {
                // rewrite User model
                if (!$user->implements->contains('Illuminate\Contracts\Auth\MustVerifyEmail')) {
                    // read user file
                    $userModelContent = $user->content;
                    // preg replace user file
                    if (preg_match("/^(?:class User extends \w+\s?(?:implements (.*))?)$/m", $userModelContent, $matches)) {
                        if (isset($matches[1])) {
                            $userModelContent = str_replace($matches[0], $matches[0] . ", MustVerifyEmail", $userModelContent);
                        } else {
                            $userModelContent = str_replace($matches[0], $matches[0] . " implements MustVerifyEmail", $userModelContent);
                        }
                        // save user file
                        file_put_contents($user->filepath, $userModelContent);
                        $this->infoDelayed("'MustVerifyEmail' interface set in {$user->class}");
                    }
                    // rewrite routes/web.php
                    if (preg_match("/^<\?php\s*(?:\/\*.*\*\/)?(\n(?:.*))$/s", file_get_contents(base_path('routes/web.php')), $matches)) {
                        if (strpos("Auth::routes(['verify' => true]);", $matches[0]) === false and isset($matches[1])) {
                            file_put_contents(base_path('routes/web.php'), str_replace($matches[1], "\n\nAuth::routes(['verify' => true]);" . $matches[1], $matches[0]));
                            $this->infoDelayed("Email verification added to routes");
                        }
                    };
                } else {
                    // rewrite routes/web.php
                    if (preg_match("/^<\?php\s*(?:\/\*.*\*\/)?(\n(?:.*))$/s", file_get_contents(base_path('routes/web.php')), $matches)) {
                        if (strpos("Auth::routes(['verify' => true]);", $matches[0]) === false and isset($matches[1])) {
                            file_put_contents(base_path('routes/web.php'), str_replace($matches[1], "\n\nAuth::routes(['verify' => true]);" . $matches[1], $matches[0]));
                            $this->infoDelayed("Email verification added to routes");
                        }
                    };
                }
            });
        }, 'auth');
    }

    private function models ()
    {
        // "Will you use 'Models' directory for storing your models?" (and move User model there)
        $this->prompt("Will you use 'Models' directory for storing your models?", function () {
            // create a folder
            if (!file_exists(app_path('Models'))) {
                mkdir(app_path('Models'));
                $this->infoDelayed("'Models' folder created [app/Models/]", "success");
            }

            // move user model there
            if (UserHelper::exists()) {
                $user = UserHelper::collect('filepath', 'content', 'class', 'name');
                // change namespace in User model
                preg_match("/^namespace (.*);$/m", $user->content, $matches);
                if (preg_match("/^namespace (.*);$/m", $user->content, $matches)) {
                    $namespace = Str::after($matches[1], 'App');
                    $namespace = !blank($namespace) ? Str::start($namespace, '\\') : '';
                    $namespace = 'App\\Models'.$namespace;

                    $content = str_replace($matches[1], $namespace, $user->content);
                    // write in new file
                    file_put_contents(app_path('Models').DIRECTORY_SEPARATOR.basename($user->filepath), $content);
                    // remove old file
                    unlink($user->filepath);
                    // set new path in config
                    $providers = config('auth.providers.users');
                    $providers['model'] = "{$namespace}\\{$user->name}";
                    Config::write('auth.providers.users', $providers);
                    $this->infoDelayed("User model was moved to 'Models' folder [app/Models/User.php]", "success");
                }
            }
            else {
                $this->infoDelayed("User model was not moved to 'Models' folder, because it was no found.", "info");
            }
        }, 'models');
    }

    private function layouts ()
    {
        // "Do you want to create a default view folder structure?"
        // "What folder name will you use for your layout views" "layouts"
        // "What folder name will you use for your partial views" "partials"
        // "Do you want to create app layout, header and footer?"
        $this->prompt("Do you want to create a default view folder structure?", function () {
            if (!ViewHelper::layoutsPath()) {
                $layouts = $this->ask("What folder name will you use for your layout views", "layouts");
                mkdir(ViewHelper::path().DIRECTORY_SEPARATOR.$layouts);
                $this->infoDelayed("Layout views folder was created [resources/".basename(ViewHelper::path())."/".$layouts."/]","success");
            }
            if (!ViewHelper::componentsPath()) {
                $partials = $this->ask("What folder name will you use for your partial views", "partials");
                mkdir(ViewHelper::path().DIRECTORY_SEPARATOR.$partials);
                $this->infoDelayed("Partial views folder was created [resources/".basename(ViewHelper::path())."/".$partials."/]","success");
            }
            $this->prompt("Do you want to create app layout, header and footer?", function () {
                // create header & footer components
                if ($componentsPath = ViewHelper::componentsPath()) {
                    TemplateHelper::fromFile(FIBERS_ROCKET."/templates/views/partial/header.stub")->replace()->tofile($componentsPath.DIRECTORY_SEPARATOR.'header.blade.php');
                    TemplateHelper::fromFile(FIBERS_ROCKET."/templates/views/partial/footer.stub")->replace()->tofile($componentsPath.DIRECTORY_SEPARATOR.'footer.blade.php');
                    $this->infoDelayed("Header and footer components were created [resources/".basename(ViewHelper::path())."/".basename($componentsPath)."/]","success");
                }

                // create app layout
                if ($layoutsPath = ViewHelper::layoutsPath()) {
                    $componentsDir = basename($componentsPath);
                    TemplateHelper::fromFile(FIBERS_ROCKET."/templates/views/layout/app.stub")->replace([
                        "component.header" => "@component('{$componentsDir}.header') @endcomponent",
                        "component.footer" => "@component('{$componentsDir}.footer') @endcomponent"
                    ])->tofile($layoutsPath.DIRECTORY_SEPARATOR.'app.blade.php');
                    $this->infoDelayed("App layout was created [resources/".basename(ViewHelper::path())."/".basename($layoutsPath)."/app.blade.php]","success");
                }
            });
        },'layouts');
    }

    private function internationals ()
    {
        // "What languages will your app support?" (create language files and fetch default translation files from github)
        // "What is the default language for your app?"
        $this->prompt("Do you want to set up language(s)?", function () {
            $languages = collect(explode(",", $this->ask("What languages will your app support? <comment>[comma separated values]</comment>", "en")))
                ->map(function ($item) {
                    return trim($item);
                })
                ->delete('en')
                ->each(function ($lang) {
                    $this->call("fibers:language", ["title" => $lang]);
                });

            if ($languages->count()) {
                $defaultLanguage = $this->choice('What is the default language?', $languages->prepend('en')->mapWithKeys(function ($locale) {
                    return [$locale => locale_get_display_language($locale, "en")];
                })->toArray(), 'en');
                // change default locale if nessesary
                if ($defaultLanguage !== 'en') {
                    Config::write('app.locale', $defaultLanguage);
                    $this->infoDelayed("Default locale set to '$defaultLanguage'", "success");
                }
            }
        }, 'internationals');
    }

    private function blanks ()
    {
        // "Do you want to remove unnecessary dependencies?"
        $this->prompt("Do you want to remove unnecessary dependencies?", function () {
            $dependencies = collect($this->pick("Which dependencies would you like to remove?", [
                'jquery' => 'HTML DOM tree traversal and manipulation library (used by bootstrap)',
                'lodash' => 'Utility library. Sort of like Laravel Str & Arr helpers in JavaScript',
                'popper' => 'Tooltip library',
                'vue' => 'Frontend library for building UI',
                'bootstrap' => 'CSS framework directed at responsive, mobile-first front-end web development'
            ], null, true));
            // if there is anything to remove
            if ($dependencies->count()) {
                // load files
                $bootstrap_js = file_get_contents(resource_path('js/bootstrap.js'));
                $app_js = file_get_contents(resource_path('js/app.js'));
                $app_scss = file_get_contents(resource_path('sass/app.scss'));
                $changes = ["bootstrap_js" => 0, "app_js" => 0, "app_scss" => 0];

                if ($dependencies->contains('jquery') and $dependencies->contains('bootstrap')) {
                    $bootstrap_js = str_replace("window.$ = window.jQuery = require('jquery');", "//window.$ = window.jQuery = require('jquery');", $bootstrap_js);
                    $changes["bootstrap_js"]++;
                }
                if ($dependencies->contains('lodash')) {
                    $bootstrap_js = str_replace("window._ = require('lodash');", "//window._ = require('lodash');", $bootstrap_js);
                    $changes["bootstrap_js"]++;
                }
                if ($dependencies->contains('popper')) {
                    $bootstrap_js = str_replace("window.Popper = require('popper.js').default;", "//window.Popper = require('popper.js').default;", $bootstrap_js);
                    $changes["bootstrap_js"]++;
                }
                if ($dependencies->contains('vue')) {
                    $app_js = str_replace("window.Vue = require('vue');", "//window.Vue = require('vue');", $app_js);
                    $app_js = str_replace("Vue.component('example-component', require('./components/ExampleComponent.vue').default);", "//Vue.component('example-component', require('./components/ExampleComponent.vue').default);", $app_js);
                    $app_js = str_replace("const app = new Vue({\n    el: '#app',\n});", "//const app = new Vue({\n//    el: '#app',\n//});", $app_js);
                    $changes["app_js"]++;
                }
                if ($dependencies->contains('bootstrap')) {
                    $bootstrap_js = str_replace("require('bootstrap');", "//require('bootstrap');", $bootstrap_js);
                    $app_scss = str_replace("@import '~bootstrap/scss/bootstrap';", "//@import '~bootstrap/scss/bootstrap';", $app_scss);
                    $changes["bootstrap_js"]++;
                    $changes["app_scss"]++;
                }

                // write files
                if ($changes['bootstrap_js']) file_put_contents(resource_path('js/bootstrap.js'), $bootstrap_js);
                if ($changes['app_js']) file_put_contents(resource_path('js/app.js'), $app_js);
                if ($changes['app_scss']) file_put_contents(resource_path('sass/app.scss'), $app_scss);

                $this->infoDelayed("Removed dependencies " . $dependencies->map(function ($item) { return "'$item'"; })->join(', '), "success");
            }
        }, 'blanks');

        // > make a select element for removing bootstrap, jquery, vue, lodash, popper, google fonts (Nunito)

    }

    private function configs ()
    {
        // "Do you want to publish config files for this package?"
        $this->prompt("Do you want to publish config files for this package?",function () {
            $this->callSilent('vendor:publish', ['--tag' => 'config', '--provider' => 'Fibers\Helper\FibersHelperServiceProvider']);
            $this->infoDelayed("Fibers config files published", "success");
        }, 'configs');
    }

    private function prompt(string $message, \Closure $closure, $option = false)
    {
        if ($this->option($option) or $this->confirm($message, "yes")) {
            $closure();
        }
    }
}
