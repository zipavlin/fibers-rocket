# Setup App

```bash
php artisan fibers:setup:app [Options]
```

Fibers Rocket - Ignite will bootstrap your fresh install of Laravel and deal with some (optional) common first steps:
* scaffolding authentication
* creating 'Models' directory
* preparing folders for view layout & partials
* setting additional languages
* removing unnecessary javascript and sass dependencies 
* publishing Fibers config

## Options
| Command | Description |
| --- | --- |
| `--auth|A`   | Will scaffold default Laravel auth with (optional) email confirmation. |
| `--models|M` | Will create a app/Models folder and move User model there. |
| `--layouts|L` | Will create folders for *layouts* and *partials* blade templates. It can also create *header*, *footer* partials and *app* layout. |
| `--languages|I` | Will add a new language to app by creating a new folder in  *langs* and download language files from [GitHub](https://github.com/caouecs/Laravel-lang). |
| `--blanks|B` | Will remove unneeded dependencies like bootstrap, jquery, ... |
| `--configs|C` | Will publish Fibers config file. |
