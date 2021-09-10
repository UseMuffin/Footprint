# Footprint

[![Build Status](https://img.shields.io/travis/UseMuffin/Footprint/master.svg?style=flat-square)](https://github.com/UseMuffin/Footprint/actions?query=workflow%3ACI+branch%3Amaster)
[![Coverage](https://img.shields.io/github/workflow/status/UseMuffin/Footprint/CI/master?style=flat-square)](https://codecov.io/github/UseMuffin/Footprint)
[![Total Downloads](https://img.shields.io/packagist/dt/muffin/footprint.svg?style=flat-square)](https://packagist.org/packages/muffin/footprint)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE)

This plugin allows you to pass the currently logged in user info to the model layer
of a CakePHP application.

It comes bundled with the `FootprintBehavior` to allow you control over columns
such as `user_id`, `created_by`, `company_id` similar to the core's `TimestampBehavior`.

## Install

Using [Composer][composer]:

```
composer require muffin/footprint
```

You then need to load the plugin by running console command:

```bash
bin/cake plugin load Muffin/Footprint
```

The Footprint plugin **must** be loaded **before** the [Authentication](https://github.com/cakephp/authentication) plugin,
so you should move the `$this->addPlugin('Muffin/Footprint');` statement above
the `$this->addPlugin('Authentication');` in your `Application::bootstrap()`
manually if required.

## Usage

### Middleware

Add the `FootprintMiddleware` to the middleware queue in your `Application::middleware()`
method `$middleware->add('Muffin/Footprint');`.
It must be added **after** `AuthenticationMiddleware` to ensure that it can read
the identify info after authentication is done.

### Behavior

To use the included behavior to automatically update the `created_by` and `modified_by`
fields of a record for example, add the following to your table's `initialize()` method:

```php
$this->addBehavior('Muffin/Footprint.Footprint');
```

You can customize that like so:

```php
$this->addBehavior('Muffin/Footprint.Footprint', [
    'events' => [
        'Model.beforeSave' => [
            'user_id' => 'new',
            'company_id' => 'new',
            'modified_by' => 'always'
        ]
    ],
    'propertiesMap' => [
        'company_id' => '_footprint.company.id',
    ],
]);
```

This will insert the currently logged in user's primary key in `user_id` and `modified_by`
fields when creating a record, on the `modified_by` field again when updating
the record and it will use the associated user record's company `id` in the
`company_id` field when creating a record.

## Patches & Features

* Fork
* Mod, fix
* Test - this is important, so it's not unintentionally broken
* Commit - do not mess with license, todo, version, etc. (if you do change any,
  bump them into commits of their own that I can ignore when I pull)
* Pull request - bonus point for topic branches

## Bugs & Feedback

http://github.com/usemuffin/footprint/issues

## License

Copyright (c) 2015-Present, [Use Muffin][muffin] and licensed under [The MIT License][mit].

[cakephp]:http://cakephp.org
[composer]:http://getcomposer.org
[mit]:http://www.opensource.org/licenses/mit-license.php
[muffin]:http://usemuffin.com
