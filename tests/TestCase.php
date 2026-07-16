<?php

declare(strict_types=1);

namespace Chatify\Tests;

use Chatify\ChatifyServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            ChatifyServiceProvider::class,
            \Laravel\Sanctum\SanctumServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('chatify.models.user', TestUser::class);
        $app['config']->set('chatify.api.middleware', ['api', 'auth:sanctum']);
        $app['config']->set('chatify.web.enabled', true);
        $app['config']->set('chatify.web.middleware', ['web', 'auth']);
        $app['config']->set('broadcasting.default', 'null');
    }

    protected function defineRoutes($router): void
    {
        $router->get('login', fn () => 'login')->name('login');
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->loadMigrationsFrom(__DIR__.'/../src/database/migrations');
    }

    protected function createUser(array $attributes = []): TestUser
    {
        return TestUser::query()->create(array_merge([
            'name' => 'Test User',
            'email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('password'),
        ], $attributes));
    }
}
