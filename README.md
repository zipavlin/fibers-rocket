<p align="center">
<img src='https://github.com/zipavlin/fibers-rocket/blob/master/docs/.vuepress/public/fibers-rocket-logo.png?raw=true' style='display:block;margin-left:auto;margin-right:auto;'>
</p>

# Fibers Rocket

Fibers Rocket is a developer oriented tool - a collection of artisan commands to scaffold common Laravel parts as fast and good as possible, so you can focus on more important stuff.

<p align="center">
    <img src='https://github.com/zipavlin/fibers-rocket/blob/master/docs/.vuepress/public/rocket-white.gif?raw=true'>
</p>

## Introduction

Fibers Rocket is a set of artisan commands that will help you scaffold and boilerplate common part of your Laravel app like models, controllers, migrations, layouts, routes and other - a bit like a smart `artisan make` on steroids.

Rocket's file templates use Laravel convention, are human readable, don't introduce any overhead and are build to be easy to modify and extend. Its commands are context aware and use information from other mvc parts to automatically either suggest or fill in required content as much as possible.

## Quick Start
```
# install composer package
composer require-dev fibers/rocket

# create model, controller and views
php artisan fibers:create <name>

# list all fibers commands
php artisan fibers --list
```

## Documentation
Please refer to [https://zipavlin.github.io/fibers-rocket/](https://zipavlin.github.io/fibers-rocket/) for more in dept installation & usage documentation and commands' API reference.
