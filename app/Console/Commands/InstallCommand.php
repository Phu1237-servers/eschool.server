<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Course;
use App\Models\CourseVideo;
use App\Repositories\Cloud\OneDriveRepository;
use App\Repositories\InstallRepository;
use App\Types\OneDriveType;
use Cache;
use Illuminate\Console\Command;
use Log;

class InstallCommand extends Command
{
    private $installRepository;
    private $oneDriveRepository;
    private $log;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retrieve and install the database data';

    public function __construct(InstallRepository $installRepository, OneDriveRepository $oneDriveRepository)
    {
        parent::__construct();
        $this->installRepository = $installRepository;
        $this->oneDriveRepository = $oneDriveRepository;
        $this->log = Log::build([
            'driver' => 'single',
            'path' => storage_path('logs/install.log'),
        ]);
    }
    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->assign_access_token()) {
            $directory_path = 'drive/root:'.env('ONEDRIVE_ROOT');

            $result = collect();
            $cache = true;
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
            $this->log->info($result);
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
                                'subtitle_url' => $video_data['subtitleUrl'] ?? '',
                                'course_id' => $new_course->id,
                            ]);
                        });
                    }
                });
            });
        }
    }

    public function assign_access_token()
    {
        if (Cache::has(OneDriveType::CACHE_ACCESS_TOKEN)) {
            $code = $this->ask('Access token code is already exists, do you want to revoke it? (y/n)', 'n');
            if ($code === 'y') {
                $this->oneDriveRepository->revokeToken();
                $this->info('Access token code is revoked');
            } else {
                $this->info('Access token code is not revoked');

                return true;
            }
        }
        $url = 'https://login.live.com/oauth20_authorize.srf?client_id='.env('ONEDRIVE_CLIENT_ID').'&scope='.urlencode(env('ONEDRIVE_SCOPE')).'&response_type=code&redirect_uri='.env('ONEDRIVE_REDIRECT_URI_CONSOLE');
        $this->info('Please go to this URL to get the access token code: '.$url);

        $code = $this->ask('Input your access token code');
        if (empty($code)) {
            return false;
        }
        $this->oneDriveRepository->assignToken($code, env('ONEDRIVE_REDIRECT_URI_CONSOLE'), $this->log);

        return true;
    }
}
