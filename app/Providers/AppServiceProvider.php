<?php

namespace Atnic\LumenGenerator\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../../config/generator.php' => base_path('config/generator.php'),
            __DIR__.'/../../config/filters.php' => base_path('config/filters.php'),
        ], 'config');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/app.php', 'app');
        $this->mergeConfigFrom(__DIR__.'/../../config/auth.php', 'auth');
        $this->mergeConfigFrom(__DIR__.'/../../config/generator.php', 'generator');
        $this->mergeConfigFrom(__DIR__.'/../../config/filters.php', 'filters');

        Gate::before(function ($user, $ability, $arguments) {
            foreach ($arguments as $argument) {
                $policy = Gate::getPolicyFor($argument);
                if (is_null($policy) && is_string($class = (is_object($argument) ? get_class($argument) : $argument))) {
                    $classDirname = str_replace('/', '\\', dirname(str_replace('\\', '/', $class)));
                    $guessedPolicy = $classDirname.'\\Policies\\'.class_basename($class).'Policy';
                    if (class_exists($guessedPolicy))
                        Gate::policy($class, $guessedPolicy);
                }
            }
        });
    }

    /**
     * Merge the given configuration with the existing configuration.
     *
     * @param  string  $path
     * @param  string  $key
     * @return void
     */
    protected function mergeConfigFrom($path, $key)
    {
        $config = $this->app['config']->get($key, []);

        $this->app['config']->set($key, array_merge($config, require $path));
    }
}
