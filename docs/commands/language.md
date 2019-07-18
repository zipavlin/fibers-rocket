# Make Language

```bash
php artisan fibers:make:language <Title>
```

Language command will setup a new [language](https://laravel.com/docs/localization), creating a new language folder, automatically downloading standard language files and copying custom files.

::: tip
Standard Laravel translation files are downloaded from [caouecs/Laravel-lang github repo](https://github.com/caouecs/Laravel-lang).
:::

## Parameters
| Parameter | Description |
| --- | --- |
| `title` | Language title or short code (2 char) |
