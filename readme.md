# Lumen Generator

## Requirement
- php: >=7.1.3,
- [phpunit/phpunit](https://github.com/sebastianbergmann/phpunit): ^7.0

## Installation
```bash
composer require atnic/lumen-generator
```
Then do this initial steps:
- [x] Setup your `.env` file.
- [x] In `app/User.php`, add `api_token` in `$hidden` property.
- [x] In `bootstrap/app.php`, uncomment and add some lines
  ```php
  ...
  $app->withFacades();
  
  $app->withEloquent();
  ...
  $app->routeMiddleware([
      'auth' => App\Http\Middleware\Authenticate::class,
  ]);
  ...
  $app->register(App\Providers\AppServiceProvider::class);
  $app->register(App\Providers\AuthServiceProvider::class);
  $app->register(App\Providers\EventServiceProvider::class);
  
  $app->register(Atnic\LumenGenerator\Providers\AppServiceProvider::class);
  $app->register(Atnic\LumenGenerator\Providers\ConsoleServiceProvider::class);
  
  $app->register(Laravel\Passport\PassportServiceProvider::class);
  ...
  ```
- [x] Update `database/factories/ModelFactory.php`, add `password` and `api_token`
  ```php
  $factory->define(App\User::class, function (Faker\Generator $faker) {
      return [
          'name' => $faker->name,
          'email' => $faker->email,
          'password' => app('hash')->make('password'),
          'api_token' => str_random()
      ];
  });
  ```
- [x] Update `.gitignore`
  ```
  /database/*.sqlite
  /storage/*.key
  ```
- [x] In `phpunit.xml`, remove attribute `syntaxCheck="false"` if exists (ex: Lumen 5.5), because its not compatible with new phpunit package
- [x] Then run 
  ```bash
  php artisan app:install
  ```

## Make Module (CRUD)
This package is overriding some laravel artisan command.

This is example to make Foo module in this project
```bash
php artisan make:controller --model=Foo FooController
```
Then do this steps:
- [x] Check new migration in `database/migrations/`, add column needed.
- [x] Check new factory in `database/factories/`, add atrribute needed.
- [x] Check new model in `app/`, add changes needed.
- [x] Check new filter in `app/Filters/`, do all `TODO:` and remove the comment if done.
- [x] Check lang en `resources/lang/en` and copy from en to lang id `resources/lang/id`, add language as needed.
- [x] Check new controller in `app/Http/Controllers/`, complete returned array in method `relations()` `visibles()` `fields()` `rules()`, do all `TODO:`, and remove comment if done.
- [x] Check new policy in `app/Policies/`, do all `TODO:` and remove the comment if done.
- [x] No need to append new Policy to `$policies` attribute in `app/Providers/AuthServiceProvider.php`. This package handle policy auto discovery, even for Laravel < 5.8.
- [x] Check new tests in `tests/Feature/`, do all `TODO:` and remove the comment if done.

## Other Useful command

```bash
# Creating Nested Controller
php artisan make:controller --parent=Foo --model=Bar Foo/BarController

# Create Single Action Controller
php artisan make:controller DashboardController
```

All new/overrided command can be viewed in `vendor/atnic/lumen-generator/app/Console/Commands`.
