# Footprint

[![Build Status](https://img.shields.io/travis/UseMuffin/Footprint/master.svg?style=flat-square)](https://travis-ci.org/UseMuffin/Footprint)
[![Coverage](https://img.shields.io/codecov/c/github/UseMuffin/Footprint.svg?style=flat-square)](https://codecov.io/github/UseMuffin/Footprint)
[![Total Downloads](https://img.shields.io/packagist/dt/muffin/footprint.svg?style=flat-square)](https://packagist.org/packages/muffin/footprint)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE)

This plugin allows you to pass the currently logged in user to the model layer of a CakePHP 3
application.

It comes bundled with the `FootprintBehavior` to allow you control over columns such as `user_id`,
`created_by`, `company_id` just like the core's `TimestampBehavior`.

## Install

Using [Composer][composer]:

```
composer require muffin/footprint
```

You then need to load the plugin. In `boostrap.php`, something like:

```php
\Cake\Core\Plugin::load('Muffin/Footprint');
```

## Usage

### Trait

First, you will need to include the `Muffin\Footprint\Auth\FootprintAwareTrait` to your `AppController`:

```php
use Muffin\Footprint\Auth\FootprintAwareTrait;

class AppController extends Controller
{
    use FootprintAwareTrait;
}
```

This will attach the `Muffin\Footprint\Event\FootprintListener` to models
which will inject the currently logged in user's instance on `Model.beforeSave`
and `Model.beforeFind` in the `_footprint` key of `$options`.

The user record provided by `AuthComponent` is converted to `User` entity before
passing to models. If your `AuthComponent` is configured to use a non-default
users table you must set the `$_userModel` property of the trait to same table:

```php
public function initialize()
{
    parent::initialize();

    // Specify the user model if required. Defaults to "Users".
    $this->_userModel = 'YourPlugin.Members';

    $this->loadComponent('Auth', [
        'authenticate' => [
            'Form' => [
                'userModel' => $this->_userModel, // Use same model for AuthComponent
            ],
        ],
    ]);
}
```

### Behavior

To use the included behavior to automatically update the `created_by` and `modified_by` fields of a record for example,
add the following to your table's `initialize()` method:

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

This will insert the currently logged in user's primary key in `user_id` and `modified_by` fields when creating
a record, on the `modified_by` field again when updating the record and it will use the associated user record's
company `id` in the `company_id` field when creating a record.

## Warning

If you have the `FootprintBehavior` attached to a model do not load the model inside
`Controller::initialize()` method directly or indirectly. If you do so the
footprint (user entity) won't be set for the model and the behavior won't work
as expected. You can load your model in `Controller::beforeFilter()` if needed.

This is because the `FootprintListener` which sets the user entity to the models
is attached after `Controller::initialize()` is run.

## Patches & Features

* Fork
* Mod, fix
* Test - this is important, so it's not unintentionally broken
* Commit - do not mess with license, todo, version, etc. (if you do change any, bump them into commits of
their own that I can ignore when I pull)
* Pull request - bonus point for topic branches

## Bugs & Feedback

http://github.com/usemuffin/footprint/issues

## Credits

This was originally inspired by [Ceeram/Blame].

## License

Copyright (c) 2015, [Use Muffin][muffin] and licensed under [The MIT License][mit].

[cakephp]:http://cakephp.org
[composer]:http://getcomposer.org
[mit]:http://www.opensource.org/licenses/mit-license.php
[muffin]:http://usemuffin.com
[Ceeram/Blame]:http://github.com/ceeram/blame
