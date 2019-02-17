<?php

namespace Upstream;

abstract class UpstreamBase
    implements UpstreamInterface
{
    abstract public function uploadFile() : bool;
    abstract public function createFolder() : bool;
    abstract public function getStorageQuota() : int;
    abstract public function getStorageUsage() : int;


    public function getStorageFree() : int
    {
        return $this->getStorageQuota() - $this->getStorageUsage();
    }

    public function isRoomOnServer(int $bytes) : bool
    {
        return $bytes < $this->getStorageFree();
    }

    public function getStorageUtilization() : int
    {
        return ($this->getStorageUsage() / $this->getStorageQuota()) * 100;
    }
}