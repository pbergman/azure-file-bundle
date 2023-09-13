<?php
declare(strict_types=1);

namespace PBergman\AzureFileBundle\Authorize;

interface RequestAuthorizeInterface
{
    public function sign(RequestContext $ctx, array &$headers): void;
    public function getAccount(): string;
}
