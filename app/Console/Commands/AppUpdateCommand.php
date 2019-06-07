<?php

namespace Atnic\LumenGenerator\Console\Commands;

use Illuminate\Console\Command;

class AppUpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update '.
        '{--production : Run config:cache and route:cache}'.
        '{--seed= : Run seeder}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'App Update';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->call('cache:clear');

        $this->call('migrate', [ '--force' => true ]);
        if ($this->option('seed')) {
            $this->call('db:seed', ['--class' => $this->option('seed') ?? null, '--force' => true]);
        }
    }
}
