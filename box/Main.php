<?php
require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../App.php';
require_once __DIR__.'/Authentication.php';

use Authentication as BoxAuth;

class Main {

    public const BACKUPS_FOLDER_NAME = 'backups';
    public const BOX_API_ERROR_CONFLICT = 409;

    public $authentication;
    public $guzzleClient;
    public $backupsFolderId;

    public function __construct()
    {
        $this->authentication = new BoxAuth();
        $this->guzzleClient = new \GuzzleHttp\Client();

        $this->backupsFolderId = $this->getBackupsFolderId();
    }

    public function getBackupsFolderId()
    {
        return $this->getFolderIdFromName(self::BACKUPS_FOLDER_NAME);
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

    public function createFolder(string $name, $parentId = null): string
    {
        if($parentId === null) {
            $parentId = $this->backupsFolderId ?? '0';
        }

        return $this->guzzleClient->post(
            'https://api.box.com/2.0/folders/',
            [
                'headers' => ['Authorization' => "Bearer {$this->authentication->getAccessToken()}"],
                'json' => [
                    'name' => $name,
                    'parent' => ['id' => $parentId]
                ]
            ]
        )->getBody()->getContents();
    }

    public function getFolderIdFromName(string $folder, $parentFolder = null)
    {
        if($parentFolder === null) {
            $parentFolder = $this->backupsFolderId ?? '0';
        }

        $items = $this->getFolderItems($parentFolder);
        $json = json_decode($items);

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
        return $this->guzzleClient->request(
            'GET',
            "https://api.box.com/2.0/folders/$folderId/items",
            [
                'headers' => ['Authorization' => "Bearer {$this->authentication->getAccessToken()}"]
            ]
        )->getBody()->getContents();
    }

    public function getFolders(string $folderId = '0'): string
    {
        return $this->guzzleClient->request(
            'GET',
            "https://api.box.com/2.0/folders/$folderId",
            [
                'headers' => ['Authorization' => "Bearer {$this->authentication->getAccessToken()}"]
            ]
        )->getBody()->getContents();
    }

    public function uploadFile(string $file, string $folderId = '0'): string
    {
        App::log("Uploading File: $file to Folder: $folderId");
        $filePathArray = explode('/',$file);
        $archiveName = array_pop($filePathArray);
        $res = '';

        try {
            $res = $this->guzzleClient->request(
                'POST',
                'https://upload.box.com/api/2.0/files/content',
                [
//                'debug' => true,
                    'headers' => ['Authorization' => "Bearer {$this->authentication->getAccessToken()}"],
                    'multipart' => [
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
                    ]
                ]
            )->getBody()->getContents();
        } catch (GuzzleHttp\Exception\ClientException $e) {
            if($e->getCode() === self::BOX_API_ERROR_CONFLICT) {
                App::log("File already exists on the server: $archiveName");
            } else {
                throw $e;
            }
        }


        return $res;
    }
}