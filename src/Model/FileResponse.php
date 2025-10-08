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

    public function getStatus(): int
    {
        try {
            return $this->response->getStatusCode();
        } catch (\Throwable $exception) {
            return -1;
        }
    }

    public function __toString(): string
    {
        return $this->response->getContent(false);
    }

    public function cancel(): void
    {
        $this->response->cancel();
    }

    public function getIterator(): \Traversable
    {
        foreach ($this->client->stream($this->response) as $chunk) {
            yield $chunk->getContent();
        }
    }
}
