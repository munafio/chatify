<?php

namespace Chatify;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ChatifyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        app()->bind('ChatifyMessenger', function () {
            return new \Chatify\ChatifyMessenger;
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Load Views, Migrations and Routes
        $this->loadViewsFrom(__DIR__ . '/../views', 'Chatify');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadRoutes();

        // Publishes
        $this->setPublishes();
    }

    /**
     * Publishing the files that the user may override.
     *
     * @return void
     */
    protected function setPublishes()
    {
        // Config
        $this->publishes([
            __DIR__ . '/../config/chatify.php' => config_path('chatify.php')
        ], 'chatify-config');

        // Migrations
        $this->publishes([
            __DIR__ . '/../database/migrations/' => database_path('migrations')
        ], 'chatify-migrations');

        // Controllers
        $this->publishes([
            __DIR__ . '/../src/Http/Controllers' => app_path('Http/Controllers/vendor/Chatify')
        ], 'chatify-controllers');

        // Views
        $this->publishes([
            __DIR__ . '/../views' => resource_path('views/vendor/Chatify')
        ], 'chatify-views');

        // Assets
        $this->publishes([
            // CSS
            __DIR__ . '/../assets/css' => public_path('css/chatify'),
            // JavaScript
            __DIR__ . '/../assets/js' => public_path('js/chatify'),
            // Images
            __DIR__ . '/../assets/imgs' => storage_path('app/public/' . config('chatify.user_avatar.folder')),
        ], 'chatify-assets');
    }
    /**
     * Group the routes and set up configurations to load them.
     *
     * @return void
     */
    protected function loadRoutes()
    {
        Route::group($this->routesConfigurations(), function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        });
    }

    /**
     * Routes configurations.
     *
     * @return array
     */
    private function routesConfigurations()
    {
        return [
            'prefix' => config('chatify.path'),
            'namespace' => 'Chatify\Http\Controllers',
            'middleware' => ['web', config('chatify.middleware')],
        ];
    }
}
