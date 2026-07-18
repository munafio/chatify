<?php

declare(strict_types=1);

namespace Chatify;

use Chatify\Console\BuildCommand;
use Chatify\Console\InstallCommand;
use Chatify\Console\PublishCommand;
use Chatify\Contracts\RecipientResolver;
use Chatify\Models\Conversation;
use Chatify\Models\Message;
use Chatify\Policies\ConversationPolicy;
use Chatify\Policies\MessagePolicy;
use Chatify\Services\DefaultRecipientResolver;
use Chatify\Support\ChatifyModels;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ChatifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/config/chatify.php', 'chatify');

        $this->app->bind(RecipientResolver::class, DefaultRecipientResolver::class);

        $this->app->bind('ChatifyMessenger', fn () => new ChatifyMessenger);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/views', 'Chatify');
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        $this->registerPolicies();
        $this->registerRateLimiters();
        $this->registerRouteBindings();
        $this->loadRoutes();
        $this->loadBroadcastChannels();

        if ($this->app->runningInConsole()) {
            $this->commands([
                BuildCommand::class,
                InstallCommand::class,
                PublishCommand::class,
            ]);
            $this->setPublishes();
        }
    }

    protected function registerPolicies(): void
    {
        Gate::policy(ChatifyModels::conversationClass(), ConversationPolicy::class);
        Gate::policy(ChatifyModels::messageClass(), MessagePolicy::class);
    }

    protected function registerRateLimiters(): void
    {
        RateLimiter::for('chatify-messages', function ($request) {
            return Limit::perMinute(60)->by($request->user()?->getKey() ?: $request->ip());
        });

        RateLimiter::for('chatify-uploads', function ($request) {
            return Limit::perMinute(10)->by($request->user()?->getKey() ?: $request->ip());
        });
    }

    protected function registerRouteBindings(): void
    {
        Route::bind('conversation', function (string $value) {
            $user = auth()->user();

            if ($user === null) {
                abort(401);
            }

            $conversation = ChatifyModels::conversationClass()::query()
                ->forUser((int) $user->getKey())
                ->where('id', $value)
                ->first();

            if ($conversation === null) {
                abort(404);
            }

            return $conversation;
        });

        Route::bind('message', function (string $value) {
            $user = auth()->user();

            if ($user === null) {
                abort(401);
            }

            $participantTable = config('chatify.tables.participants', 'ch_conversation_participants');
            $messageTable = config('chatify.tables.messages', 'ch_messages');

            $message = ChatifyModels::messageClass()::query()
                ->where("{$messageTable}.id", $value)
                ->whereIn('conversation_id', function ($query) use ($participantTable, $user) {
                    $query->select('conversation_id')
                        ->from($participantTable)
                        ->where('user_id', $user->getKey());
                })
                ->first();

            if ($message === null) {
                abort(404);
            }

            return $message->load('conversation');
        });
    }

    protected function loadRoutes(): void
    {
        $this->loadRoutesFrom(__DIR__.'/routes/api.php');

        if (config('chatify.web.enabled', true)) {
            $this->loadRoutesFrom(__DIR__.'/routes/web.php');
        }
    }

    protected function loadBroadcastChannels(): void
    {
        if (file_exists(__DIR__.'/routes/channels.php')) {
            require __DIR__.'/routes/channels.php';
        }
    }

    protected function setPublishes(): void
    {
        $this->publishes([
            __DIR__.'/config/chatify.php' => config_path('chatify.php'),
        ], 'chatify-config');

        $this->publishes([
            __DIR__.'/database/migrations/2024_01_01_000001_create_chatify_v2_tables.php' => database_path('migrations/2024_01_01_000001_create_chatify_v2_tables.php'),
            __DIR__.'/database/migrations/2024_01_01_000002_add_theme_preferences_to_user_settings.php' => database_path('migrations/2024_01_01_000002_add_theme_preferences_to_user_settings.php'),
            __DIR__.'/database/migrations/2024_01_01_000003_extend_messages_for_actions.php' => database_path('migrations/2024_01_01_000003_extend_messages_for_actions.php'),
            __DIR__.'/database/migrations/2024_01_01_000004_extend_groups_for_management.php' => database_path('migrations/2024_01_01_000004_extend_groups_for_management.php'),
            __DIR__.'/database/migrations/2024_01_01_000005_add_system_messages.php' => database_path('migrations/2024_01_01_000005_add_system_messages.php'),
        ], 'chatify-migrations');

        $this->publishes([
            __DIR__.'/routes/channels.php' => base_path('routes/chatify/channels.php'),
        ], 'chatify-channels');

        $this->publishes([
            __DIR__.'/../dist' => public_path('vendor/chatify'),
        ], 'chatify-assets');

        $this->publishes([
            __DIR__.'/../resources/patterns' => public_path('vendor/chatify/patterns'),
        ], 'chatify-patterns');

        $this->publishes([
            __DIR__.'/../frontend' => resource_path('vendor/chatify/frontend'),
        ], 'chatify-frontend');

        $this->publishes([
            __DIR__.'/views' => resource_path('views/vendor/chatify'),
        ], 'chatify-views');
    }
}
