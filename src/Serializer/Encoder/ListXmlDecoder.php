<?php
declare(strict_types=1);

namespace PBergman\Bundle\AzureFileBundle\Serializer\Encoder;

use Symfony\Component\Serializer\Encoder\ContextAwareDecoderInterface;
use Symfony\Component\Serializer\Encoder\NormalizationAwareInterface;

class ListXmlDecoder implements ContextAwareDecoderInterface, NormalizationAwareInterface
{
    const FORMAT = 'xml+windows-azure-file';

    public function decode($data, $format, array $context = []): mixed
    {
        $reader = new \XMLReader();
        $reader->xml($data);
        return $this->read($reader);
    }

    private function read(\XMLReader $reader): string|array|null
    {
        $value = null;
        while ($reader->read()) {
            switch ($reader->nodeType) {
                case \XMLReader::CDATA:
                case \XMLReader::TEXT:
                    $value .= $reader->value;
                    break;
                case \XMLReader::ELEMENT:

                    $tag = $reader->name;

                    if ($reader->isEmptyElement) {
                        $value[$tag] = null;
                        break;
                    }

                    $sub = $this->read($reader);

                    if ($tag === 'File' || $tag === 'Directory') {
                        $sub['Type'] = $tag;
                        $value[] = $sub;
                    } else {
                        $value[$tag] = $sub;
                    }

                    if (false !== $reader->moveToFirstAttribute()) {
                        do {
                            $value['@' . $reader->name] = $reader->value;
                        } while ($reader->moveToNextAttribute());
                    }
                    break;
                case \XMLReader::END_ELEMENT:
                    return $value;
            }
        }
        return $value;
    }

    public function supportsDecoding($format, array $context = []): bool
    {
        if (null === $request = ($context['request'] ?? null)) {
            return false;
        }

        if (null === $query = ($request['query'] ?? null)) {
            return false;
        }

        return $format === self::FORMAT && ($query['restype'] ?? '') === 'directory' && ($query['comp'] ?? '') === 'list';
    }
}
