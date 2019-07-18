# Make Migration

```bash
php artisan fibers:make:migration <Title> [Options]
```

This command will create a new [Migration](https://laravel.com/docs/migrations) and optionally continue to creating a model. It tries to speed up migration creation by automatically filling usual boilerplate parts.

Command will automatically create pivot table migration files when needed.

## Attribute Input
Migration creation is simple, but does require user's help to set appropriate attributes. This multiline input uses simplified (but modified) migration syntax. **Read more about attribute input [**here**](/fibers-rocket/attributes).**  

## Parameters
| Parameter | Description |
| --- | --- |
| `title` | Title is used also for other naming derivatives. It is normalized automatically. |

## Options
| Option | Description |
| --- | --- |
| `--model|M` | Will create model file as well |
| `--last|L` | Will set migration's filename so it is migrated at the end |
| `--table=` | Will set table name. If not set title parameter will be used |

::: tip
Use `--silent|S` option to suppress unnecessary input prompt and to populate data automatically.
:::
