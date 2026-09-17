<?php

namespace App\Providers;

use App\Services\Face\Contracts\FaceEngine;
use App\Services\Face\Engines\HttpFaceEngine;
use App\Support\AuthContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AuthContext::class);
        $this->app->bind(FaceEngine::class, HttpFaceEngine::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Token-based (not session/cookie) broadcasting auth — replaces the default
        // 'web'-middleware /broadcasting/auth route registered by withRouting().
        Broadcast::routes(['middleware' => ['auth.jwt']]);
        require base_path('routes/channels.php');

        RateLimiter::for('general', fn (Request $request) => Limit::perMinutes(15, 100)
            ->by($request->ip())
            ->response(fn () => response()->json([
                'success' => false,
                'message' => 'Too many requests, please try again later',
            ], 429)));

        RateLimiter::for('auth', fn (Request $request) => Limit::perMinutes(15, 10)
            ->by($request->ip())
            ->response(fn () => response()->json([
                'success' => false,
                'message' => 'Too many login attempts, please try again later',
            ], 429)));

        RateLimiter::for('exam', fn (Request $request) => Limit::perMinutes(15, 60)
            ->by($request->ip())
            ->response(fn () => response()->json([
                'success' => false,
                'message' => 'Too many requests, please try again later',
            ], 429)));

        RateLimiter::for('face', fn (Request $request) => Limit::perMinutes(15, 20)
            ->by($request->ip())
            ->response(fn () => response()->json([
                'success' => false,
                'message' => 'Too many face verification attempts, please try again later',
            ], 429)));

        RateLimiter::for('monitoring', fn (Request $request) => Limit::perMinute(5)
            ->by($request->ip())
            ->response(fn () => response()->json([
                'success' => false,
                'message' => 'Too many monitoring requests',
            ], 429)));
    }
}
