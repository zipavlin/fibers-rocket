# Make Guard

```bash
php artisan fibers:make:guard <Title> [Options]
```

Guard command will create a [FormRequest](https://laravel.com/docs/validation#form-request-validation), automatically populating _rules_ array with attributes collected from a [model](/fibers-rocket/commands/model).

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
| `--target=` | Target model |

::: tip
Use `--silent|S` option to suppress unnecessary input prompt and to populate data automatically.
:::
