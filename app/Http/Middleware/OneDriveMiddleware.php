<?php

namespace App\Http\Middleware;

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
        if (Cache::has('onedrive_access_token')) {
            return $next($request);
        }
        return redirect()->to('https://login.live.com/oauth20_authorize.srf?client_id='.env('ONEDRIVE_CLIENT_ID').'&scope='.env('ONEDRIVE_SCOPE').'&response_type=code&redirect_uri='.env('ONEDRIVE_REDIRECT_URI'))->send();
    }
}
