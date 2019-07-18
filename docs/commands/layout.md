# Make Layout

```bash
php artisan fibers:make:layout <Title> [Options]
```

This command will create layout [Views](https://laravel.com/docs/views) in path set in `config/view.paths` into a new folder named after targeted model.  

Command creates only view that are needed, based on either `only/except` options or targeted model's controller if one is found:
* **index**: a table list of model's records (uses `_item.blade.php`)
* **show**: show single record's data
* **create**: create a new record (uses `_form.blade.php`)
* **edit**: edit existing record (uses `_form.blade.php`)
* **_item**: partial view with a single record data
* **_form**: partial view with model's attributes

::: tip
Set targeted model by using `--target=` options to skip model input prompt.
:::

## Parameters
| Parameter | Description |
| --- | --- |
| `title` | Title is used also for other naming derivatives. It is normalized automatically. |

## Options
| Command | Description |
| --- | --- |
| `--controller|C` | Will create controller as well |
| `--bootstrap` | Will use bootstrap when creating views |
| `--paginated` | Will use pagination in index view |
| `--target=` | Target model |
| `--only=` | Comma separated collection of controller methods |
| `--except=` | Comma separated collection of ignored controller methods |

::: tip
Use `--silent|S` option to suppress unnecessary input prompt and to populate data automatically.
:::
