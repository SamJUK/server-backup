<?php
require_once __DIR__.'/../vendor/autoload.php';

use GuzzleHttp\Client;
use \Firebase\JWT\JWT;

class Authentication {

    public const CONFIG_PATH = __DIR__ . '/../conf/config.json';
    public const AUTHENTICATION_URL = 'https://api.box.com/oauth2/token';

    public $config;

    public function __construct()
    {
        $this->getConfig();
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function getConfig()
    {
        if(!file_exists(self::CONFIG_PATH)) {
            throw new Exception('Cannot find config file');
        }

        $contents = file_get_contents(self::CONFIG_PATH);
        if(!is_string($contents)) {
            throw new RuntimeException('Config Contents is invalid');
        }

        try {
            $this->config = json_decode($contents);
            return $this->config;
        } catch (Exception $e) {
            throw new RuntimeException('Unable to parse the config as json');
        }
    }

    /**
     * @return mixed
     */
    private function getPrivateKey()
    {
        return $this->config->boxAppSettings->appAuth->privateKey;
    }

    /**
     * @return mixed
     */
    private function getPassPhrase()
    {
        return $this->config->boxAppSettings->appAuth->passphrase;
    }

    /**
     * @return bool|resource
     */
    public function getKey()
    {
        return openssl_pkey_get_private($this->getPrivateKey(), $this->getPassPhrase());
    }

    private function getAuthClaims(): array
    {
        return [
            'iss' => $this->config->boxAppSettings->clientID,
            'sub' => $this->config->enterpriseID,
            'box_sub_type' => 'enterprise',
            'aud' => self::AUTHENTICATION_URL,
            'jti' => base64_encode(random_bytes(64)),
            'exp' => time() + 45,
            'kid' => $this->config->boxAppSettings->appAuth->publicKeyID
        ];
    }

    public function getAssertion(): string
    {
        return JWT::encode($this->getAuthClaims(), $this->getKey(), 'RS512');
    }

    public function getAuthParams(): array
    {
        return [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $this->getAssertion(),
            'client_id' => $this->config->boxAppSettings->clientID,
            'client_secret' => $this->config->boxAppSettings->clientSecret
        ];
    }

    /**
     * @return mixed
     */
    public function getAccessToken()
    {
        $client = new Client();
        $response = $client->request('POST', self::AUTHENTICATION_URL, ['form_params' => $this->getAuthParams()]);
        $data = $response->getBody()->getContents();
        return json_decode($data)->access_token;
    }

}