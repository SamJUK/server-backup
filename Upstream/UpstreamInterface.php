<?php

namespace Upstream;

interface UpstreamInterface
{
    /**
     * Upload a file to the server
     */
    public function uploadFile(string $file) : bool;

    /**
     * Create a folder on the server
     */
    public function createFolder(string $name) : bool;

    /**
     * Check if there is room on the server
     */
    public function isRoomOnServer(int $bytes) : bool;

    /**
     * Get what the storage quota is in bytes
     */
    public function getStorageQuota() : int;

    /**
     * How much of the quota has been used in bytes
     */
    public function getStorageUsage() : int;


    /**
     * Get how many bytes of storage is available for use
     */
    public function getStorageFree() : int;

    /**
     * Percentage of the quota used (0-100)
     */
    public function getStorageUtilization() : int;
}