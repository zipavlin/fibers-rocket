# Make Pivot

```bash
php artisan fibers:make:pivot <Model> <Model>
```

Pivot command will create a pivot table, connection two model. It runs [migration command](/fibers-rocket/commands/migration) internally.

## Parameters
| Parameter | Description |
| --- | --- |
| `model` | Model title(s) used in table creation |

## Options
| Command | Description |
| --- | --- |
| `--column=*` | Additional pivot table columns |
