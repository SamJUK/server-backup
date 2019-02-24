<?php

namespace Upstream\Providers;

use App;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException as GuzzleException;
use Upstream\UpstreamBase;
use Upstream\Providers\Box\Authentication as BoxAuth;
use Upstream\Exceptions\FolderMissingException;

class Box extends UpstreamBase
{
    // TODO: Pull from config file
    private const CONTINUE_ON_UPLOAD_ERROR = false;

    // TODO: Use error_code rather than HTTP status
    private const BOX_API_ERROR_CONFLICT = 409;

    private const BOX_ROOT_FOLDER = '0';
    private const BACKUPS_FOLDER_NAME = 'backups';

    public const BASE_API_URL = 'https://api.box.com/2.0/';
    public const BASE_UPLOAD_URL = 'https://upload.box.com/api/2.0/';

    protected $authentication;
    protected $guzzleClient;
    protected $backupsFolderId;

    public function __construct()
    {
        $this->authentication = new BoxAuth();
        $this->guzzleClient = new GuzzleClient();

        $this->backupsFolderId = $this->getBackupsFolderId();
    }

    private function getBackupsFolderId()
    {
        return $this->getFolderIdFromName(self::BACKUPS_FOLDER_NAME);
    }

    private function getFolderIdFromName(string $folder, $parentFolder = null)
    {
        if($parentFolder === null) {
            $parentFolder = $this->backupsFolderId ?? self::BOX_ROOT_FOLDER;
        }

        $items = $this->getFolderItems($parentFolder);
        $json = json_decode($items);

        if($json === null) {
            throw new \RuntimeException('Box Api: The response from getFolderItems is not valid JSON');
        }

        if($json->total_count <= 0) {
            throw new FolderMissingException('No children in folder');
        }

        foreach($json->entries as $entry) {
            if(strtolower($entry->name) === $folder) {
                return $entry->id;
            }
        }

        throw new FolderMissingException('Could not find the folder');
    }

    public function getFolderItems(string $folderId = '0'): string
    {
        $baseUrl = self::BASE_API_URL;
        // TODO: Handle expired Auth tokens
        try {
            return $this->guzzleClient->request(
                'GET',
                "${baseUrl}folders/$folderId/items",
                $this->getBaseParameters()
            )->getBody()->getContents();
        }catch (GuzzleException $e) {
            // @TODO Revist this, for some we cant get the response body
            $res = (string) $e->getResponse()->getBody();
        }
    }

    public function getFolders(string $folderId = '0'): string
    {
        $baseUrl = self::BASE_API_URL;
        // TODO: Handle expired Auth Tokens
        return $this->guzzleClient->request(
            'GET',
            "${$baseUrl}folders/$folderId",
            $this->getBaseParameters()
        )->getBody()->getContents();
    }

    public function getSiteBackupFolderId(string $folder)
    {
        try {
            return $this->getFolderIdFromName($folder, $this->backupsFolderId);
        } catch (FolderMissingException $e) {
            $res = $this->createFolder($folder, $this->backupsFolderId);
            $resJson = json_decode($res);
            return $resJson->id;
        }
    }

    // TODO: Tidy up this function
    public function uploadFile(string $file, string $folderId = '0') : bool
    {
        App::log("Uploading File: $file to Folder: $folderId");
        $baseUrl = self::BASE_UPLOAD_URL;

        try {
            $parameters = $this->getUploadParameters($file, $folderId);

            // Handle Expired Auth Tokens
            return $this->guzzleClient->request(
                'POST',
                "${baseUrl}files/content",
                $parameters
            )->getBody()->getContents();
        } catch (GuzzleException $e) {
            $archiveName = basename($file);
            // TODO: Change to use error_code attribute
            if($e->getCode() === self::BOX_API_ERROR_CONFLICT) {
                App::log("File already exists on the server: $archiveName");
            } elseif(self::CONTINUE_ON_UPLOAD_ERROR) {
                App::log("Error uploading file: $archiveName");
                App::log($e->getMessage());
            } else {
                throw $e;
            }
        }

        return '';
    }

    /**
     * @throws \RuntimeException
     */
    private function getUploadParameters(string $file, string $folderId) : array
    {
        $archiveName = basename($file);
        $params = $this->getBaseParameters();
        $params['multipart'] = [
            [
                'name' => $archiveName,
                'contents' => fopen($file, 'rb')
            ],
            [
                'name' => 'name',
                'contents' => $archiveName
            ],
            [
                'name' => 'parent_id',
                'contents' => $folderId
            ]
        ];

        if(App::DEBUG_MODE) {
            $parameters['debug'] = true;
        }

        return $params;
    }

    public function getBaseParameters()
    {
        return [
            'headers' => [
                'Authorization' => "Bearer {$this->authentication->getAccessToken()}"
            ]
        ];
    }

    public function createFolder(string $name, $parentId = null) : bool
    {
        if($parentId === null) {
            $parentId = $this->backupsRootId ?? self::BOX_ROOT_FOLDER;
        }

        $baseUrl = self::BASE_API_URL;
        $parameters = $this->getBaseParameters();
        $parameters['json'] = [
            'name' => $name,
            'parent' => ['id' => $parentId]
        ];

        // TODO: Handle expired tokens and other errors
        return $this->guzzleClient->post(
            "${baseUrl}2.0/folders/",
            $parameters
        )->getBody()->getContents();
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