<?php
/*
|--------------------------------------------------------------------------
| Fibers language command [php artisan fibers:language <Title>]
|--------------------------------------------------------------------------
|
| Language command will setup a new language, creating a new language folder,
| automatically downloading standard language files and copying custom files.
|
*/

namespace Fibers\Rocket\Commands;

use Fibers\Rocket\Command;
use Illuminate\Support\Str;

class Language extends Command
{
    protected $signature =  'fibers:language
                            {title : Language name or code }';
    protected $description = 'Set up translation files for a new language';

    private $language_code;
    private $language_default;
    private $language_exists;
    private $language_standard = ['auth.php', 'pagination.php', 'passwords.php', 'validation.php'];

    /**
     * Command closure
     * Get user input, normalize it, parse it to model data, create model file
     */
    public function handle()
    {
        parent::handle();

        // get language code
        $this->language_code = Str::lower(strlen($this->argument('title')) === 2 ? $this->argument('title') : substr($this->argument('title'), 0, 2));

        // get default language
        $this->language_default = config('app.locale');

        // check if language exists on github
        $this->language_exists = $this->url_exists("https://raw.githubusercontent.com/caouecs/Laravel-lang/master/src/{$this->language_code}/validation.php");

        // check if language folder exists else create
        if (!file_exists(resource_path("lang/{$this->language_code}"))) mkdir(resource_path("lang/{$this->language_code}"));

        // copy non standard files from default language
        collect(glob(resource_path("lang/{$this->language_default}/*")))
            // remove standard files if lang exists on github
            ->when($this->language_exists, function ($collection) {
                return $collection->diff($this->language_standard);
            })
            // copy files
            ->each(function ($file) {
                $filename = basename($file);
                if (!file_exists(resource_path("lang/{$this->language_code}/$filename"))) {
                    copy($file, resource_path("lang/{$this->language_code}/$filename"));
                }
            });

        // try to get language files from github
        if ($this->language_exists) {
            foreach ($this->language_standard as $file) {
                $url = "https://raw.githubusercontent.com/caouecs/Laravel-lang/master/src/{$this->language_code}/$file";
                if ($this->url_exists($url) and !file_exists(resource_path("lang/{$this->language_code}/$file"))) {
                    $homepage = file_get_contents($url);
                    $handle = fopen(resource_path("lang/{$this->language_code}/$file"), "w");
                    fwrite($handle, $homepage);
                    fclose($handle);
                }
            }
        }

        // finish
        $title = locale_get_display_language($this->language_code, "en");
        $this->infoDelayed("{$title} language files set [resources/lang/{$this->language_code}/]", "success");
    }

    private function internationals ()
    {
        // "What languages will your app support?" (create language files and fetch default translation files from github)
        // "What is the default language for your app?"
        $this->prompt("Do you want to set up language(s)?", function () {
            collect(explode(",", $this->ask("What languages will your app support? <comment>[comma separated values]</comment>", "en")))
                ->map(function ($item) {
                    return trim($item);
                })
                ->delete('en')
                ->each(function ($lang) {
                    // check if translations for this lang exist
                    if ($this->url_exists("https://raw.githubusercontent.com/caouecs/Laravel-lang/master/src/$lang/validation.php")) {

                        // create a new folder if it doesnt't exist
                        if (!file_exists(resource_path("lang/$lang"))) mkdir(resource_path("lang/$lang"));

                        // download all missing files [auth.php, pagination.php, passwords.php, validation.php]
                        foreach (['auth.php', 'pagination.php', 'passwords.php', 'validation.php'] as $file) {
                            $url = "https://raw.githubusercontent.com/caouecs/Laravel-lang/master/src/$lang/$file";
                            if ($this->url_exists($url) and !file_exists(resource_path("lang/$lang/$file"))) {
                                $homepage = file_get_contents($url);
                                $handle = fopen(resource_path("lang/$lang/$file"),"w");
                                fwrite($handle,$homepage);
                                fclose($handle);
                            }
                        }

                        $this->infoDelayed("'$lang' language files downloaded [resources/lang/$lang/]", "success");
                    }
                });
        });
    }

    private function get_headers ($url)
    {
        // returns string responsecode, or false if no responsecode found in headers (or url does not exist)
        if(! $url || ! is_string($url)){
            return false;
        }
        $headers = @get_headers($url);
        if($headers && is_array($headers)){
            foreach($headers as $hline){
                // search for things like "HTTP/1.1 200 OK" , "HTTP/1.0 200 OK" , "HTTP/1.1 301 PERMANENTLY MOVED" , "HTTP/1.1 400 Not Found" , etc.
                // note that the exact syntax/version/output differs, so there is some string magic involved here
                if(preg_match('/^HTTP\/\S+\s+([1-9][0-9][0-9])\s+.*/', $hline, $matches) ){// "HTTP/*** ### ***"
                    $code = $matches[1];
                    return intval($code);
                }
            }
            // no HTTP/xxx found in headers:
            return false;
        }
        // no headers :
        return false;
    }

    private function url_exists($url)
    {
        return $this->get_headers($url) === 200;
    }
}
