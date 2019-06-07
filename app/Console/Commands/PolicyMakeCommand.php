<?php

namespace Atnic\LumenGenerator\Console\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

/**
 * Policy Make Command
 */
class PolicyMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:policy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new policy class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Policy';

    /**
     * Execute the console command.
     *
     * @return bool|null
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function handle()
    {
        if (!$this->alreadyExists('ModelPolicy') && $this->hasOption('model')) {
            $name = $this->qualifyClass('ModelPolicy');

            $path = $this->getPath($name);

            // Next, we will generate the path to the location where this class' file should get
            // written. Then, we will build the class and make the proper replacements on the
            // stub files so that it gets the correctly formatted namespace and class name.
            $this->makeDirectory($path);

            $this->files->put($path, $this->buildClass($name));
        }

        return parent::handle();
    }

    /**
     * Build the class with the given name.
     *
     * @param  string $name
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function buildClass($name)
    {
        if ($name == $this->qualifyClass('ModelPolicy')) {
            $stub = $this->files->get($this->getStub($name));

            $parent = $this->replaceNamespace($stub, $name)->replaceClass($stub, $name);

            $stub = $this->replaceUserNamespace($parent);

            $model = $this->option('model');

            return $model ? $this->replaceModel($stub, $model) : $stub;
        }

        $stub = $this->replaceUserNamespace(
            parent::buildClass($name)
        );

        $model = $this->option('model');

        return $model ? $this->replaceModel($stub, $model) : $stub;
    }

    /**
     * Replace the User model namespace.
     *
     * @param  string  $stub
     * @return string
     */
    protected function replaceUserNamespace($stub)
    {
        if (! config('auth.providers.users.model')) {
            return $stub;
        }

        return str_replace(
            $this->rootNamespace().'User',
            config('auth.providers.users.model'),
            $stub
        );
    }

    /**
     * Replace the model for the given stub.
     *
     * @param  string  $stub
     * @param  string  $model
     * @return string
     */
    protected function replaceModel($stub, $model)
    {
        $model = str_replace('/', '\\', $model);

        $namespaceModel = $this->laravel->getNamespace().$model;

        if (Str::startsWith($model, '\\')) {
            $stub = str_replace('NamespacedDummyModel', trim($model, '\\'), $stub);
        } else {
            $stub = str_replace('NamespacedDummyModel', $namespaceModel, $stub);
        }

        $stub = str_replace(
            "use {$namespaceModel};\nuse {$namespaceModel};", "use {$namespaceModel};", $stub
        );

        $model = class_basename(trim($model, '\\'));

        $dummyUser = class_basename(config('auth.providers.users.model'));

        $dummyModel = Str::camel($model) === 'user' ? 'model' : $model;

        $stub = str_replace('DocDummyModel', Str::snake($dummyModel, ' '), $stub);

        $stub = str_replace('DummyModel', $model, $stub);

        $stub = str_replace('dummyModel', Str::camel($dummyModel), $stub);

        $stub = str_replace('DummyUser', $dummyUser, $stub);

        return str_replace('DocDummyPluralModel', Str::snake(Str::plural($dummyModel), ' '), $stub);
    }

    /**
     * Get the stub file for the generator.
     *
     * @param null $name
     * @return string
     */
    protected function getStub($name = null)
    {
        if ($name == $this->qualifyClass('ModelPolicy')) return __DIR__.'/stubs/policy.model.stub';

        return $this->option('model')
            ? __DIR__.'/stubs/policy.stub'
            : __DIR__.'/stubs/policy.plain.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Policies';
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['model', 'm', InputOption::VALUE_OPTIONAL, 'The model that the policy applies to'],
        ];
    }
}
