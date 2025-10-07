<?php
declare(strict_types=1);

namespace PBergman\Bundle\AzureFileBundle\RestApi;

use PBergman\Bundle\AzureFileBundle\Authorize\RequestAuthorizeInterface;
use PBergman\Bundle\AzureFileBundle\Authorize\RequestContext;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;
use Symfony\Contracts\Service\ResetInterface;

class Client implements HttpClientInterface, ResetInterface, LoggerAwareInterface
{
    private HttpClientInterface $client;
    private RequestAuthorizeInterface $authorizer;

    public function __construct(HttpClientInterface $client, RequestAuthorizeInterface $authorizer)
    {
        $this->client     = $client;
        $this->authorizer = $authorizer;
    }

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        if (false === array_key_exists('headers', $options)) {
            $options['headers'] = [];
        }

        $this->authorizer->sign(new RequestContext($method, $url, $options), $options['headers']);

        return $this->client->request($method, $url, $options);
    }

    public function stream($responses, float $timeout = null): ResponseStreamInterface
    {
        return $this->client->stream($responses, $timeout);
    }

    public function reset()
    {
        if ($this->client instanceof ResetInterface) {
            $this->client->reset();
        }
    }

    public function withOptions(array $options): static
    {
        $clone = clone $this;
        $clone->client = $this->client->withOptions($options);
        return $clone;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        if ($this->client instanceof LoggerAwareInterface) {
            $this->client->setLogger($logger);
        }
    }
}