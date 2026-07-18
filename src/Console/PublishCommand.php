<?php

declare(strict_types=1);

namespace Chatify\Console;

use Illuminate\Console\Command;

class PublishCommand extends Command
{
    protected $signature = 'chatify:publish {--force : Overwrite any existing files}';

    protected $description = 'Publish Chatify views, assets, and frontend source';

    public function handle(): int
    {
        if ($this->option('force')) {
            $this->call('vendor:publish', [
                '--tag' => 'chatify-config',
                '--force' => true,
            ]);

            $this->call('vendor:publish', [
                '--tag' => 'chatify-migrations',
                '--force' => true,
            ]);
        }

        $this->call('vendor:publish', [
            '--tag' => 'chatify-views',
            '--force' => true,
        ]);

        $this->call('vendor:publish', [
            '--tag' => 'chatify-assets',
            '--force' => true,
        ]);

        $this->call('vendor:publish', [
            '--tag' => 'chatify-patterns',
            '--force' => true,
        ]);

        $this->call('vendor:publish', [
            '--tag' => 'chatify-frontend',
            '--force' => true,
        ]);

        $this->info('Published. Rebuild assets: php artisan chatify:build');

        return self::SUCCESS;
    }
}
