<?php
namespace Roolith\Traits;


use Exception;

trait FileSystem
{
    public function makeDir($dir, $permission = 0777)
    {
        if (!file_exists($dir)) {
            mkdir($dir, $permission, true);
        }

        return $this;
    }

    public function deleteDir($dirPath)
    {
        if (substr($dirPath, strlen($dirPath) - 1, 1) !== '/') {
            $dirPath .= '/';
        }

        $files = glob($dirPath . '*', GLOB_MARK);

        foreach ($files as $file) {
            if (is_dir($file)) {
                $this->deleteDir($file);
            } else {
                unlink($file);
            }
        }

        rmdir($dirPath);
    }

    public function deleteFilesInDir($dir)
    {
        $result = true;
        $files = glob($dir.'/*');

        foreach ($files as $file) {
            try {
                unlink($file);
            } catch (Exception $e) {
                $result = false;
            }
        }

        return $result;
    }
}
