<?php

namespace App\Providers;

use App\Interfaces\Headquarters\RegistrationService;
use App\Repositories\Headquarters\RegistrationRepository;
use App\Services\Headquarters\ChangeRecorder;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class HeadquartersServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ChangeRecorder::class);
    }

    public array $bindings = [RegistrationService::class => RegistrationRepository::class];

    public function boot(): void
    {
        $response = fn (Request $request, array $headers) => response()->json([
            'message' => 'Demasiadas solicitudes de Headquarters. Intente más tarde.', 'code' => 429, 'data' => null,
        ], 429, $headers);
        RateLimiter::for('headquarters-registration', fn (Request $request): array => [
            Limit::perMinute(3)->by('minute:'.$request->ip())->response($response),
            Limit::perHour(10)->by('hour:'.$request->ip())->response($response),
            Limit::perHour(30)->by('mail-global')->response($response),
        ]);
        RateLimiter::for('headquarters-confirmation', fn (Request $request) => Limit::perMinute(10)->by($request->ip())->response($response));
        RateLimiter::for('headquarters-access', fn (Request $request) => Limit::perMinute(120)->by($request->ip())->response($response));
    }
}
