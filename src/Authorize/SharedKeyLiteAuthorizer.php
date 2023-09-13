<?php
declare(strict_types=1);

namespace PBergman\AzureFileBundle\Authorize;

// https://learn.microsoft.com/en-us/rest/api/storageservices/authorize-with-shared-key#blob-queue-and-file-services-shared-key-lite-authorization
class SharedKeyLiteAuthorizer implements RequestAuthorizeInterface
{
    private string $account;
    private string $version;
    private string $key;

    public function __construct(string $account, string $key, string $version = '2022-11-02')
    {
        $this->key = $key;
        $this->account = $account;
        $this->version = $version;
    }

    // https://learn.microsoft.com/en-us/rest/api/storageservices/authorize-with-shared-key#shared-key-lite-and-table-service-format-for-2009-09-19-and-later
    private function getCanonicalizedResource(RequestContext $ctx): string
    {
        if ('' !== $comp = $ctx->getQueryPart('comp')) {
            $comp = '?comp=' . $comp;
        }

        return sprintf('/%s/%s%s', $this->account, $ctx->getResourcePath(), $comp);
    }

    // https://learn.microsoft.com/en-us/rest/api/storageservices/authorize-with-shared-key#constructing-the-canonicalized-headers-string
    private function getCanonicalizedHeaders(array $headers): string
    {
        $return = [];

        foreach ($headers as $key => $value) {
            if (0 === stripos((string)$key, 'x-ms-')) {
                $return[] = sprintf('%s:%s', $key, (is_array($value) ? $value[0] : $value));
            }
        }

        sort($return);

        return implode("\n", $return);
    }

    public function getHeader(array $headers, string $name): ?string
    {
        $headers = array_change_key_case($headers);

        if (array_key_exists($name, $headers)) {
            return (is_array($headers[$name]) ? $headers[$name][0] : $headers[$name]);
        }

        return null;
    }

    public function sign(RequestContext $ctx, array &$headers): void
    {
        if (null === $this->getHeader($headers, 'x-ms-version')) {
            $headers['x-ms-version'] = $this->version;
        }

        if (null === $this->getHeader($headers, 'date')) {
            $headers['date'] = (new \DateTime('now', new \DateTimeZone('GMT')))->format(\DATE_RFC7231);
        }

        foreach ($headers as $key => $value) {
            // some sort of edge case where sentry will set header like
            // 0 => "sentry-trace: 37ff958844b042e3b1018ea1c7f7ff0b-a6eca4c0a5f84811-0"
            if (is_numeric($key) && false !== $idx = strpos($value, ':')) {
                $headers[substr($value, 0, $idx)] = ltrim(substr($value, $idx + 1));
                unset($headers[$key]);
            }
        }

        $hash = \hash_init('sha256', \HASH_HMAC, \base64_decode($this->key));

        \hash_update($hash, sprintf("%s\n", $ctx->getMethod()));
        \hash_update($hash, sprintf("%s\n", $this->getHeader($headers, 'content-md5')));
        \hash_update($hash, sprintf("%s\n", $this->getHeader($headers, 'content-type')));
        \hash_update($hash, sprintf("%s\n", $this->getHeader($headers, 'date')));
        \hash_update($hash, sprintf("%s\n", $this->getCanonicalizedHeaders($headers)));
        \hash_update($hash, sprintf("%s", $this->getCanonicalizedResource($ctx)));

        $headers['Authorization'] = sprintf('SharedKeyLite %s:%s', $this->account, \base64_encode(hash_final($hash, true)));
    }

    public function getAccount(): string
    {
        return $this->account;
    }
}