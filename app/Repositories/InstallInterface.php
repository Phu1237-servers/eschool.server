<?php

namespace App\Repositories;

interface InstallInterface
{
    public function getDirectory($path, $recusion = false);
    public function mergeVideoWithSub($data);
}
