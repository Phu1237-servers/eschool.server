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
        $this->graph = new Graph();
        $this->graph->setAccessToken(Cache::get('onedrive_access_token'));
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
            'redirect_uri' => env('ONEDRIVE_REDIRECT_URI'),
        ];
        $grant_type = 'authorization_code';
        if (!Cache::has('onedrive_access_token') && Cache::has('onedrive_refresh_token')) {
            $grant_type = 'refresh_token';
            $body['refresh_token'] = Cache::get('onedrive_refresh_token');
        }
        $body['grant_type'] = $grant_type;
        $http = Http::asForm()
            ->post('https://login.live.com/oauth20_token.srf', $body);

        Cache::rememberForever('onedrive_refresh_token', function () use ($http) {
            return $http['refresh_token'];
        });
        Cache::remember('onedrive_access_token', $http['expires_in'], function () use ($http) {
            return $http['access_token'];
        });

        return $http['access_token'];
    }

    public function index()
    {
        $directory_path = 'drive/root:/Data/HocTap/Vue School';
        $directories = $this->getDirectoryContent($directory_path);
        $result = [];
        for ($i = 0; $i < count($directories); $i++) {
            $result[$i] = $this->directoryToArray($directories[$i]);
        }

        return $result;
    }

    private function directoryToArray($directory, $recusion = false)
    {
        $item = $directory->getProperties();
        $array = [];
        $continue = [
            'id', 'name', 'webUrl', 'parentReference', 'folder',
        ];
        foreach($item as $key => $value) {
            if (!in_array($key, $continue)) {
                continue;
            }
            $array[$key] = $value;
        }
        if ($recusion && isset($array['folder'])) {
            $path = urldecode($array['parentReference']['path']).'/'.$array['name'];
            $directories = $this->getDirectoryContent($path);
            $children = [];
            for ($i = 0; $i < count($directories); $i++) {
                $children[$i] = $this->directoryToArray($directories[$i]);
            }
            $array['children'] = $children;
        }
        return $array;
    }

    private function getDirectoryContent($path): Directory|Array
    {
        if ($this->graph) {
            return $this->graph->createRequest('GET', 'https://graph.microsoft.com/v1.0/me/'.$path.':/children')
                ->setReturnType(Directory::class)
                ->execute();
        }
        return [];
    }
    
    public function logout()
    {
        Cache::forget('onedrive_refresh_token');
        Cache::forget('onedrive_access_token');
    }
}
