<?php

namespace App\Providers;

use App\Repositories\CategoryInterface;
use App\Repositories\CategoryRepository;
use App\Repositories\Cloud\OneDriveInterface;
use App\Repositories\Cloud\OneDriveRepository;
use App\Repositories\InstallInterface;
use App\Repositories\InstallRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // General
        $this->app->bind(InstallInterface::class, InstallRepository::class);
        // Models
        $this->app->bind(CategoryInterface::class, CategoryRepository::class);
        // OneDrive
        $this->app->bind(OneDriveInterface::class, OneDriveRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Fix "Specified key was too long; max key length"
        \Illuminate\Support\Facades\Schema::defaultStringLength(191);
    }
}
