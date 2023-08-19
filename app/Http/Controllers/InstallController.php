<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InstallController extends Controller
{
    private $log;
    public function __construct()
    {
        $this->log = Log::build([
            'driver' => 'single',
            'path' => storage_path('logs/install.log'),
        ]);
    }

    public function index()
    {
        $this->log->info('Install page loaded');

        return view('install.index');
    }

    public function install(Request $request)
    {

    }
}
