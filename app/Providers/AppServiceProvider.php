<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;

use App\Models\Post;
use App\Observers\PostObserver;
use App\Models\Bank;
use App\Observers\BankObserver;
use App\Models\CsMainProject;
use App\Observers\CsMainProjectObserver;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url') . "/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });
        Post::observe(PostObserver::class);
        Bank::observe(BankObserver::class);
        CsMainProject::observe(CsMainProjectObserver::class);

        // Define morph map for polymorphic relationships
        Relation::morphMap([
            'user' => \App\Models\User::class,
            'role' => \Spatie\Permission\Models\Role::class,
        ]);
    }
}
