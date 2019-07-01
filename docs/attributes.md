# Attribute Input

When using `php artisan fibers:model` or `php artisan fibers:migration` command, you will be prompted to enter model/migration's attributes using a _nano editor_ or a simple _multiline input_.  
Both input variants use simplified (but modified) migration syntax with added relationship type and few special types (id, uuid, timestamps etc.):

```bash
# regular type
title -> type (arguments), options

# special type
type 
```

## Types
Types correspond to [migration columns](https://laravel.com/docs/migrations#columns) and in extension to database column types.  
The only exception in types (for now) is relationship type - see details below.

<details class='narrow'>
<summary><strong>List of all types</strong></summary>

| Type | Alternative | Arguments | Magic | Casts |
| ---- | ------------| --------- | ----- | ----- |
| bigIncrements | biginc |  |  |  |
| bigInteger | bigint, bi |  |  | integer |
| binary |  |  |  |  |
| boolean | bool, b |  |  | boolean |
| char |  | length:int |  | string |
| date |  |  |  | date |
| dateTime |  |  |  | date |
| dateTimeTz |  |  |  | date |
| decimal |  | precision:int, scale:int |  | decimal |
| double |  | precision:int, scale:int |  | double |
| enum | select | options:array |  | string |
| float | f | precision:int, scale:int |  | float |
| geometry | geo |  |  | collection |
| geometryCollection |  |  |  | collection |
| id |  |  | ✓ |  |
| increments | inc |  |  |  |
| integer | int, i |  |  | integer |
| ipAddress | ip |  |  | string |
| json | array, doc, j |  |  | collection |
| jsonb |  |  |  | collection |
| lineString |  |  |  | string |
| longText |  |  |  | string |
| macAddress |  |  |  | string |
| mediumIncrements |  |  |  |  |
| mediumInteger |  |  |  | integer |
| mediumText |  |  |  | string |
| morphs |  |  |  |  |
| multiLineString |  |  |  | string |
| multiPoint |  |  |  | collection |
| multiPolygon |  |  |  | collection |
| nullableMorphs |  |  |  |  |
| nullableTimestamps |  |  | ✓ | timestamp |
| point |  |  |  | collection |
| polygon |  |  |  | collection |
| relationship | relation, r | has-one, has-many, belongs-one, belongs-many |  |  |
| rememberToken | token, remember |  | ✓ | string |
| set | multiselect | options:array |  | string |
| smallIncrements |  |  |  |  |
| smallInteger |  |  |  | integer |
| softDeletes |  |  | ✓ |  |
| softDeletesTz |  |  | ✓ |  |
| string | str, s | length:int |  | string |
| text | txt, t |  |  | string |
| time |  |  |  | date |
| timeTz |  |  |  | date |
| timestamp |  |  |  | timestamp |
| timestampTz |  |  |  | timestamp |
| timestamps |  |  | ✓ | timestamps |
| timestampsTz |  |  | ✓ | timestamps |
| tinyIncrements |  |  |  |  |
| tinyInteger |  |  |  | boolean |
| unsignedBigInteger |  |  |  | integer |
| unsignedDecimal |  | precision:int, scale:int |  | decimal |
| unsignedInteger |  |  |  | integer |
| unsignedMediumInteger |  |  |  | integer |
| unsignedSmallInteger |  |  |  | integer |
| unsignedTinyInteger |  |  |  | boolean |
| uuid |  |  | ✓ | string |
| year |  |  |  | date |

</details>

::: tip
Types are automatically normalized: dashes (-) are removed and string is lowercased.  
Arguments are automatically cast to appropriate type so you shouldn't use quotes.   
:::

## Options

Options include model attribute settings, model relationship modifiers, migration column modifiers and migration index modifiers.

<details class='narrow'>
<summary><strong>List of all options</strong></summary>

| Option | Arguments | Description | Model | Migration |
| ------ | ----------| ----------- | ----- | --------- |
| thought | string | relate model thought other model's class | ✓ | ✓ |
| pivot | string, string | list of pivot columns in many-many relationship | ✓ | ✓ |
| model | model's class | used as target model when creating relationships; default field title | ✓ | ✓ |
| table | string | used as table when setting relationship | ✓ | ✓ |
| local | string | used as local key when setting relationship | ✓ | ✓ |
| foreign | string | used as foreign when setting relationship | ✓ | ✓ |
| ------ | ----------| ----------- | ----- | --------- |
| fillable |  | add to fillable array | ✓ |  |
| hidden |  | add to hidden array | ✓ |  |
| eager |  | eager load relationship | ✓ |  |
| format | string | sets date to format | ✓ |  |
| morph | string | sets morph target to model's class | ✓ |  |
| as | string | ??? | ✓ |  |
| timestamps |  | attach timestamps to many-many pivot table | ✓ |
| ------ | ----------| ----------- | ----- | --------- |
| after | string | place the column "after" another column |  | ✓ |
| autoIncrement |  | automatically increment column |  | ✓ |
| useCurrent |  | desc |  | ✓ |
| charset | string | specify a character set for the column |  | ✓ |
| collation | string | specify a collation for the column |  | ✓ |
| comment | string | ads comment to column |  | ✓ |
| default | mixed | sets default value |  | ✓ |
| first |  | place the column "first" in the table |  | ✓ |
| nullable |  | sets column as nullable |  | ✓ |
| unsigned |  | set INTEGER columns as UNSIGNED |  | ✓ |
| useCurrent |  | set TIMESTAMP columns to use CURRENT_TIMESTAMP as default value |  | ✓ |
| unique |  | sets unique requirement |  | ✓ |
| primary |  | sets column as primary key |  | ✓ |
| index |  | index this column |  | ✓ |
| update | string | sets onUpdate for foreign column |  | ✓ |
| delete | string | sets onDelete for foreign column |  | ✓ |

::: tip
If _model_, _table_, _local_ or _foreign_ options are not set, they will be auto-populated from field's title.
:::

</details>

## Magic Types
There are some 'magic' types that do not have neither title nor any arguments or options.

* **id**: create bigIncrements column with title 'id'
* **uuid**: create char column with title 'uuid' and add appropriate trait to model
* **timestamps|timestampsTz|nullableTimestampTz**: create 'created_at' and 'updated_at' columns
* **rememberToken**: create a 'remember_token' varchar column
* **softDeletes|softDeletesTz**: create 'deleted_at' timestamp column and add appropriate trait to model

::: tip
Some magic types add additional traits to models. You can set them in `config/fibers.traits`
:::

## Example
```text
id
title -> string (255)
published -> boolean, hidden
published_at -> date, format:d-m-Y
place -> relationship (belongs-to-many), eager
rating -> relationship (belongs-to-many), pivot:title|amount, eager
user -> relationship (has-one)
review -> relationship (has-one), trough:Place
tags -> relationship (has-many), morph:taggable
timestamps
soft-deletes
```
