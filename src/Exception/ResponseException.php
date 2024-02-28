<?php
declare(strict_types=1);

namespace PBergman\Bundle\AzureFileBundle\Exception;

use Symfony\Contracts\HttpClient\ResponseInterface;

class ResponseException extends \RuntimeException implements ExceptionInterface
{
    private ?ResponseInterface $response;
    private ?string $errorCode;

    public function __construct(string $message, ?string $code = null, ?ResponseInterface $response = null,\Throwable $previous = null)
    {
        parent::__construct($message, $response->getInfo('http_code'), $previous);
        $this->errorCode = $code;
        $this->response  = $response;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }
}