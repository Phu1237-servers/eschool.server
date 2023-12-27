<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CourseProgress;
use Illuminate\Http\Request;

class CourseProgressController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
    public function show(CourseProgress $courseProgress)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     * @param mixed $courses_id
     */
    public function update(Request $request)
    {
        CourseProgress::updateOrCreate(
            [
                'course_video_id' => $request->course_video_id,
                'user_id' => $request->user()->id,
            ],
            [
                'progress' => $request->progress,
            ]
        );

        return response()->json([
            'message' => 'success',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CourseProgress $courseProgress)
    {
        //
    }
}
