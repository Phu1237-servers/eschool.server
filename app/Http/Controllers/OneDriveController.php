<?php

namespace App\Http\Controllers;

use Cache;
use Illuminate\Http\Request;
use Microsoft\Graph\Graph;
// use Microsoft\Graph\Http\GraphResponse;
use Microsoft\Graph\Model\Directory;
use Microsoft\Graph\Model\User;
use Illuminate\Support\Facades\Http;

class OneDriveController extends Controller
{
    private $graph;
    public function __construct()
    {
        if (Cache::has('onedrive_access_token')) {
            $this->graph = new Graph();
            $this->graph->setAccessToken(Cache::get('onedrive_access_token'));
        } else {
            return redirect()->to('https://login.live.com/oauth20_authorize.srf?client_id='.env('ONEDRIVE_CLIENT_ID').'&scope='.env('ONEDRIVE_SCOPE').'&response_type=code&redirect_uri=https://your-video-api.test/access_token_response')->send();
        }
    }

    public function access_token_response(Request $request)
    {
        $code = $request->get('code');
        $this->getToken($code);

        return redirect()->to('/one');
    }

    private function getToken($code)
    {
        $body = [
            'client_id' => env('ONEDRIVE_CLIENT_ID'),
            'client_secret' => env('ONEDRIVE_CLIENT_SECRET'),
            'code' => $code,
            'redirect_uri' => 'https://your-video-api.test/access_token_response',
        ];
        $grant_type = 'authorization_code';
        if (!Cache::has('onedrive_access_token')) {
            $grant_type = 'refresh_token';
            $body['refresh_token'] = Cache::get('onedrive_refresh_token');
        }
        $body['grant_type'] = $grant_type;
        $http = Http::post('https://login.live.com/oauth20_token.srf', $body);

        Cache::rememberForever('onedrive_refresh_token', function () use ($http) {
            return $http['refresh_token'];
        });
        Cache::remember('onedrive_access_token', $http['expires_in'], function () use ($http) {
            return $http['access_token'];
        });

        return $http['token'];
    }

    private function auth()
    {
        $result = $this->graph->createRequest('GET', 'https://login.microsoftonline.com/consumers/oauth2/v2.0/token')
            ->attachBody([
                'grant_type' => 'client_credentials',
                'client_id' => 'eba0e7fd-a49a-4954-a40d-01e0dae08279',
                'client_secret'=> 'lXU8Q~fWeOHKQPaqnd6Hkr6fJjYctTWMfha4zc.o',
                'username' => 'phuvip867',
                'password' => '5SiUQWrf2h',
                'scope' => 'eba0e7fd-a49a-4954-a40d-01e0dae08279/.default offline_access',
            ])
            ->setReturnType(User::class)
            ->execute();

        var_dump($result); exit;

        return $result;
    }

    public function index()
    {
        $directory_path = '/Data/HocTap/Vue School';
        $directories = $this->getDirectoryContent($directory_path);
        foreach ($directories as $item) {
            $item = $item->getProperties();
            $item_path = $directory_path.'/'.$item['name'];
            // echo $item->name. '<br>';
        }

        return $directories;
    }

    private function getDirectoryContent($path): Directory
    {
        return $this->graph->createRequest('GET', 'https://graph.microsoft.com/v1.0/me/drive/root:'.$path.':/children?$select=id,name,webUrl')
            ->setReturnType(Directory::class)
            ->execute();
    }
}
