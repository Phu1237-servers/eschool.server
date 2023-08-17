<?php

namespace App\Http\Controllers;

use Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Microsoft\Graph\Graph;
// use Microsoft\Graph\Http\GraphResponse;
use Microsoft\Graph\Model\Directory;
use Microsoft\Graph\Model\Video;
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
        $this->assign_token($code);

        return redirect()->route('one');
    }

    private function assign_token($code)
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
    }

    public function index()
    {
        if (!Cache::has('onedrive_directories')) {
            Cache::rememberForever('onedrive_directories', function () {
                $directory_path = 'drive/root:'.env('ONEDRIVE_ROOT');

                return $this->getDirectory($directory_path, 1);
            });
        }

        $result = Cache::get('onedrive_directories');
        $this->flush();

        return $result;
    }

    private function getDirectory($path, $recusion = false)
    {
        $directories = $this->getDirectoryByPath($path);
        $result = [];
        for ($i = 0; $i < count($directories); $i++) {
            $result[$i] = $this->directoryToArray($directories[$i], $recusion);
        }

        return $result;
    }

    private function directoryToArray($directory, $recusion = false)
    {
        $array = [];
        foreach ($directory->getProperties() as $key => $value) {
            $array[$key] = $value;
        }
        if ($recusion && isset($array['folder'])) {
            $path = urldecode($array['parentReference']['path']).'/'.$array['name'];
            $array['children'] = $this->getDirectory($path, $recusion);
        }
        if (isset($array['file'])) {
            if ($array['file']['mimeType'] == 'video/mp4') {
                $array = $this->getVideoById($array['id'])->getProperties();
            }
        }
        if (isset($array['@microsoft.graph.downloadUrl'])) {
            $array['url'] = $array['@microsoft.graph.downloadUrl'];
        }

        return $this->model_to_array($array);
    }

    private function model_to_array($model)
    {
        $allowed = [
            'id', 'name', 'webUrl', 'children', 'url', 'video', 'file'
        ];

        return Arr::only($model, $allowed);
    }

    private function getDirectoryByPath($path): Directory | array
    {
        if ($this->graph) {
            return $this->graph->createRequest('GET', 'https://graph.microsoft.com/v1.0/me/'.$path.':/children')
                ->setReturnType(Directory::class)
                ->execute();
        }

        return [];
    }

    private function getVideoById($id)
    {
        if ($this->graph) {
            return $this->graph->createRequest('GET', 'https://graph.microsoft.com/v1.0/me/drive/items/'.$id)
                ->setReturnType(Video::class)
                ->execute();
        }

        return [];
    }

    public function flush()
    {
        Cache::forget('onedrive_directories');
    }


    public function revoke()
    {
        Cache::forget('onedrive_refresh_token');
        Cache::forget('onedrive_access_token');
    }
}
