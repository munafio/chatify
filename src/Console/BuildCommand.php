<?php

declare(strict_types=1);

namespace Chatify\Console;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class BuildCommand extends Command
{
    protected $signature = 'chatify:build
                            {--skip-build : Publish existing dist assets without running npm build}
                            {--install : Run npm install before building}';

    protected $description = 'Build the Chatify Vue frontend and publish assets to public/vendor/chatify';

    public function handle(): int
    {
        $packageRoot = dirname(__DIR__, 2);
        $frontendPath = $packageRoot.DIRECTORY_SEPARATOR.'frontend';

        if (! is_dir($frontendPath)) {
            $this->error('Chatify frontend directory not found: '.$frontendPath);

            return self::FAILURE;
        }

        if ($this->option('install')) {
            if ($this->runNpm($frontendPath, 'install') !== self::SUCCESS) {
                return self::FAILURE;
            }
        }

        if (! $this->option('skip-build')) {
            if (! is_dir($frontendPath.DIRECTORY_SEPARATOR.'node_modules')) {
                $this->warn('node_modules missing — running npm install first.');
                if ($this->runNpm($frontendPath, 'install') !== self::SUCCESS) {
                    return self::FAILURE;
                }
            }

            $this->info('Building Chatify frontend…');
            if ($this->runNpm($frontendPath, 'run build') !== self::SUCCESS) {
                return self::FAILURE;
            }
        }

        $distPath = $packageRoot.DIRECTORY_SEPARATOR.'dist';
        if (! is_dir($distPath) || ! is_file($distPath.DIRECTORY_SEPARATOR.'chatify.js')) {
            $this->error('Build output missing at '.$distPath.'. Run without --skip-build.');

            return self::FAILURE;
        }

        $this->info('Publishing assets to public/vendor/chatify…');
        $this->call('vendor:publish', [
            '--tag' => 'chatify-assets',
            '--force' => true,
        ]);

        $this->call('vendor:publish', [
            '--tag' => 'chatify-patterns',
            '--force' => true,
        ]);

        $this->call('vendor:publish', [
            '--tag' => 'chatify-sounds',
            '--force' => true,
        ]);

        $this->newLine();
        $this->info('Chatify assets published.');
        $this->line('  → '.public_path('vendor/chatify'));

        return self::SUCCESS;
    }

    private function runNpm(string $frontendPath, string $command): int
    {
        $npm = $this->npmBinary();
        $process = Process::fromShellCommandline($npm.' '.$command, $frontendPath);
        $process->setTimeout(600);
        $process->run(function (string $type, string $buffer): void {
            $this->output->write($buffer);
        });

        if (! $process->isSuccessful()) {
            $this->error('npm '.$command.' failed.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function npmBinary(): string
    {
        return PHP_OS_FAMILY === 'Windows' ? 'npm.cmd' : 'npm';
    }
}
