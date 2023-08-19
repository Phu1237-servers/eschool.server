<?php

namespace App\Http\Controllers;

use Cache;
use Illuminate\Http\Request;
use Microsoft\Graph\Graph;
use Microsoft\Graph\Model\Thumbnail;
use Microsoft\Graph\Model\Directory;
use Microsoft\Graph\Model\Video;
use Illuminate\Support\Facades\Http;
use Log;

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
        Log::info($http->body());
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
        // $this->flush();

        return $result->map(function ($item) {
            if (isset($item['children'])) {
                $item['children'] = $this->mergeVideoSub($item['children']);
            }

            return $item;
        });
    }

    private function mergeVideoSub($data)
    {
        $data = $data->groupBy('file.basename');
        $result = $data->map(function ($item, $key) {
            $video = $item->firstWhere('file.extension', 'mp4');
            $subtitle = $item->firstWhere('file.extension', 'vtt');
            if (!$video) {
                return [
                    'type' => 'other',
                    'data' => $item
                ];
            }

            $result = [
                'type' => 'video',
                'data' => [
                    'title' => $key,
                    'videoUrl' => $video['url'],
                    'videoThumbnail' => $video['thumbnail'],
                ]
            ];
            if ($subtitle) {
                $result['data']['subtitleUrl'] = $subtitle['url'];
            }

            return $result;
        })->values();

        return $result;
    }

    private function getDirectory($path, $recusion = false)
    {
        $directories = $this->getDirectoryByPath($path);
        $result = collect();
        for ($i = 0; $i < count($directories); $i++) {
            $result->push($this->directoryToCollection($directories[$i], $recusion));
        }

        return $result;
    }

    private function directoryToCollection($directory, $recusion = false)
    {
        $collection = collect($directory->getProperties());
        if ($recusion && isset($collection['folder'])) {
            $path = urldecode($collection['parentReference']['path']).'/'.$collection['name'];
            $collection->put('children', $this->getDirectory($path, $recusion));
        }
        if (isset($collection['file'])) {
            // Video properties
            if ($collection['file']['mimeType'] == 'video/mp4') {
                $collection = collect($this->getVideoById($collection['id'])->getProperties());
            }
            // File name without extension
            $collection->put('file', [
                'basename' => pathinfo($collection['name'], PATHINFO_FILENAME),
                'extension' => pathinfo($collection['name'], PATHINFO_EXTENSION),
            ]);
            // Public download url
            if (isset($collection['@microsoft.graph.downloadUrl'])) {
                $collection->put('url', $collection['@microsoft.graph.downloadUrl']);
            }
        }
        // File thumbnail
        $collection->put('thumbnail', collect($this->getThumbnailById($collection['id'])));

        return $this->model_to_array($collection);
    }

    private function model_to_array($model)
    {
        $allowed = [
            'id', 'name', 'webUrl', 'file',
            'baseName', 'children', 'url', 'video', 'thumbnail', // Custom properties
        ];

        return $model->only($allowed);
    }

    private function getDirectoryByPath($path): Directory | array
    {
        return $this->graph->createRequest('GET', 'https://graph.microsoft.com/v1.0/me/'.$path.':/children')
            ->setReturnType(Directory::class)
            ->execute();
    }

    private function getVideoById($id)
    {
        return $this->graph->createRequest('GET', 'https://graph.microsoft.com/v1.0/me/drive/items/'.$id)
            ->setReturnType(Video::class)
            ->execute();
    }

    private function getThumbnailById($id)
    {
        return $this->graph->createRequest('GET', 'https://graph.microsoft.com/v1.0/me/drive/items/'.$id.'/thumbnails')
            ->setReturnType(Thumbnail::class)
            ->execute();
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
