<?php

namespace App\Http\Controllers;

use App\Repositories\Cloud\OneDriveInterface;

class OneDriveController extends Controller
{
    private $oneDriveRepository;
    public function __construct(OneDriveInterface $oneDriveRepository)
    {
        $this->oneDriveRepository = $oneDriveRepository;
    }

    public function index()
    {

    }

    public function revoke()
    {
        $this->oneDriveRepository->revokeToken();
    }
}
