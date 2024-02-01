<?php
declare(strict_types=1);

namespace PBergman\Bundle\AzureFileBundle\Util;

use Psr\Cache\CacheItemPoolInterface;

class MimeTypeGuesser
{
    private string $file;
    private ?CacheItemPoolInterface $cache;

    public function __construct(string $file, ?CacheItemPoolInterface $azureFileCache = null)
    {
        $this->file  = $file;
        $this->cache = $azureFileCache;
    }

    public function guess(string $path, bool $force = false): ?string
    {
        if ("" === $extension = \pathinfo($path, \PATHINFO_EXTENSION)) {
            return null;
        }

        return $this->getExtension($extension, $force);
    }

    private function getExtension(string $extension, bool $force = false): ?string
    {
        return ((null !== $this->cache && false === $force) ? $this->readExtensionsFromCache() : $this->readExtensionsFromFile())[$extension] ?? null;
    }

    public function readExtensionsFromFile(): array
    {
        if (false === $fp = fopen($this->file, 'r')) {
            throw new \RuntimeException('Could not open "' . $this->file . '".')   ;
        }

        $list = [];

        while (false !== $line = fgets($fp)) {
            if (preg_match('/^(?:\s+)?(?!#)(\S+\/\S+)\s+(.+)\n/', $line, $match)) {
                foreach (preg_split('/\s+/', $match[2]) as $extension) {
                    $list[$extension] = $match[1];
                }
            }
        }

        \fclose($fp);
        \ksort($list);

        return $list;
    }

    private function readExtensionsFromCache(): array
    {
        $item = $this->cache->getItem('pbergman.mime_types');

        if (false === $item->isHit()) {
            $this->cache->save($item->set($this->readExtensionsFromFile()));
        }

        return $item->get() ?? [];
    }
}