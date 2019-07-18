# Make Model

```bash
php artisan fibers:make:model <Title> [Options]
```

Fibers Rocket considers [Models](https://laravel.com/docs/eloquent) to be a first-class citizens (alongside its table) and are it's entry point into your application when fetching required information. Models are used to smartly populate data when creating controllers, layours, guards, etc.  

Model will be created either in `app` or `app/Models` folder, depending on location of other models, it's namespace will be set accordingly, it's attributes will be [appropriately cast](/fibers-rocket/attributes#types) and relationships will be set.  

Some attributes will be ignored by default and will not have corresponding fields and will not be cast:
* id
* uuid
* created_at
* updated_at
* deleted_at
* timestamps
* timestampstz
* softdeletes
* softdeletestz

Other attributes will be automatically hidden from array export:
* password
* remember_token

::: tip
Attributes are automatically added to _fillable_ array for fast prototyping (unless they are hidden or ignored). Don't forget to remove them if necessary.
:::

## Attribute Input
Model creation is simple, but does require user's help to set appropriate attributes. This multiline input uses simplified (but modified) migration syntax. **Read more about attribute input [**here**](/fibers-rocket/attributes).**  

::: tip
Some magic attribute types add additional traits to models. You can set them in `config/fibers.traits`.
:::

## Parameters
| Parameter | Description |
| --- | --- |
| `title` | Title is used also for other naming derivatives. It is normalized automatically. |

## Options
| Command | Description |
| --- | --- |
| `--migration|M` | Will create migration file as well using same attributes |

::: tip
Use `--silent|S` option to suppress unnecessary input prompt and to populate data automatically.
:::
