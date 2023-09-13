<?php
declare(strict_types=1);

namespace PBergman\AzureFileBundle\Util;

use Psr\Cache\CacheItemPoolInterface;

class MimeTypeGuesser
{
    private string $file;
    private CacheItemPoolInterface $cache;

    public function __construct(CacheItemPoolInterface $PBergmanAzureFileCache, string $file)
    {
        $this->file  = $file;
        $this->cache = $PBergmanAzureFileCache;
    }

    public function guess(string $path): ?string
    {
        if ("" === $extension = \pathinfo($path, \PATHINFO_EXTENSION)) {
            return null;
        }

        return $this->getExtension($extension);
    }

    private function getExtension(string $extension): ?string
    {
        $item = $this->cache->getItem('PBergman.mime_types');

        if (false === $item->isHit()) {
            $fp   = fopen($this->file, 'r');
            $list = [];

            while (false !== $line = fgets($fp)) {
                if (preg_match('/^(?:\s+)?(?!#)([^\s]+\/[^\s]+)\s+(.+)\n/', $line, $match)) {
                    foreach (preg_split('/\s+/', $match[2]) as $extension) {
                        $list[$extension] = $match[1];
                    }
                }
            }

            $this->cache->save($item->set($list));
        }

        return $item->get()[$extension] ?? null;
    }
}