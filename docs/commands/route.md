# Make Route

```bash
php artisan fibers:make:route <Title> [Options]
```

This command will append a new [Route](https://laravel.com/docs/routing) to file set in `config/fibers.routes` (defaults to `routes/web.php`).  

Command creates a [resource](https://laravel.com/docs/controllers#resource-controllers) route and populates either _only_ or _except_ parameter, depending on which is shortest, based on either `only/except` options or targeted model's controller if one is found.

Route name is a slug version of targeted model name and is prepared for model dependency modification using _route parameter_.

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
| `--target=` | Target model |
| `--only=` | Comma separated collection of controller methods |
| `--except=` | Comma separated collection of ignored controller methods |

::: tip
Use `--silent|S` option to suppress unnecessary input prompt and to populate data automatically.
:::
