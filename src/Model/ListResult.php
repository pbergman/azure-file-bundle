<?php
declare(strict_types=1);

namespace PBergman\Bundle\AzureFileBundle\Model;

use Symfony\Component\Serializer\Attribute\SerializedName;

class ListResult implements \IteratorAggregate
{
    private ?string $marker = null;

    private ?string $nextMarker = null;

    private ?string $prefix = null;

    private ?int $maxResults = null;

    private ?string $directoryId = null;

    #[SerializedName('@ServiceEndpoint')]
    private ?string $serviceEndpoint = null;

    #[SerializedName('@ShareName')]
    private ?string $shareName = null;

    #[SerializedName('@ShareSnapshot')]
    private ?string $shareSnapshot = null;

    #[SerializedName('@DirectoryPath')]
    private ?string $directoryPath = null;

    /**
     * @var array<FileInfo>|null
     *
     */
    private ?array $entries;

    public function getMarker(): ?string
    {
        return $this->marker;
    }

    public function setMarker(?string $marker): void
    {
        $this->marker = $marker;
    }

    public function getNextMarker(): ?string
    {
        return $this->nextMarker;
    }

    public function setNextMarker(?string $nextMarker): void
    {
        $this->nextMarker = $nextMarker;
    }

    public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    public function setPrefix(?string $prefix): void
    {
        $this->prefix = $prefix;
    }

    public function getMaxResults(): ?int
    {
        return $this->maxResults;
    }

    public function setMaxResults(?int $maxResults): void
    {
        $this->maxResults = $maxResults;
    }

    public function getDirectoryId(): ?string
    {
        return $this->directoryId;
    }

    public function setDirectoryId(?string $directoryId): void
    {
        $this->directoryId = $directoryId;
    }

    public function getServiceEndpoint(): ?string
    {
        return $this->serviceEndpoint;
    }

    public function setServiceEndpoint(?string $serviceEndpoint): void
    {
        $this->serviceEndpoint = $serviceEndpoint;
    }

    public function getShareName(): ?string
    {
        return $this->shareName;
    }

    public function setShareName(?string $shareName): void
    {
        $this->shareName = $shareName;
    }

    public function getShareSnapshot(): ?string
    {
        return $this->shareSnapshot;
    }

    public function setShareSnapshot(?string $shareSnapshot): void
    {
        $this->shareSnapshot = $shareSnapshot;
    }

    public function getDirectoryPath(): ?string
    {
        return $this->directoryPath;
    }

    public function setDirectoryPath(?string $directoryPath): void
    {
        $this->directoryPath = $directoryPath;
    }

    public function getEntries(): array
    {
        return $this->entries;
    }

    public function setEntries(array $entries): void
    {
        $this->entries = [];

        foreach ($entries as $entry) {
            $this->addEntry($entry);
        }
    }

    public function addEntry(FileInfo ...$entry): void
    {
        $this->entries = \array_merge($entry, $this->entries);
    }

    /** @return \Generator|FileInfo[] */
    public function getIterator(): \Generator
    {
        foreach ($this->entries as $entry) {
            yield $entry;
        }
    }
}
