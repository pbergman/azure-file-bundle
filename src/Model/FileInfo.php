<?php
declare(strict_types=1);

namespace PBergman\Bundle\AzureFileBundle\Model;

use Symfony\Component\Serializer\Attribute\DiscriminatorMap;
use Symfony\Component\Serializer\Attribute\SerializedName;

#[DiscriminatorMap(
    typeProperty: "Type",
    mapping: [
        'File'      => 'PBergman\Bundle\AzureFileBundle\Model\File',
        'Directory' => 'PBergman\Bundle\AzureFileBundle\Model\Directory'
    ]
)]
abstract class FileInfo
{
    #[SerializedName('FileId')]
    private string $id;

    private ?string $name;

    private ?string $attributes;

    private ?string $permissionKey;

    private ?FileInfoProperties $properties;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getAttributes(): ?string
    {
        return $this->attributes;
    }

    public function setAttributes(?string $attributes): self
    {
        $this->attributes = $attributes;
        return $this;
    }

    public function getPermissionKey(): ?string
    {
        return $this->permissionKey;
    }

    public function setPermissionKey(?string $permissionKey): self
    {
        $this->permissionKey = $permissionKey;
        return $this;
    }

    public function getProperties(): ?FileInfoProperties
    {
        return $this->properties;
    }

    public function setProperties(?FileInfoProperties $properties): self
    {
        $this->properties = $properties;
        return $this;
    }
}
