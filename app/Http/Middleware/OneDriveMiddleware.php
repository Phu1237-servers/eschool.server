<?php

namespace App\Http\Middleware;

use App\Types\OneDriveType;
use Cache;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OneDriveMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Cache::has(OneDriveType::CACHE_ACCESS_TOKEN)) {
            return $next($request);
        }
        if (Cache::has(OneDriveType::CACHE_REFRESH_TOKEN)) {
            if ($this->refreshToken()) {
                return $next($request);
            }
        }

        return redirect()->to('https://login.live.com/oauth20_authorize.srf?client_id='.env('ONEDRIVE_CLIENT_ID').'&scope='.env('ONEDRIVE_SCOPE').'&response_type=code&redirect_uri='.env('ONEDRIVE_REDIRECT_URI'))->send();
    }

    public function refreshToken(): bool
    {
        $logger = \Log::build([
            'driver' => 'single',
            'path' => storage_path('logs/token.log'),
        ]);
        $body = [
            'client_id' => env('ONEDRIVE_CLIENT_ID'),
            'client_secret' => env('ONEDRIVE_CLIENT_SECRET'),
            'redirect_uri' => env('ONEDRIVE_REDIRECT_URI'),
            'refresh_token' => Cache::get(OneDriveType::CACHE_REFRESH_TOKEN),
            'grant_type' => 'refresh_token',
        ];
        $http = \Http::asForm()
            ->post('https://login.live.com/oauth20_token.srf', $body);
        if ($logger) {
            $logger->info($http->body());
        }

        $response = $http->json();
        Cache::rememberForever(OneDriveType::CACHE_REFRESH_TOKEN, function () use ($response) {
            return $response['refresh_token'];
        });
        Cache::remember(OneDriveType::CACHE_ACCESS_TOKEN, $response['expires_in'], function () use ($response) {
            return $response['access_token'];
        });

        return true;
    }

}
