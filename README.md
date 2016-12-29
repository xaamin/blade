Blade
=====

Standalone version of [Blade templating engine](http://laravel.com/docs/5.3/blade) for use outside of Laravel.


Installation
------------

```bash
composer require xaamin/blade
```

Usage
-----

Create a Blade instance by passing it the folder(s) where your view files are located, and a cache folder. Render a template by calling the `make` method.

```php
    use Xaamin\Blade\View;

    $blade = new Blade('views', 'cache');

    echo $blade->make('home', ['name' => 'John Doe']);
```

Now you can easily create a directive by calling the ``compiler()`` function

```php
    $blade->compiler()->directive('money', function ($expression) {
        return "<?= '$ ' . number_format($expression, 2, '.', ','); ?>";
    });
```

In your Blade Template

```php
    <?php $decimal = '520.50' ?>
    @datetime($decimal)
```

The Blade instances passes all methods to the internal view factory. So methods such as `exists`, `file`, `share`, `composer` and `creator` are available as well.

More information about the Blade templating engine can be found on http://laravel.com/docs/5.3/blade.