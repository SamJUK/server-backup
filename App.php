<?php
use Upstream\Providers\Box;

class App {

    public const DEBUG_MODE = true;
    public const LOG_DEBUG = 'debug';

    public const ARCHIVE_TMP_LOCATION = __DIR__.'/var/tmp';
    public const ARCHIVE_STORAGE_LOCATION = __DIR__.'/var/archives';
    public const CONFIG_SITE_LOCATION = __DIR__.'/conf/siteconfig.json';

    public const SITE_NAME_FROM_ARCHIVE_REGEX = '/([\w\.\-]*?)[\.\d\_\-]*.tar/';

    public static function archive_sites(): void
    {
        self::log('Archiving Sites', self::LOG_DEBUG);
        $sites = json_decode(file_get_contents(self::CONFIG_SITE_LOCATION));

        if(!file_exists(self::ARCHIVE_TMP_LOCATION) && !is_dir(self::ARCHIVE_TMP_LOCATION) && !mkdir(self::ARCHIVE_TMP_LOCATION, 0777, true) && !is_dir(self::ARCHIVE_TMP_LOCATION)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', self::ARCHIVE_TMP_LOCATION));
        }

        array_walk($sites, [self::class, 'archive_site']);
    }

    public static function archive_site($site, string $site_name): void
    {
        $startTime = microtime(true);
        $archiveName = self::get_archive_name($site_name, self::ARCHIVE_TMP_LOCATION);
        self::log("Building Archive: $archiveName");
        $files = implode(' ', $site->files);
        // @TODO: Find a better way to handle this. PharData fails on long file names
        shell_exec("tar -cf $archiveName $files");
        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        self::log("Archive Built: $archiveName :: ${totalTime}s", self::LOG_DEBUG);

        $startTime = microtime(true);
        $compressedName = self::compress_archive($archiveName);
        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        self::log("Compression Finished: $compressedName :: ${totalTime}s", self::LOG_DEBUG);
    }

    public static function get_archive_name(string $site_name, string $folder): string
    {
        $date = date('d-m-Y_H-i-s');
        $suffix = 1;
        $archiveNameTemplate = "$folder/${site_name}_$date.tar";
        $archiveName = $archiveNameTemplate;

        while(file_exists($archiveName) || file_exists($archiveName.'.gz')) {
            $archiveName = substr($archiveNameTemplate, 0, -3).$suffix++.'.tar';
        }

        return $archiveName;
    }

    public static function compress_archive(string $archiveName): string
    {
        if(self::brotli_exists()) {
            self::log('Compressing with Brotli', self::LOG_DEBUG);
            $compressedName = "$archiveName.br";
            if(file_exists($compressedName)){
                unlink($compressedName);
            }
            shell_exec("brotli -9 $archiveName");
            unlink($archiveName);
            return $compressedName;
        }

        self::log('Compressing with Gunzip', self::LOG_DEBUG);
        $compressedName = "$archiveName.gz";
        if(file_exists($compressedName)){
            unlink($compressedName);
        }
        shell_exec("gzip $archiveName");
        unlink($archiveName);
        return $compressedName;
    }

    public static function brotli_exists(): bool
    {
        return shell_exec('command -v "brotli"') !== null;
    }

    public static function get_site_from_archive_name(string $archiveName) : string
    {
        $archiveName = basename($archiveName);
        $matches = array();
        preg_match(self::SITE_NAME_FROM_ARCHIVE_REGEX, $archiveName, $matches);
        return $matches[1] ?? '';
    }

    public static function move_archive_to_longterm_local_storage(string $file): void
    {
        if(!file_exists(self::ARCHIVE_STORAGE_LOCATION) && !is_dir(self::ARCHIVE_STORAGE_LOCATION) && !mkdir(self::ARCHIVE_STORAGE_LOCATION, 0777, true) && !is_dir(self::ARCHIVE_STORAGE_LOCATION)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', self::ARCHIVE_STORAGE_LOCATION));
        }

        if(!file_exists($file)) {
            throw new \RuntimeException('Could not find file to move to long term storage');
        }

        $filenameArray = explode('/', $file);
        $filename = array_pop($filenameArray);
        $folder = self::ARCHIVE_STORAGE_LOCATION;
        rename($file, "$folder/$filename");
        self::log("Moved $file to long term storage", self::LOG_DEBUG);
    }

    public static function log(string $message, $type = null): void
    {
        if(!self::DEBUG_MODE && $type === self::LOG_DEBUG) {
            return;
        }

        $date = date('d/m/Y h:i:s');
        $timestamp = "$date :: ";

        echo $timestamp.$message.PHP_EOL;
    }

    public static function upload_archives(): void
    {
        self::log('Uploading Sites');
        $box = new Box();
        $path = self::ARCHIVE_TMP_LOCATION;
        $archives = glob("$path/*.tar*");
        array_walk($archives, array(self::class, 'upload_archive'), $box);
    }

    public static function upload_archive(string $archiveName, string $id, Box $box): void
    {
        $startTime = microtime(true);
        $siteName = self::get_site_from_archive_name($archiveName);
        $folderId = $box->getSiteBackupFolderId($siteName);
        $box->uploadFile("$archiveName", $folderId);
        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        self::log("File Upload Complete: $archiveName :: ${totalTime}s");
        self::move_archive_to_longterm_local_storage($archiveName);
    }

}