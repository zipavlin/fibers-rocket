---
home: true
heroImage: /logo-rocket-big.svg
actionText: Check commands →
actionLink: /docs/
features:
- title: Build for developers
  details: Fibers Rocket is build to make your development easier by automating usual stuff so you can focus on your magic.
- title: Smart scaffolding
  details: Commands try to automatically figure out what you need and help you build faster with input suggestions or smart auto values when in silent mode.
- title: Out of the way
  details: Package follows a Laravel conventions when creating files. There is nothing new to learn and no overhead when customizing.
footer: MIT Licensed | Copyright © 2019-present Žiga Pavlin
---

<img src='/rocket.gif' style='display:block;margin-left:auto;margin-right:auto;'>

## Short Introduction
**This package is in public alpha.**

Fibers Rocket is a developer oriented tool - a collection of artisan commands to scaffold common Laravel parts as fast and good as possible, so you can focus on more important stuff.

## Quick Start
```
# install composer package
composer require-dev fibers/rocket

# create model, controller and views
php artisan fibers:create <name>

# list all fibers commands
php artisan fibers --list
```

## Commands
<input type="checkbox" checked disabled> [Ignite](/commands/ignite) - bootstrap your app with common steps  
<input type="checkbox" checked disabled> [Create](/commands/create) - batch create mvc  
<input type="checkbox" checked disabled> [Model](/commands/model) - create a model  
<input type="checkbox" checked disabled> [Controller](/commands/controller) - create a controller  
<input type="checkbox" checked disabled> [Layout](/commands/layout) - create a layout views  
<input type="checkbox" checked disabled> [Route](/commands/route) - create a route  
<input type="checkbox" checked disabled> [Migration](/commands/migration) - create a migration  
<input type="checkbox" checked disabled> [Guard](/commands/guard) - create a guard  
<input type="checkbox" checked disabled> [Language](/commands/language) - add a new language  
<input type="checkbox" checked disabled> [Pivot](/commands/pivot) - create a pivot table  
<input type="checkbox" disabled> Policy - create a policy file  
<input type="checkbox" disabled> User - set up a new user model with auth  
<input type="checkbox" disabled> Role - add role to user model  


## Read More
- [Introduction](/guide#introduction)
- [Requirements](/guide#requirements)
- [Installation](/guide#installation)
- [Usage](/guide#usage)
- [Contributing](/guide#contributing)
