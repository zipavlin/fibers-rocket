<?php
/*
|--------------------------------------------------------------------------
| Fibers model command [php artisan fibers:model Name]
|--------------------------------------------------------------------------
|
| This command will create a FormRequest, automatically population rules
| array with attributes collected from a model.
|
*/

namespace Fibers\Rocket\Commands;

use Fibers\Rocket\Command;
use Fibers\Helper\Facades\ModelsHelper;
use Fibers\Helper\Facades\TemplateHelper;
use Illuminate\Support\Str;

class Guard extends Command
{
    protected $signature =  'fibers:guard
                            {title : Title of guard}
                            {--target= : Target model }';
    protected $description = 'Create Laravel FormRequest';

    private $guard_title; // ClassTitle
    private $guard_classname; // ClassTitleController
    private $guard_rules; // collection of rules if FormRequest is not used
    private $guard_target;

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
        $this->success = $this->createGuard();
    }

    /**
     * Collect data and create new controller file
     * @return bool
     */
    private function createGuard(): bool
    {
        // write template
        $this->checkFileExist(app_path("Http/Requests/$this->guard_classname.php"), function ($path) {
            TemplateHelper::fromFile(FIBERS_ROCKET."/templates/guard.stub")->replace([
                "name" => $this->guard_classname,
                "rules" => TemplateHelper::array($this->guard_rules)
            ])->tofile($path);

            // output to user
            $this->infoDelayed("Request created [app/Http/Requests/$this->guard_classname.php]");
        });

        return true;
    }

    private function collectInput()
    {
        // collect title
        $this->guard_classname = Str::finish(Str::studly(Str::singular(class_basename($this->argument('title')))), "Request");
        $this->guard_title = Str::before($this->guard_classname, 'Request');
        $this->guard_target = $this->getTarget($this->guard_title);

        // collect field rules if we are not using FormRequest
        if ($model = ModelsHelper::get($this->guard_target)) {
            $this->guard_rules = $model->rules();
        } else {
            $this->guard_rules = collect();
        }
    }
}
