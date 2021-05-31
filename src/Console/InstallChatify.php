<?php

namespace Chatify\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class InstallChatify extends Command
{
    protected $signature = 'chatify:install';

    protected $description = 'Install Chatify package';

    private $isV8;

    public function handle()
    {
        $this->isV8 = explode('.',app()->version())[0] >= 8;

        $this->info('Installing Chatify...');

        $this->line('----------');
        $this->line('Configurations...');
        $this->modifyModelsPath('/../Http/Controllers/MessagesController.php','User');
        $this->modifyModelsPath('/../Http/Controllers/MessagesController.php','ChFavorite');
        $this->modifyModelsPath('/../Http/Controllers/MessagesController.php','ChMessage');
        $this->modifyModelsPath('/../ChatifyMessenger.php','ChFavorite');
        $this->modifyModelsPath('/../ChatifyMessenger.php','ChMessage');
        $this->modifyModelsPath('/../Models/ChFavorite.php');
        $this->modifyModelsPath('/../Models/ChMessage.php');
        $this->info('[✓] done');

        $assetsToBePublished = [
            'config' => config_path('chatify.php'),
            'views' => resource_path('views/vendor/Chatify'),
            'assets' => public_path('css/chatify'),
            'models' => app_path(($this->isV8 ? 'Models/' : '').'ChMessage.php'),
            'migrations' => database_path('migrations/2019_09_22_192348_create_messages_table.php'),
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
        $this->info('[✓] Chatify installed successfully');
    }

    private function modifyModelsPath($targetFilePath, $model = null){
        $path = realpath(__DIR__.$targetFilePath);
        $contents = File::get($path);
        $model = !empty($model) ? '\\'.$model : ';';
        $contents = str_replace(
            (!$this->isV8 ? 'App\Models' : 'App').$model,
            ($this->isV8 ? 'App\Models' : 'App').$model,
            $contents
        );
        File::put($path, $contents);
    }

    private function process($target, $path)
    {
        $this->line('Publishing '.$target.'...');
        if (!File::exists($path)) {
            $this->publish($target);
            $this->info('[✓] '.$target.' published.');
        } else {
            if ($this->shouldOverwrite($target)) {
                $this->line('Overwriting '.$target.'...');
                $this->publish($target,true);
            } else {
                $this->line('[-] Ignored, The existing '.$target.' was not overwritten');
            }
        }
    }

    private function shouldOverwrite($target)
    {
        return $this->confirm(
            $target.' already exists. Do you want to overwrite it?',
            false
        );
    }

    private function publish($tag, $forcePublish = false)
    {
        $params = [
            '--provider' => "Chatify\ChatifyServiceProvider",
            '--tag' => 'chatify-'.$tag
        ];

        if ($forcePublish === true) {
            $params['--force'] = '';
        }

       $this->call('vendor:publish', $params);
    }
}
