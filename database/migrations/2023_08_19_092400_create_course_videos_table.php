<?php

use App\Models\Course;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('course_videos', function (Blueprint $table) {
            $table->id();
            $table->text('name');
            $table->text('thumbnail');
            $table->string('duration');
            $table->text('download_url');
            $table->text('subtitle_url')->nullable();
            $table->string('cloud_id')->unique()->nullable();
            $table->text('cloud_path')->nullable();
            $table->foreignIdFor(Course::class)->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_videos');
    }
};
