<?php

namespace App\Repositories;

use App\Repositories\Cloud\OneDriveInterface;

class InstallRepository implements InstallInterface
{
    private $oneDriveRepository;
    public function __construct(OneDriveInterface $oneDriveRepository)
    {
        $this->oneDriveRepository = $oneDriveRepository;
    }

    public function getDirectory($path, $recusion = false)
    {
        $directories = $this->oneDriveRepository->getDirectoryByPath($path);
        $result = collect();
        for ($i = 0; $i < count($directories); $i++) {
            $result->push($this->directoryToCollection($directories[$i], $recusion));
        }

        return $result;
    }

    private function directoryToCollection($directory, $recusion = false)
    {
        $collection = collect($directory->getProperties());
        $path = urldecode($collection['parentReference']['path']).'/'.$collection['name'];
        if ($recusion && isset($collection['folder'])) {
            $collection->put('children', $this->getDirectory($path, $recusion));
        }
        if (isset($collection['file'])) {
            // Video properties
            if ($collection['file']['mimeType'] == 'video/mp4') {
                $collection = collect($this->oneDriveRepository->getVideoById($collection['id'])->getProperties());
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
        $collection->put('path', $path);
        // File thumbnail
        $collection->put('thumbnail', collect($this->oneDriveRepository->getThumbnailById($collection['id'])));

        return $this->toArray($collection);
    }

    private function toArray($model)
    {
        $allowed = [
            'id', 'name', 'webUrl', 'file',
            'path', 'children', 'url', 'video', 'thumbnail', // Custom properties
        ];

        return $model->only($allowed);
    }

    public function mergeVideoWithSub($data)
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
                    'id' => $video['id'],
                    'name' => $key,
                    'path' => $video['path'],
                    'duration' => $video['video']['duration'] / 1000, // convert miliseconds to seconds
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
}
