<?php

namespace Atnic\LumenGenerator\Console\Commands;

use Illuminate\Console\Command;

class AppInstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:install '.
        '{--migrate-refresh : Migrate refresh instead of migrate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'App Install';

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
     * @return mixed
     */
    public function handle()
    {
        if (!file_exists(base_path('.env'))) {
            $this->error('File .env not found');
            return false;
        }
        if (empty(env('APP_KEY'))) {
            $this->error('APP_KEY is unset');
            return false;
        }
        if (!file_exists(database_path('migrations/2014_10_12_000000_create_users_table.php'))) {
            file_put_contents(
                database_path('migrations/2014_10_12_000000_create_users_table.php'),
                file_get_contents(__DIR__.'/stubs/migration/2014_10_12_000000_create_users_table.stub')
            );
            $this->line("<info>Created Migration:</info> 2014_10_12_000000_create_users_table.php");
        }
        $this->info('Migrate and Seeding...');
        if ($this->option('migrate-refresh')) {
            $this->call('migrate:refresh', [ '--seed' => true ]);
        } else {
            $this->call('migrate', [ '--seed' => true ]);
        }
        if (!file_exists(storage_path('oauth-private.key')) || !file_exists(storage_path('oauth-public.key'))) {
            $this->info('Passport Key Generate...');
            $this->call('passport:install');
        }

        return true;
    }
}
