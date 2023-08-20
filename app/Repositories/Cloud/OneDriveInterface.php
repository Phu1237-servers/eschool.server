<?php

namespace App\Repositories\Cloud;

interface OneDriveInterface
{
    public function getDirectoryByPath($path);
    public function getVideoById($id);
    public function getThumbnailById($id);
    public function assignToken($code, $logger = null);
    public function revokeToken();
}
