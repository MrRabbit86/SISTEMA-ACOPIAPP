<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

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
        // Anti fuerza bruta por cuenta (email), no por IP compartida:
        // intentos de cada cuenta se cuentan por separado.
        RateLimiter::for('login.web', fn (Request $request): Limit => $this->limiteLogin(6, $request));
        RateLimiter::for('login.api', fn (Request $request): Limit => $this->limiteLogin(5, $request));
    }

    private function limiteLogin(int $porMinuto, Request $request): Limit
    {
        $email = strtolower(trim((string) $request->string('email')));
        $llave = $email !== '' ? $email : $request->ip();

        return Limit::perMinute($porMinuto)->by($llave);
    }
}
