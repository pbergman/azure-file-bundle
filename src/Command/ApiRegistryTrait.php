<?php
declare(strict_types=1);

namespace PBergman\Bundle\AzureFileBundle\Command;

use PBergman\Bundle\AzureFileBundle\RestApi\FileApi;

trait ApiRegistryTrait
{
    private array $registry = [];

    public function register($name, FileApi $api): void
    {
        $this->registry[$name] = $api;
    }

    private function getApiForDirectory(string $directory): FileApi
    {
        /** @var FileApi $api  */
        if (null === $api = ($this->registry[$directory] ?? null)) {
            throw new \RuntimeException(sprintf('No API defined for directory "%s", available directories: "%s"', $directory, implode('", "', $this->getDirectories())));
        }

        return $api;
    }

    public function getDirectories(): array
    {
        return \array_keys($this->registry);
    }
}

