<?php
namespace Roolith\Caching\Traits;


use Exception;

trait FileSystem
{
    /**
     * Make directory
     *
     * @param $dir
     * @param int $permission
     * @return $this
     */
    public function makeDir($dir, $permission = 0777)
    {
        if (!file_exists($dir)) {
            mkdir($dir, $permission, true);
        }

        return $this;
    }

    /**
     * Delete directory
     *
     * @param $dirPath
     * @return FileSystem
     */
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

        return $this;
    }

    /**
     * Delete files in a directory
     *
     * @param $dir
     * @return bool
     */
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
