# Guide

## Introduction
**This package is in public alpha.**

Fibers Rocket is a set of artisan commands that will help you scaffold and boilerplate common part of your Laravel app like models, controllers, migrations, layouts, routes and other - a bit like a smart `artisan make` on steroids.

Rocket's file templates use Laravel convention, are human readable, don't introduce any overhead and are build to be easy to modify and extend. Its commands are context aware and use information from other mvc parts to automatically either suggest or fill in required content as much as possible.

**TL;DR: Fibers Rocket will help you kick start your app, then move aside for you to do your magic.**

## Requirements
1. **Laravel**: 5.8.*
2. **Fibers Helper**: 0.1.*

## Installation
Install using composer require by running:
```bash
composer require-dev fibers/rocket
```
Or add `"fibers/rocket": "^0.1",` to `composer.json` and run `composer install`.

## Usage
Run with `php artisan fibers:<command>` and follow questions in console.

## Contributing
PRs are welcome. This package was build to streamline Laravel boilerplating and could use feedback about different use cases and requirements as well as additional quality check and/or missing commands.

## Todo
* Feature test  
* Additional commands  
* Fibers Captain integration  

## What is Fibers?
Fibers Rocket is a part of Fibers - a rapid development framework.
