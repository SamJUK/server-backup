<?php

namespace Upstream\Providers;

use GuzzleHttp\Client as GuzzleClient;
use Upstream\UpstreamBase;
use Upstream\Providers\Box\Authentication as BoxAuth;

class Box extends UpstreamBase
{
    /** @var BoxAuth */
    protected $Authentication;

    /** @var GuzzleClient */
    protected $guzzleClient;


    public function __construct()
    {
        $this->Authentication = new BoxAuth();
        $this->guzzleClient = new GuzzleClient();
    }


    public function uploadFile() : bool
    {
        // TODO: Implement method uploadFile()
    }

    public function createFolder() : bool
    {
        // TODO: Implement method createFolder()
    }

    public function getStorageQuota() : int
    {
        // TODO: Implement method getStorageQuota()
    }

    public function getStorageUsage() : int
    {
        // TODO: Implement method getStorageUsage()
    }

}