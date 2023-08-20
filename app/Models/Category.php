<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;
    protected $fillable = [
        'name', 'cloud_id', 'cloud_path'
    ];

    public function courses()
    {
        return $this->hasMany(Course::class);
    }

    public function videos()
    {
        return $this->hasManyThrough(CourseVideo::class, Course::class);
    }
}
