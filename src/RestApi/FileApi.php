<?php
declare(strict_types=1);

namespace PBergman\Bundle\AzureFileBundle\RestApi;

use PBergman\Bundle\AzureFileBundle\Exception\ListResponseException;
use PBergman\Bundle\AzureFileBundle\Exception\RemoveResponseException;
use PBergman\Bundle\AzureFileBundle\Model\FileMeta;
use PBergman\Bundle\AzureFileBundle\Model\FileResponse;
use PBergman\Bundle\AzureFileBundle\Model\ListResult;
use PBergman\Bundle\AzureFileBundle\Serializer\Encoder\ListXmlDecoder;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class FileApi
{

    public const LIST_INCLUDE_TIMESTAMPS  = 0x01;
    public const LIST_INCLUDE_ETAGS       = 0x02;
    public const LIST_INCLUDE_ATTR        = 0x04;
    public const LIST_INCLUDE_PERM        = 0x08;
    public const LIST_INCLUDE_ALL         = self::LIST_INCLUDE_TIMESTAMPS|self::LIST_INCLUDE_ETAGS|self::LIST_INCLUDE_ATTR|self::LIST_INCLUDE_PERM;
    private const LIST_INCLUDE_MAP        = [
        self::LIST_INCLUDE_TIMESTAMPS   => 'Timestamps',
        self::LIST_INCLUDE_ETAGS        => 'ETag',
        self::LIST_INCLUDE_ATTR         => 'Attributes',
        self::LIST_INCLUDE_PERM         => 'PermissionKey',
    ];

    private HttpClientInterface $client;
    private SerializerInterface $serializer;

    public function __construct(SerializerInterface $serializer, HttpClientInterface $client)
    {
        $this->client = $client;
        $this->serializer = $serializer;
    }

    /**
     * https://learn.microsoft.com/en-us/rest/api/storageservices/list-directories-and-files
     *
     * @param string|null $path
     * @param string|null $prefix
     * @param int|null $maxResults
     * @param bool $extended
     * @param int $includes
     *
     * @return ListResult
     *
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function list(?string $path = null, ?string $prefix = null, ?int $maxResults = null, bool $extended = false, int $includes = self::LIST_INCLUDE_ALL): ListResult
    {

        $options = [
            'query' => [
                'restype' => 'directory',
                'comp'    => 'list',
            ]
        ];

        if ($includes > 0) {
            $include = '';
            for ($i = 1, $c = max(...array_keys(self::LIST_INCLUDE_MAP)); $i <= $c; $i *= 2 ) {
                if ($i === ($i & $includes)) {
                    $include .= self::LIST_INCLUDE_MAP[$i] . ',';
                }
            }
            if ($include !== '') {
                $options['query']['include'] = substr($include, 0, -1);
            }
        }

        if ($extended) {
            $options['headers']['x-ms-file-extended-info'] = 'true';
        }

        if ($prefix) {
            $options['query']['prefix'] = $prefix;
        }

        if ($maxResults) {
            $options['query']['maxresults'] = $maxResults;
        }

        do {
            $result = $this->fetchListResult($path, $options);

            if (!isset($list)) {
                $list = $result;
            } else {
                $list->addEntry(...$result->getEntries());
            }

            $options['query']['marker'] = $result->getNextMarker();

        } while (null !== $result->getNextMarker());

        return $list;
    }

    private function parseErrorResponse(ResponseInterface $resp, ?string $default = null): ?array
    {
        if (0 !== strpos(($resp->getHeaders(false)['content-type'][0] ?? ''), 'application/xml')) {
            return null;
        }

        $doc = new \DOMDocument();
        $doc->loadXML($resp->getContent(false));

        $err = null;
        $msg = $default ?? 'Failed to fetch response';

        if (($elements = $doc->getElementsByTagName('Code')) && $elements->count() > 0) {
            $err = $elements->item(0)->textContent;
        }
        if (($elements = $doc->getElementsByTagName('Message')) && $elements->count() > 0) {
            $msg = $elements->item(0)->textContent;
        }

        unset($doc);

        return [$msg, $err];
    }

    private function checkResponse(ResponseInterface $resp): ResponseInterface
    {
        if (200 > $resp->getStatusCode()) {

            if (null === ($err = $this->parseErrorResponse($resp, 'Failed to list directories and files'))) {
                throw new ListResponseException('Failed to list directories and files', null, $resp);
            }

            throw new ListResponseException($err[0], $err[1], $resp);
        }

        return $resp;
    }

    private function fetchListResult(?string $path, array $options)
    {
        return $this->serializer->deserialize(
            $this->checkResponse($this->client->request('GET', $path ?? '', $options))->getContent(),
            ListResult::class,
            ListXmlDecoder::FORMAT,
            [
                'request' => $options,
                AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true,
            ]
        );
    }

    /**
     * https://learn.microsoft.com/en-us/rest/api/storageservices/delete-file2
     */
    public function deleteFile(string ...$names): void
    {
        $responses = [];

        foreach ($names as $name) {
            $responses[] = $this->client->request('DELETE', rawurlencode($name), ['user_data' => ['file' => $name]]);
        }

        foreach ($responses as $response) {
            if ($response->getStatusCode() !== 202) {

                $default = sprintf('Failed to remove file %s', $response->getInfo('user_data')['file']);

                if (null === ($err = $this->parseErrorResponse($response, $default))) {
                    throw new RemoveResponseException($default, null, $response);
                }

                throw new RemoveResponseException($err[0], $err[1], $response);
            }
        }
    }

    /**
     * https://learn.microsoft.com/en-us/rest/api/storageservices/get-file
     */
    public function getFile(string $name): ?FileResponse
    {
        return new FileResponse($this->client->request('GET', rawurlencode($name)), $this->client);
    }

    public function getMeta(string $name): FileMeta
    {
        $data = [];
        $info = $this->client->request('HEAD', rawurlencode($name))->getHeaders();
        $map  = [
            'x-ms-type'                  => 'type',
            'content-type'               => 'Content-Type',
            'content-length'             => 'Content-Length',
            'last-modified'              => 'LastModified',
            'x-ms-file-last-write-time'  => 'LastWriteTime',
            'x-ms-file-change-time'      => 'ChangeTime',
            'x-ms-file-creation-time'    => 'CreationTim',
            'etag'                       => 'etag',
        ];

        foreach ($map as $from => $to) {
            $data[$to] = $info[$from][0] ?? null;
        }

        return $this->serializer->denormalize($data, FileMeta::class, null, [AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true]);
    }
}
