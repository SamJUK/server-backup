<?php

namespace Upstream;

use GuzzleHttp\Client as GuzzleClient;

abstract class AuthenticationBase implements AuthenticationInterface
{
    /** @var string */
    protected $_config_path;

    /** @var GuzzleClient  */
    protected $_guzzleClient;

    /** @var  \stdClass */
    protected $_config;

    abstract protected function setConfigPath() : void;

    public function __construct()
    {
        $this->_guzzleClient = new GuzzleClient();
        $this->_config = $this->getConfig();
    }

    public function getConfig() : \stdClass
    {
        if($this->_config instanceof \stdClass) {
            return $this->_config;
        }

        throw new \RuntimeException('Config is not an stdClass');
    }

    protected function setConfig() : void
    {
        $this->_config = $this->parseConfig($this->fetchConfig());
    }

    protected function fetchConfig()
    {
        if(!file_exists($this->_config_path)) {
            throw new \RuntimeException('Cannot find config file');
        }

        $content = file_get_contents($this->_config_path);

        if(!\is_string($content)) {
            throw new \RuntimeException('Config Contents is invalid');
        }

        return $content;
    }

    protected function parseConfig($config) : \stdClass
    {
        $config = json_decode($config);

        if($config === null) {
            throw new \RuntimeException('Error converting Upstream Provider, Box Authentication Config to Json');
        }

        return $config;
    }
}