<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseVideo extends Model
{
    use HasFactory;
    protected $fillable = [
        'name', 'thumbnail', 'duration', 'download_url', 'subtitle_url', 'cloud_id', 'cloud_path', 'course_id'
    ];
    protected $appends = [
        'current_progress'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'duration' => 'double',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function category()
    {
        return $this->belongsToThrough(Category::class, Course::class);
    }

    public function getCurrentProgressAttribute()
    {
        return $this->hasMany(CourseProgress::class)->where('user_id', auth()->id())->first()->progress ?? 0;
    }
}
