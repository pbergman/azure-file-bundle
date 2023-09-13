<?php
declare(strict_types=1);

namespace PBergman\AzureFileBundle\Authorize;

class RequestContext
{
    private string $scheme;
    private string $host;
    private string $path;
    private string $method;
    private array $query = [];

    public function __construct(string $method, string $url, array $options = [])
    {
        $info         = \parse_url($url);
        $this->scheme = $info['scheme'] ?? $options['base_uri']['scheme'] ?? '';
        $this->host   = $info['host'] ?? $options['base_uri']['host'] ?? '';
        $this->path   = $info['path'] ?? $options['base_uri']['path'] ?? '/';
        $this->method = $method;

        if (!isset($options['query'])) {
            \parse_str($info['query'], $query);
            $this->query = $query;
        } else {
            $this->query = $options['query'];
        }
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getResourcePath(): string
    {
        // check if is URI encoded
        if (preg_match("/^[a-z0-9%+-_]*$/i", $this->path)) {
            return ('/' === $this->path[0]) ? substr($this->path, 1) : $this->path;
        }

        $parts = [];

        foreach (explode('/', $this->path) as $part) {
            $parts[] = \rawurlencode(\rawurldecode($part));
        }

        return implode('/', \array_filter($parts));
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getMethod(): string
    {
        return strtoupper($this->method);
    }

    public function getQuery(): array
    {
        return $this->query;
    }

    public function getQueryPart($name): string
    {
        if (isset($this->query[$name])) {
            return ((array)$this->query[$name])[0];
        }

        return '';
    }
}