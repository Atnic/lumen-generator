<?php

namespace Atnic\LumenGenerator\Console\Commands;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

/**
 * Controller Make Command
 */
class ControllerMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:controller';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new controller class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Controller';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        if ($this->option('parent')) {
            return __DIR__.'/stubs/controller.nested.stub';
        } elseif ($this->option('model')) {
            return __DIR__.'/stubs/controller.model.stub';
        } elseif ($this->option('resource')) {
            return __DIR__.'/stubs/controller.stub';
        }

        return __DIR__.'/stubs/controller.plain.stub';
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getTranslationStub()
    {
        return __DIR__.'/stubs/translation.stub';
    }

    /**
     * Generate Translation File
     * @return void
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function generateTranslation()
    {
        $name = $this->qualifyClass($this->getNameInput());
        $path = $this->getTranslationPath($name);

        $this->makeDirectory($path);
        if (!$this->files->exists($path)) {
            $this->files->put($path, $this->buildTranslation($name));
            $this->info('Controller translation also generated successfully.');
            $this->warn($path);
        }
    }

    /**
     * Get the translation path.
     *
     * @param  string  $name
     * @return string
     */
    protected function getTranslationPath($name)
    {
        $name = $this->getTranslationName($name);

        return base_path().'/resources/lang/en/'.$name.'.php';
    }

    /**
     * Get the translation name.
     *
     * @param  string  $name
     * @return string
     */
    protected function getTranslationName($name)
    {
        $name = $this->getRouteName($name);
        $name = array_last(explode('.', $name));

        return $name;
    }

    /**
     * Build the translation with the given name.
     *
     * @param  string $name
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function buildTranslation($name)
    {
        $name = $this->getRouteName($name);
        $name = array_last(explode('.', $name));
        $name = str_replace('_', ' ', $name);

        $replace = [
            'dummy_model_plural_variable' => $name,
            'dummy_model_variable' => str_singular($name),
        ];

        return str_replace(array_keys($replace), array_values($replace), $this->files->get($this->getTranslationStub()));
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Http\Controllers';
    }

    /**
     * Build the class with the given name.
     *
     * Remove the base controller import if we are already in base namespace.
     *
     * @param  string $name
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function buildClass($name)
    {
        $controllerNamespace = $this->getNamespace($name);

        $replace = [];

        if ($this->option('parent')) {
            $replace['DummyFullParentClass'] = $controllerNamespace.'Controller';
            $replace['DummyParentClass'] = class_basename($controllerNamespace.'Controller');
            $replace['parent_dummy_route'] = $this->getParentRouteName($name);
            $replace = $this->buildParentReplacements();
        }
        if ($this->option('model')) {
            $replace = $this->buildModelReplacements($replace);
        }
        $replace['dummy_route'] = $this->getRouteName($name);
        $replace["use {$controllerNamespace}\Controller;\n"] = '';

        return str_replace(array_keys($replace), array_values($replace), parent::buildClass($name));
    }

    /**
     * Build the replacements for a parent controller.
     *
     * @return array
     */
    protected function buildParentReplacements()
    {
        $parentModelClass = $this->parseModel($this->option('parent'));
        if (!$this->files->exists($this->getPath($parentModelClass))) {
            if ($this->confirm("A {$parentModelClass} model does not exist. Do you want to generate it?", true)) {
                $this->call('make:model', ['name' => str_replace($this->rootNamespace(), '', $parentModelClass), '-m' => true, '-f' => true]);
            }
        }

        $policyClass = str_replace_first($this->rootNamespace(), $this->rootNamespace().'Policies\\', $parentModelClass).'Policy';
        if (!$this->files->exists($this->getPath($policyClass))) {
            if ($this->confirm("A {$policyClass} policy does not exist. Do you want to generate it?", true)) {
                $this->call('make:policy', ['name' => $policyClass, '--model' => class_basename($parentModelClass)]);
            }
        }

        return [
            'ParentDummyFullModelClass' => $parentModelClass,
            'ParentDummyModelClass' => class_basename($parentModelClass),
            'ParentDummyModelVariable' => lcfirst(class_basename($parentModelClass)),
            'parent_dummy_model_variable' => snake_case(class_basename($parentModelClass)),
            'parent_dummy_model_plural_variable' => str_plural(snake_case(class_basename($parentModelClass))),
            'ParentDummyTitle' => ucwords(snake_case(class_basename($parentModelClass), ' ')),
        ];
    }

    /**
     * Build the model replacement values.
     *
     * @param  array  $replace
     * @return array
     */
    protected function buildModelReplacements(array $replace)
    {
        $modelClass = $this->parseModel($this->option('model'));
        if (!$this->files->exists($this->getPath($modelClass))) {
            if ($this->confirm("A {$modelClass} model does not exist. Do you want to generate it?", true)) {
                $this->call('make:model', ['name' => str_replace($this->rootNamespace(), '', $modelClass), '-m' => true, '-f' => true]);
            }
        }

        $policyClass = str_replace_first($this->rootNamespace(), $this->rootNamespace().'Policies\\', $modelClass).'Policy';
        if (!$this->files->exists($this->getPath($policyClass))) {
            if ($this->confirm("A {$policyClass} policy does not exist. Do you want to generate it?", true)) {
                $this->call('make:policy', ['name' => $policyClass, '--model' => class_basename($modelClass)]);
            }
        }

        return array_merge($replace, [
            'DummyFullModelClass' => $modelClass,
            'DummyModelClass' => class_basename($modelClass),
            'DummyModelVariable' => lcfirst(class_basename($modelClass)),
            'dummyModelVariable' => camel_case(class_basename($modelClass)),
            'dummy_model_variable' => snake_case(class_basename($modelClass)),
            'dummy_model_plural_variable' => str_plural(snake_case(class_basename($modelClass))),
            'DummyTitle' => ucwords(snake_case(class_basename($modelClass), ' ')),
        ]);
    }

    /**
     * Get the fully-qualified model class name.
     *
     * @param  string  $model
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function parseModel($model)
    {
        if (preg_match('([^A-Za-z0-9_/\\\\])', $model)) {
            throw new InvalidArgumentException('Model name contains invalid characters.');
        }

        $model = trim(str_replace('/', '\\', $model), '\\');

        if (! Str::startsWith($model, $rootNamespace = $this->laravel->getNamespace())) {
            $model = $rootNamespace.$model;
        }

        return $model;
    }

    /**
     * Execute the console command.
     *
     * @return bool|null
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function handle()
    {
        if (parent::handle() === false) return false;

        $this->createTest();
        $this->generateTranslation();
        $this->appendRouteFile();

        return null;
    }

    /**
     * Create a test for the controller.
     *
     * @return void
     */
    protected function createTest()
    {
        $name = $this->qualifyClass($this->getNameInput());
        $controllerClass = Str::replaceFirst($this->getDefaultNamespace(trim($this->rootNamespace(), '\\')).'\\', '', $name);

        $this->call('make:test', [
            'name' => $controllerClass.'Test',
            '--parent' => $this->option('parent') ? : null,
            '--model' => $this->option('model') ? : null,
            '--resource' => $this->option('resource') ? : false,
        ]);
    }

    /**
     * Append Route Files
     * @return void
     */
    protected function appendRouteFile()
    {
        $name = $this->qualifyClass($this->getNameInput());
        $nameWithoutNamespace = str_replace($this->getDefaultNamespace(trim($this->rootNamespace(), '\\')).'\\', '', $name);

        $file = base_path('routes/web.php');
        $routeName = $this->getRouteName($name);
        $routePath = $this->getRoutePath($name);

        $routeDefinition = "\$router->get('$routePath', [ 'as' => '$routeName', 'uses' => '$nameWithoutNamespace' ]);".PHP_EOL;

        if ($this->option('parent') || $this->option('model') || $this->option('resource')) {
            $routePathExploded = explode('/', $routePath);
            $as = str_replace('/', '.', $routePath);
            if (count($routePathExploded) > 1) {
                array_pop($routePathExploded);
                $prefix = implode('/', $routePathExploded).'/';
            } else {
                $prefix = '';
            }
            $asExploded = explode('.', $as);
            $models = array_pop($asExploded);
            $model = str_singular($models);
            if ($this->option('parent')) {
                $parents = array_pop($asExploded);
                $parent = str_singular($parents);
                $routeDefinition =
                    PHP_EOL.
                    "\$router->get('{$prefix}{$parents}/{{$parent}}/$models', [ 'as' => '$as.index', 'uses' => '$nameWithoutNamespace@index' ]);".PHP_EOL.
                    "\$router->post('{$prefix}{$parents}/{{$parent}}/$models', [ 'as' => '$as.store', 'uses' => '$nameWithoutNamespace@store' ]);".PHP_EOL.
                    "\$router->get('{$prefix}{$parents}/{{$parent}}/$models/{{$model}}', [ 'as' => '$as.show', 'uses' => '$nameWithoutNamespace@show' ]);".PHP_EOL.
                    "\$router->put('{$prefix}{$parents}/{{$parent}}/$models/{{$model}}', [ 'as' => '$as.update', 'uses' => '$nameWithoutNamespace@update' ]);".PHP_EOL.
                    "\$router->patch('{$prefix}{$parents}/{{$parent}}/$models/{{$model}}', [ 'as' => '$as.update', 'uses' => '$nameWithoutNamespace@update' ]);".PHP_EOL.
                    "\$router->delete('{$prefix}{$parents}/{{$parent}}/$models/{{$model}}', [ 'as' => '$as.destroy', 'uses' => '$nameWithoutNamespace@destroy' ]);".PHP_EOL;

            } else {
                $routeDefinition =
                    PHP_EOL.
                    "\$router->get('{$prefix}{$models}', [ 'as' => '$as.index', 'uses' => '$nameWithoutNamespace@index' ]);".PHP_EOL.
                    "\$router->post('{$prefix}{$models}', [ 'as' => '$as.store', 'uses' => '$nameWithoutNamespace@store' ]);".PHP_EOL.
                    "\$router->get('{$prefix}{$models}/{{$model}}', [ 'as' => '$as.show', 'uses' => '$nameWithoutNamespace@show' ]);".PHP_EOL.
                    "\$router->put('{$prefix}{$models}/{{$model}}', [ 'as' => '$as.update', 'uses' => '$nameWithoutNamespace@update' ]);".PHP_EOL.
                    "\$router->patch('{$prefix}{$models}/{{$model}}', [ 'as' => '$as.update', 'uses' => '$nameWithoutNamespace@update' ]);".PHP_EOL.
                    "\$router->delete('{$prefix}{$models}/{{$model}}', [ 'as' => '$as.destroy', 'uses' => '$nameWithoutNamespace@destroy' ]);".PHP_EOL;
            }
        }

        file_put_contents($file, $routeDefinition, FILE_APPEND);

        $this->warn($file.' modified.');
    }

    /**
     * Get the route name.
     *
     * @param  string  $name
     * @return string
     */
    protected function getRouteName($name)
    {
        $name = Str::replaceFirst($this->getDefaultNamespace(trim($this->rootNamespace(), '\\')).'\\', '', $name);
        $name = Str::replaceLast('Controller', '', $name);
        $names = explode('\\', $name);
        foreach ($names as $key => $value) {
            $names[$key] = snake_case($value);
        }
        if ($this->option('parent') && count($names) >= 2) {
            $model = str_plural(array_pop($names));
            $parent = str_plural(array_pop($names));
            array_push($names, $parent, $model);
        } elseif (($this->option('model') || $this->option('resource')) && count($names) >= 1) {
            $model = str_plural(array_pop($names));
            array_push($names, $model);
        }
        $name = implode('.', $names);

        return str_replace('\\', '.', $name);
    }

    /**
     * Get the route name.
     *
     * @param  string  $name
     * @return string
     */
    protected function getParentRouteName($name)
    {
        $name = Str::replaceFirst($this->getDefaultNamespace(trim($this->rootNamespace(), '\\')).'\\', '', $name);
        $name = Str::replaceLast('Controller', '', $name);
        $names = explode('\\', $name);
        foreach ($names as $key => $value) {
            $names[$key] = snake_case($value);
        }
        if (count($names) >= 2) {
            array_pop($names);
            $parent = str_plural(array_pop($names));
            array_push($names, $parent);
        }
        $name = implode('.', $names);

        return str_replace('\\', '.', $name);
    }

    /**
     * Get the route path.
     *
     * @param  string  $name
     * @return string
     */
    protected function getRoutePath($name)
    {
        $routeName = $this->getRouteName($name);
        $routeNameExploded = explode('.', $routeName);
        $routePath = str_replace('.', '/', $this->getRouteName($routeName));
        if ($this->option('parent') && count($routeNameExploded) >= 2) {
            $routePath = str_replace_last('/', '.', $routePath);
        }
        return $routePath;
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['model', 'm', InputOption::VALUE_OPTIONAL, 'Generate a resource controller for the given model.'],
            ['resource', 'r', InputOption::VALUE_NONE, 'Generate a resource controller class.'],
            ['invokable', 'i', InputOption::VALUE_NONE, 'Generate a single method, invokable controller class.'],
            ['parent', 'p', InputOption::VALUE_OPTIONAL, 'Generate a nested resource controller class.'],
        ];
    }
}
