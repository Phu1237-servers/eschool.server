<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Course;
use App\Models\CourseVideo;
use App\Repositories\Cloud\OneDriveInterface;
use App\Repositories\InstallRepository;
use App\Types\OneDriveType;
use Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InstallController extends Controller
{
    private $installRepository;
    private $oneDriveRepository;
    private $log;
    public function __construct(InstallRepository $installRepository, OneDriveInterface $oneDriveRepository)
    {
        $this->installRepository = $installRepository;
        $this->oneDriveRepository = $oneDriveRepository;
        $this->log = Log::build([
            'driver' => 'single',
            'path' => storage_path('logs/install.log'),
        ]);
    }

    public function index()
    {
        $this->log->info('Install page loaded');

        return view('install.index');
    }

    public function install(Request $request)
    {
        $directory_path = 'drive/root:'.env('ONEDRIVE_ROOT');

        $result = collect();
        $cache = false;
        if (!$cache || !Cache::has(OneDriveType::CACHE_DIRECTORIES)) {
            $result = $this->installRepository->getDirectory($directory_path, 1);
            if ($cache) {
                Cache::rememberForever(OneDriveType::CACHE_DIRECTORIES, function () use ($result) {
                    return $result;
                });
            }
        } else {
            $result = Cache::get(OneDriveType::CACHE_DIRECTORIES);
        }
        $result->each(function ($category) {
            $new_category = Category::updateOrcreate([
                'cloud_id' => $category['id'],
                'cloud_path' => $category['path'],
            ], [
                'name' => $category['name'],
            ]);
            $category['children']->each(function ($course) use ($new_category) {
                $new_course = Course::updateOrcreate([
                    'cloud_id' => $course['id'],
                    'cloud_path' => $course['path'],
                ], [
                    'name' => $course['name'],
                    'description' => '',
                    'thumbnail' => '',
                    'category_id' => $new_category->id,
                ]);
                if (isset($course['children'])) {
                    $videos = $this->installRepository->mergeVideoWithSub($course['children']);
                    $videos->each(function ($video) use ($new_course) {
                        if ($video['type'] === 'other') return;
                        $video_data = $video['data'];
                        CourseVideo::updateOrcreate([
                            'cloud_id' => $video_data['id'],
                            'cloud_path' => $video_data['path'],
                        ], [
                            'name' => $video_data['name'],
                            'thumbnail' => $video_data['videoThumbnail']->first()->getProperties()['large']['url'],
                            'duration' => $video_data['duration'],
                            'download_url' => $video_data['videoUrl'],
                            'subtitle_url' => $video_data['subtitleUrl'],
                            'course_id' => $new_course->id,
                        ]);
                    });
                }
            });
        });

        return 'Install DONE';
    }

    public function access_token_response(Request $request)
    {
        $code = $request->get('code');
        $this->oneDriveRepository->assignToken($code, $this->log);

        return redirect()->route('install.submit');
    }
}
