<?php

namespace Upstream\Providers\Box;

use Upstream\AuthenticationInterface;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException as GuzzleException;
use \Firebase\JWT\JWT;
use Upstream\Providers\Box;

class Authentication implements AuthenticationInterface
{
    private const BOX_API_ERROR_UNAUTHORISED = 401;

    private const CONFIG_PATH = APP_ROOT . '/conf/providers/box/config.json';
    private const AUTHENTICATION_URL = 'https://api.box.com/oauth2/token';

    private $_guzzleClient;

    private $_config;
    private $_accesstoken;

    public function __construct()
    {
        $this->_guzzleClient = new GuzzleClient();
        $this->getConfig();
        $this->fetchNewAccessToken();
    }

    /**
     * @throws \RuntimeException
     */
    private function getConfig() : \stdClass
    {
        if($this->_config instanceof \stdClass) {
            return $this->_config;
        }

        if(!file_exists(self::CONFIG_PATH)) {
            throw new \RuntimeException('Cannot find config file');
        }

        $contents = file_get_contents(self::CONFIG_PATH);
        if(!\is_string($contents)) {
            throw new \RuntimeException('Config Contents is invalid');
        }

        $this->_config = json_decode($contents);

        if($this->_config === null) {
            throw new \RuntimeException('Error converting Upstream Provider, Box Authentication Config to Json');
        }

        return $this->_config;
    }

    /**
     * @throws \RuntimeException
     */
    private function getPrivateKey() : string
    {
        $res = @$this->_config->boxAppSettings->appAuth->privateKey;

        if($res === null) {
            throw new \RuntimeException('Unable to extract privateKey from the Box Auth config file.');
        }

        return $res;
    }

    /**
     * @throws \RuntimeException
     */
    private function getPassPhrase() : string
    {
        $res = @$this->_config->boxAppSettings->appAuth->passphrase;

        if($res === null) {
            throw new \RuntimeException('Unable to extract passphrase from the Box Auth config file.');
        }

        return $res;
    }

    /**
     * @throws \RuntimeException
     */
    private function getKey()
    {
        $key = openssl_pkey_get_private($this->getPrivateKey(), $this->getPassPhrase());

        if($key === false) {
            throw new \RuntimeException('Unable to get the Box Authentication private key');
        }

        return openssl_pkey_get_private($this->getPrivateKey(), $this->getPassPhrase());
    }

    private function getAuthClaims(): array
    {
        return [
            'iss' => $this->_config->boxAppSettings->clientID,
            'sub' => $this->_config->enterpriseID,
            'box_sub_type' => 'enterprise',
            'aud' => self::AUTHENTICATION_URL,
            'jti' => base64_encode(random_bytes(64)),
            'exp' => time() + 45,
            'kid' => $this->_config->boxAppSettings->appAuth->publicKeyID
        ];
    }

    private function createJWTAssertion(): string
    {
        return JWT::encode($this->getAuthClaims(), $this->getKey(), 'RS512');
    }

    private function getAuthParams(): array
    {
        return [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $this->createJWTAssertion(),
            'client_id' => $this->_config->boxAppSettings->clientID,
            'client_secret' => $this->_config->boxAppSettings->clientSecret
        ];
    }


    /** @inheritdoc */
    public function isAccessTokenValid() : bool
    {
        $base_url = Box::BASE_UPLOAD_URL;
        try {
            $this->_guzzleClient->request(
                'GET',
                "${$base_url}users/me",
                ['headers' => ['Authorization' => "Bearer {$this->getAccessToken()}"]]
            )->getBody()->getContents();
            return true;
        } catch (GuzzleException $e) {
            // TODO: Change to use error_code attribute
            if($e->getCode() === self::BOX_API_ERROR_UNAUTHORISED) {
                return false;
            }
            throw $e;
        }
    }

    /**
     * @inheritdoc
     * @throws \RuntimeException
     */
    public function getAccessToken($fetchOnFail = false) : string
    {
        if(\is_string($this->_accesstoken)) {
            return $this->_accesstoken;
        }

        if($fetchOnFail) {
            return $this->fetchNewAccessToken();
        }

        throw new \RuntimeException('Unable to get the Box Auth Access Token');
    }

    /**
     * @inheritdoc
     * @throws \RuntimeException
     */
    public function fetchNewAccessToken() : string
    {
        $response = $this->_guzzleClient->request('POST', self::AUTHENTICATION_URL, ['form_params' => $this->getAuthParams()]);
        $response = $response->getBody()->getContents();
        $json = json_decode($response);

        if(($json instanceof \stdClass) === false) {
            throw new \RuntimeException('Issue with Box Auth converting access token response to JSON');
        }

        if(!property_exists($json, 'access_token')) {
            throw new \RuntimeException('Access token does not exist in the Box Auth response json');
        }

        $this->_accesstoken = $json->access_token;
        return $this->getAccessToken();
    }
}