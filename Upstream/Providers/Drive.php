<?php

namespace Upstream\Providers;

use App;
use Upstream\UpstreamBase;
use Upstream\Exceptions\FolderMissingException;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException as GuzzleException;


class Drive extends UpstreamBase
{
    const BASE_URL = 'https://www.googleapis.com/';

    protected $guzzleClient;
    protected $authentication;

    public function __construct()
    {
        $this->guzzleClient = new GuzzleClient();
        $this->authentication = new \stdClass();
    }

    private function getBaseParameters()
    {
        return [
            'headers' => [
                'Authorization' => "Bearer: {$this->authentication->getAccessToken()}"
            ]
        ];
    }

    private function getUploadParameters($file, $folderid)
    {
        $params = $this->getBaseParameters();



        return $params;
    }

    public function uploadFile(string $file, string $folderId = '0') : bool
    {
        App::log("Uploading File: $file to Folder: $folderId");
        $url = self::BASE_URL.'upload/drive/v3/files?uploadType=multipart';

        try {
            $parameters = $this->getUploadParameters($file, $folderId);
            $this->guzzleClient->request('POST', $url, $parameters);
            return true;

        } catch (GuzzleException $e) {
            $archiveName = basename($file);
            // Custom logging on why it failed
        }

        return true;
    }

    public function createFolder(string $name, $parentId = null): bool
    {

    }

    public function getStorageQuota() : int
    {
        // TODO: Implement method getStorageQuota()
        return 0;
    }

    public function getStorageUsage() : int
    {
        // TODO: Implement method getStorageUsage()
        return 0;
    }

}