<?php

namespace Upstream\Providers\Drive;

use Upstream\AuthenticationBase;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException as GuzzleException;
use Upstream\AuthenticationInterface;


class Authentication extends AuthenticationBase implements AuthenticationInterface
{

    public function __construct()
    {
        parent::__construct();
    }

    protected function setConfigPath(): void
    {
        $this->_config_path = APP_ROOT . '/conf/providers/drive/config.js';
    }

}