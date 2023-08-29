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
        'current_progress'
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

    public function progress()
    {
        return $this->hasOne(CourseProgress::class);
    }

    public function getCurrentProgressAttribute()
    {
        if (auth()->check()) {
        return $this->progress()->where('user_id', auth()->user()->id)->first()->progress ?? 0;
        }

        return 0;
    }

    public function getRelatedCoursesAttribute()
    {
        return $this->where('category_id', $this->category_id)->where('id', '!=', $this->id)->get();
    }
}
