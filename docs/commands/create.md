# Create MVC

```bash
php artisan fibers:create <Title> [Options]
```

Fibers Rocket - Create will create a (full) mvc package of files. This command runs multiple Rocket commands in sequence and is therefore the fastest way to scaffold a new model with all connected Laravel parts. Keep in mind that all parts can also be scaffolded separately using relevant commands.

* **model**: create model file as well
* **controller**: create controller file as well
* **layout**: create layout view files as well
* **guard**: create and use guarded request
* **route**: add controller to routes
* ~~**admin**: Scaffold admin interface~~ (not yet implemented)

::: warning
When creating a model it's migration will be automatically migrated. **Take extra care when setting attributes using [**_attribute input_**](/fibers-rocket/attributes).**
:::

## Parameters
| Parameter | Description |
| --- | --- |
| `title` | Title is used also for other naming derivatives. It is normalized automatically. |

## Options
| Command | Description |
| --- | --- |
| `--mvc` | **Will create a minimal model + view + controller scaffolding** |
| `--all` | **Will create a full model + view + controller + guard + route + (admin) scaffolding** |
| `--model|M` | Will create model file as well |
| `--controller|C` | Will create controller file as well |
| `--layout|L` | Will create layout view files as well |
| `--guard|G` | Will create and use guarded request |
| `--route|R` | Will add controller to routes |
| `--admin|A` | ~~Will scaffold admin interface~~ (not yet implemented) |
| `--paginated` | Will create a paginated index |
| `--bootstrap` | Will use bootstrap in layout creation |
| `--only=` | Comma separated collection of controller methods |
| `--except=` | Comma separated collection of ignored controller methods |

::: tip
Use `--silent|S` option to suppress unnecessary input prompt and to populate data automatically.
:::
