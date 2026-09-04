<?php
namespace Roolith\Caching\Traits;


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
     * Only removes files matching the given pattern (default star dot rcache).
     * Skips dot entries, directories, and non-files.
     *
     * @param $dir
     * @param string $pattern
     * @return bool
     */
    public function deleteFilesInDir($dir, $pattern = '*.rcache')
    {
        $result = true;
        $files = glob(rtrim($dir, '/').'/'.$pattern);

        if (!is_array($files)) {
            return true;
        }

        foreach ($files as $file) {
            $basename = basename($file);

            if ($basename === '' || $basename[0] === '.') {
                continue;
            }

            if (!is_file($file)) {
                continue;
            }

            try {
                set_error_handler(function () {
                    return true;
                });

                try {
                    $deleted = unlink($file);
                } finally {
                    restore_error_handler();
                }

                if (!$deleted) {
                    $result = false;
                }
            } catch (\Throwable $e) {
                $result = false;
            }
        }

        return $result;
    }
}
