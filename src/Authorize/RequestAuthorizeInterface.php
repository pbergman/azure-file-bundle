<?php
declare(strict_types=1);

namespace PBergman\Bundle\AzureFileBundle\Authorize;

interface RequestAuthorizeInterface
{
    public function sign(RequestContext $ctx, array &$headers): void;
    public function getAccount(): string;
}
