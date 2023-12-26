<?php

namespace App\Repositories\Cloud;

use App\Types\OneDriveType;
use Microsoft\Graph\Graph;
use Illuminate\Support\Facades\Http;
use Cache;
use Microsoft\Graph\Model\Thumbnail;
use Microsoft\Graph\Model\Directory;
use Microsoft\Graph\Model\Video;

class OneDriveRepository implements OneDriveInterface
{
    protected $graph;

    public function __construct()
    {
        $this->graph = new Graph();
        $this->graph = $this->graph->setAccessToken(Cache::get(\App\Types\OneDriveType::CACHE_ACCESS_TOKEN));
    }

    public function getDirectoryByPath($path): Directory | array
    {
        $this->graph->setAccessToken(Cache::get(\App\Types\OneDriveType::CACHE_ACCESS_TOKEN));

        return $this->graph->createRequest('GET', 'https://graph.microsoft.com/v1.0/me/'.$path.':/children')
            ->setReturnType(Directory::class)
            ->execute();
    }

    public function getVideoById($id): Video | array
    {
        return $this->graph->createRequest('GET', 'https://graph.microsoft.com/v1.0/me/drive/items/'.$id)
            ->setReturnType(Video::class)
            ->execute();
    }

    public function getThumbnailById($id): Thumbnail | array
    {
        return $this->graph->createRequest('GET', 'https://graph.microsoft.com/v1.0/me/drive/items/'.$id.'/thumbnails')
            ->setReturnType(Thumbnail::class)
            ->execute();
    }

    public function assignToken($code, $redirect_url, $logger = null): void
    {
        $body = [
            'client_id' => env('ONEDRIVE_CLIENT_ID'),
            'client_secret' => env('ONEDRIVE_CLIENT_SECRET'),
            'code' => $code,
            'redirect_uri' => $redirect_url,
        ];
        $grant_type = 'authorization_code';
        if (!Cache::has(OneDriveType::CACHE_ACCESS_TOKEN) && Cache::has(OneDriveType::CACHE_REFRESH_TOKEN)) {
            $grant_type = 'refresh_token';
            $body['refresh_token'] = Cache::get(OneDriveType::CACHE_REFRESH_TOKEN);
        }
        $body['grant_type'] = $grant_type;
        $http = Http::asForm()
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
    }

    public function revokeToken(): void
    {
        Cache::forget(OneDriveType::CACHE_REFRESH_TOKEN);
        Cache::forget(OneDriveType::CACHE_ACCESS_TOKEN);
    }
}
