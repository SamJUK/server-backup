<?php

namespace Upstream\Providers;

use App;
use Upstream\UpstreamBase;
use Upstream\Exceptions\FolderMissingException;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException as GuzzleException;


class Drive extends UpstreamBase
{

    protected $guzzleClient;
    protected $authentication;

    public function __construct()
    {
        $this->guzzleClient = new GuzzleClient();
        $this->authentication = new \stdClass();
    }

}