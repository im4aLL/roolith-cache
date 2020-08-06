<?php
namespace Roolith\Driver;


use Carbon\Carbon;
use Roolith\Interfaces\DriverInterface;
use Roolith\Traits\FileSystem;

class FileDriver extends Driver implements DriverInterface
{
    use FileSystem;

    public $cacheDir;

    public function bootstrap()
    {
        $config = $this->getConfig();
        $this->cacheDir = $config['dir'];

        $this->makeDir($this->cacheDir);
    }

    public function store($key, $value, Carbon $expiration)
    {
        $filename = $this->getFilenameByKey($key);
        $compressData = $this->compress($key, $value, $expiration);

        if (file_put_contents($this->cacheDir.'/'.$filename, $compressData) !== false) {
            return true;
        }

        return false;
    }

    public function get($key)
    {
        $filename = $this->getFilenameByKey($key);

        if (!file_exists($this->cacheDir.'/'.$filename)) {
            return false;
        }

        $compressData = file_get_contents($this->cacheDir.'/'.$filename);
        $data = $this->decompress($compressData);

        if ($this->isExpired($data) || !$this->isValid($data)) {
            return false;
        }

        return $data['value'];
    }

    public function getRaw($key)
    {
        $filename = $this->getFilenameByKey($key);

        if (!file_exists($this->cacheDir.'/'.$filename)) {
            return null;
        }

        $compressData = file_get_contents($this->cacheDir.'/'.$filename);
        return $this->decompress($compressData);
    }

    public function has($key)
    {
        $filename = $this->getFilenameByKey($key);

        return file_exists($this->cacheDir.'/'.$filename);
    }

    public function delete($key)
    {
        if (!$this->has($key)) {
            return false;
        }

        $filename = $this->getFilenameByKey($key);

        return unlink($this->cacheDir.'/'.$filename);
    }

    public function flush()
    {
        return $this->deleteFilesInDir($this->cacheDir);
    }

    public function isValid($value)
    {
        return is_array($value) && isset($value['key']) && isset($value['value']) && isset($value['expiration']);
    }

    public function isExpired($decompressData)
    {
        return Carbon::now()->gte($decompressData['expiration']);
    }

    protected function getCacheFileExtension()
    {
        $config = $this->getConfig();

        return isset($config['ext']) ? $config['ext'] : 'rcache';
    }

    protected function getFilenameByKey($key)
    {
        return $this->sanitizeKeyString($key).'.'.$this->getCacheFileExtension();
    }
}
