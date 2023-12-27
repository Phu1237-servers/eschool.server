<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;
    protected $fillable = [
        'name', 'description', 'thumbnail', 'views', 'cloud_id', 'cloud_path', 'category_id'
    ];
    protected $appends = [
        'current_watching_video',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function videos()
    {
        return $this->hasMany(CourseVideo::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getCurrentWatchingVideoAttribute()
    {
        return $this->hasManyThrough(CourseProgress::class, CourseVideo::class)->where('user_id', auth()->id())->orderBy('updated_at', 'DESC')->first()->course_video_id ?? 0;
    }
}
