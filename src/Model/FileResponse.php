<?php
declare(strict_types=1);

namespace PBergman\Bundle\AzureFileBundle\Model;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class FileResponse implements \IteratorAggregate
{
    private ResponseInterface $response;
    private HttpClientInterface $client;

    public function __construct(ResponseInterface $response, HttpClientInterface $client)
    {
        $this->response = $response;
        $this->client = $client;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    public function exists(): bool
    {
        return $this->getStatus() === 200;
    }

    public function getStatus()
    {
        return $this->response->getInfo('http_code');
    }

    public function __toString(): string
    {
        return $this->response->getContent(false);
    }

    public function cancel(): void
    {
        $this->response->cancel();
    }

    public function getIterator()
    {
        foreach ($this->client->stream($this->response) as $chunk) {
            yield $chunk->getContent();
        }
    }
}