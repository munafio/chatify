<?php

namespace Chatify\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chatify:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install Chatify package';

    /**
     * Check Laravel version.
     *
     * @var bool
     */
    private $isV8;

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->isV8 = explode('.', app()->version())[0] >= 8;
        $this->isV9 = explode('.', app()->version())[0] >= 9;

        $this->info('Installing Chatify...');

        $this->line('----------');
        $this->line('Configurations...');
        $this->modifyModelsPath('/../Http/Controllers/MessagesController.php', 'User');
        $this->modifyModelsPath('/../Http/Controllers/MessagesController.php', 'ChFavorite');
        $this->modifyModelsPath('/../Http/Controllers/MessagesController.php', 'ChMessage');
        $this->modifyModelsPath('/../Http/Controllers/Api/MessagesController.php', 'User');
        $this->modifyModelsPath('/../Http/Controllers/Api/MessagesController.php', 'ChFavorite');
        $this->modifyModelsPath('/../Http/Controllers/Api/MessagesController.php', 'ChMessage');
        $this->modifyModelsPath('/../ChatifyMessenger.php', 'ChFavorite');
        $this->modifyModelsPath('/../ChatifyMessenger.php', 'ChMessage');
        $this->modifyModelsPath('/../Models/ChFavorite.php');
        $this->modifyModelsPath('/../Models/ChMessage.php');
        $this->info('[✓] done');

        $assetsToBePublished = [
            'config' => config_path('chatify.php'),
            'views' => resource_path('views/vendor/Chatify'),
            'assets' => public_path('css/chatify'),
            'models' => app_path(($this->isV8 ? 'Models/' : '') . 'ChMessage.php'),
            'migrations' => database_path('migrations/2019_09_22_192348_create_messages_table.php'),
            'routes' => base_path('routes/chatify'),
        ];

        foreach ($assetsToBePublished as $target => $path) {
            $this->line('----------');
            $this->process($target, $path);
        }

        $this->line('----------');
        $this->line('Creating storage symlink...');
        Artisan::call('storage:link');
        $this->info('[✓] Storage linked.');
        $this->line('----------');

        $packageJsonPath = base_path('package.json');
        $packageJson = json_decode(File::get($packageJsonPath), true);

        $packageJson['dependencies']['extendable-media-recorder'] = "^9.2.4";
        $packageJson['dependencies']['extendable-media-recorder-wav-encoder'] = "^7.0.111";
        $packageJson['dependencies']['wav-blob-util'] = "^1.0.21";
        $packageJson['dependencies']['wavesurfer.js'] = "^7.8.0";

        // Save the updated package.json
        File::put($packageJsonPath, json_encode($packageJson, JSON_PRETTY_PRINT));

        $this->info('[✓] Dependencies added to package.json successfully.');

        $this->line('----------');
        if ($this->isV9) {
            $configPath = base_path('vite.config.js');
            $newContent = <<<EOT
                import { defineConfig } from 'vite';
                import laravel from 'laravel-vite-plugin';

                export default defineConfig({
                    plugins: [
                        laravel({
                            input: ['resources/css/app.css',
                                'resources/js/app.js',
                                'public/js/chatify/audioRecorder.js',
                                'public/js/chatify/voiceMessage.js',],
                            refresh: true,
                        }),
                    ],
                });
            EOT;
        } else {
            $configPath = base_path('webpack.mix.js');
            $newContent = <<<EOT
                const mix = require('laravel-mix');

                /*
                |--------------------------------------------------------------------------
                | Mix Asset Management
                |--------------------------------------------------------------------------
                |
                | Mix provides a clean, fluent API for defining some Webpack build steps
                | for your Laravel application. By default, we are compiling the Sass
                | file for the application as well as bundling up all the JS files.
                |
                */

                mix.js('resources/js/app.js', 'public/js')
                    .js('public/js/chatify/audioRecorder.js', 'public/js')
                    .js('public/js/chatify/voiceMessage.js', 'public/js')
                    .postCss("resources/css/app.css", "public/css", [
                        require("tailwindcss"),
                    ])
                    .sourceMaps();
            EOT;
        }
        if (file_exists($configPath)) {
            file_put_contents($configPath, $newContent);
            $this->info('[✓] js complie file has been overwritten successfully');
        }

        $this->line('----------');
        $this->info('[✓] Chatify installed successfully');
    }

    /**
     * Modify models imports/namespace path according to Laravel version.
     *
     * @param string $targetFilePath
     * @param string $model
     * @return void
     */
    private function modifyModelsPath($targetFilePath, $model = null)
    {
        $path = realpath(__DIR__ . $targetFilePath);
        $contents = File::get($path);
        $model = !empty($model) ? '\\' . $model : ';';
        $contents = str_replace(
            (!$this->isV8 ? 'App\Models' : 'App') . $model,
            ($this->isV8 ? 'App\Models' : 'App') . $model,
            $contents
        );
        File::put($path, $contents);
    }

    /**
     * Check, publish, or overwrite the assets.
     *
     * @param string $target
     * @param string $path
     * @return void
     */
    private function process($target, $path)
    {
        $this->line('Publishing ' . $target . '...');
        if (!File::exists($path)) {
            $this->publish($target);
            $this->info('[✓] ' . $target . ' published.');
            return;
        }
        if ($this->shouldOverwrite($target)) {
            $this->line('Overwriting ' . $target . '...');
            $this->publish($target, true);
            $this->info('[✓] ' . $target . ' published.');
            return;
        }
        $this->line('[-] Ignored, The existing ' . $target . ' was not overwritten');
    }

    /**
     * Ask to overwrite.
     *
     * @param string $target
     * @return void
     */
    private function shouldOverwrite($target)
    {
        return $this->confirm(
            $target . ' already exists. Do you want to overwrite it?',
            false
        );
    }

    /**
     * Call the publish command.
     *
     * @param string $tag
     * @param bool $forcePublish
     * @return void
     */
    private function publish($tag, $forcePublish = false)
    {
        $this->call('vendor:publish', [
            '--tag' => 'chatify-' . $tag,
            '--force' => $forcePublish,
        ]);
    }
}
