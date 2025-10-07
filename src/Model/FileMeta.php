<?php
declare(strict_types=1);

namespace PBergman\Bundle\AzureFileBundle\Model;

use Symfony\Component\Serializer\Attribute\SerializedName;

class FileMeta extends FileInfoProperties
{
    private ?string $type;


    #[SerializedName('Content-Type')]
    private ?string $contentType;

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    public function getContentType(): ?string
    {
        return $this->contentType;
    }

    public function setContentType(?string $contentType): void
    {
        $this->contentType = $contentType;
    }
}