<?php

namespace App\Providers;

use App\Models\EmploiDuTemps;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);

        Route::bind('seance', fn (string $valeur) => EmploiDuTemps::query()->findOrFail($valeur));

        RateLimiter::for('connexion', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });
    }
}
