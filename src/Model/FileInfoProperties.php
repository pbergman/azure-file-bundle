<?php
declare(strict_types=1);

namespace PBergman\AzureFileBundle\Model;

use Symfony\Component\Serializer\Annotation\SerializedName;

class FileInfoProperties
{
    /**
     * @SerializedName("Content-Length")
     */
    private ?int $contentLength = null;

    /**
     * @SerializedName("CreationTime")
     */
    private ?\DateTimeInterface $creationTime;

    /**
     * @SerializedName("LastAccessTime")
     */
    private ?\DateTimeInterface $lastAccessTime;

    /**
     * @SerializedName("LastWriteTime")
     */
    private ?\DateTimeInterface $lastWriteTime;

    /**
     * @SerializedName("ChangeTime")
     */
    private ?\DateTimeInterface $changeTime;

    /**
     * @SerializedName("Last-Modified")
     */
    private ?\DateTimeInterface $lastModified;

    private ?string $etag = null;

    public function getContentLength(): ?int
    {
        return $this->contentLength;
    }

    public function setContentLength(?int $contentLength): self
    {
        $this->contentLength = $contentLength;
        return $this;
    }

    public function getCreationTime(): ?\DateTimeInterface
    {
        return $this->creationTime;
    }

    public function setCreationTime(?\DateTimeInterface $creationTime): self
    {
        $this->creationTime = $creationTime;
        return $this;
    }

    public function getLastAccessTime(): ?\DateTimeInterface
    {
        return $this->lastAccessTime;
    }

    public function setLastAccessTime(?\DateTimeInterface $lastAccessTime): self
    {
        $this->lastAccessTime = $lastAccessTime;
        return $this;
    }

    public function getLastWriteTime(): ?\DateTimeInterface
    {
        return $this->lastWriteTime;
    }

    public function setLastWriteTime(?\DateTimeInterface $lastWriteTime): self
    {
        $this->lastWriteTime = $lastWriteTime;
        return $this;
    }

    public function getChangeTime(): ?\DateTimeInterface
    {
        return $this->changeTime;
    }

    public function setChangeTime(?\DateTimeInterface $changeTime): self
    {
        $this->changeTime = $changeTime;
        return $this;
    }

    public function getLastModified(): ?\DateTimeInterface
    {
        return $this->lastModified;
    }

    public function setLastModified(?\DateTimeInterface $lastModified): self
    {
        $this->lastModified = $lastModified;
        return $this;
    }

    public function getEtag(): ?string
    {
        return $this->etag;
    }

    public function setEtag(?string $etag): self
    {
        $this->etag = $etag;
        return $this;
    }
}