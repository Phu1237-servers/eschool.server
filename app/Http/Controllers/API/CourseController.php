<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseVideo;
use App\Repositories\InstallInterface;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    private $installRepository;
    public function __construct(InstallInterface $installRepository)
    {
        $this->installRepository = $installRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $courses = Course::with(['category', 'videos'])->paginate(10);

        return [
            'data' => $courses,
        ];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Course $course)
    {
        $course->increment('views');

        return [
            'data' => $course->load(['category', 'videos'])->append('related_courses'),
        ];
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Course $course)
    {
        // var_dump($course->cloud_path); exit;
        $result = $this->installRepository->getDirectory($course->cloud_path, true);

        // return response()->json($course);

        $videos = $this->installRepository->mergeVideoWithSub($result);
        $videos->each(function ($video) {
            if ($video['type'] === 'other') return;
            $video_data = $video['data'];
            CourseVideo::updateOrcreate([
                'cloud_id' => $video_data['id'],
                'cloud_path' => $video_data['path'],
            ], [
                'thumbnail' => $video_data['videoThumbnail']->first()->getProperties()['large']['url'],
                'download_url' => $video_data['videoUrl'],
                'subtitle_url' => $video_data['subtitleUrl'],
            ]);
        });

        return [
            'data' => $course->load(['category', 'videos'])->append('related_courses'),
        ];
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Course $course)
    {
        //
    }
}
