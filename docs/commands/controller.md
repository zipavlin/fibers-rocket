# Make Controller

```bash
php artisan fibers:make:controller <Title> [Options]
```

This command will create a new [Controller](https://laravel.com/docs/controllers) and optionally continue creating a guard, route, layout and/or model. It tries to speed up controller creation by automatically filling usual boilerplate parts.

Controller has injected model dependency (based on targeted model) and is using either FormRequest validation or standard request validation based on `private $rules` array set in controller. It also has some useful info shared between views, like singular & plural title and route base.

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
| `--guard|G` | Will create and use guarded request |
| `--route|R` | Will add controller to routes |
| `--layout|L` | Will create layout view files as well |
| `--model|M` | Will create model file as well |
| `--paginated` | Will use pagination in index action |
| `--target=` | Target model |
| `--only=` | Comma separated collection of controller methods |
| `--except=` | Comma separated collection of ignored controller methods |

::: tip
Use `--silent|S` option to suppress unnecessary input prompt and to populate data automatically.
:::
