<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseProgress extends Model
{
    use HasFactory;
    protected $fillable = [
        'progress', 'course_video_id', 'user_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'progress' => 'double',
    ];

    public function course()
    {
        return $this->belongsToThrough(Course::class, CourseVideo::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
