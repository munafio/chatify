<?php

declare(strict_types=1);

namespace Chatify\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallCommand extends Command
{
    protected $signature = 'chatify:install {--with-ui : Publish views and compiled frontend assets}';

    protected $description = 'Install Chatify v2 (migrations, config, broadcast channels)';

    public function handle(): int
    {
        $this->info('Installing Chatify v2...');

        $tempDir = storage_path('framework/temp');
        if (! File::isDirectory($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        $this->call('vendor:publish', [
            '--tag' => 'chatify-config',
            '--force' => true,
        ]);

        $this->call('vendor:publish', [
            '--tag' => 'chatify-migrations',
            '--force' => true,
        ]);

        $this->call('vendor:publish', [
            '--tag' => 'chatify-channels',
            '--force' => true,
        ]);

        if ($this->option('with-ui')) {
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
        }

        $this->line('Add Chatify\\Traits\\InteractsWithChatify to your User model.');
        $this->line('Set CHATIFY_API_MIDDLEWARE=web,auth when using the bundled web UI.');
        $this->line('Run: php artisan migrate');
        $this->line('Run: php artisan storage:link');
        $this->line('Ensure php.ini upload_tmp_dir points to storage/framework/temp (writable).');

        if ($this->option('with-ui')) {
            $this->line('Web UI: visit /'.config('chatify.web.prefix', 'chatify'));
        } else {
            $this->line('Optional: php artisan chatify:publish --force');
        }

        $this->info('Chatify v2 installed.');

        return self::SUCCESS;
    }
}
