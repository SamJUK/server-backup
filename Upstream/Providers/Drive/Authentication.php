<?php

namespace Upstream\Providers\Drive;

use Upstream\AuthenticationBase;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException as GuzzleException;


class Authentication extends AuthenticationBase
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