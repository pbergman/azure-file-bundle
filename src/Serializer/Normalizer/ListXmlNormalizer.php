<?php
declare(strict_types=1);

namespace PBergman\Bundle\AzureFileBundle\Serializer\Normalizer;

use PBergman\Bundle\AzureFileBundle\Model\ListResult;
use PBergman\Bundle\AzureFileBundle\Serializer\Encoder\ListXmlDecoder;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class ListXmlNormalizer implements DenormalizerInterface
{
    private ObjectNormalizer $normalizer;

    public function __construct(ObjectNormalizer $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    public function supportsDenormalization($data, $type, $format = null, array $context = []): bool
    {
        return $type === ListResult::class && $format === ListXmlDecoder::FORMAT && $data !== null;
    }

    public function denormalize($data, $type, $format = null, array $context = []): array
    {
        $object = &$data['EnumerationResults'];

        foreach (preg_grep('/^@/', array_keys($data)) as $attr) {
            $object[$attr] = &$data[$attr];
        }

        if (empty($object['Entries'])) {
            $object['Entries'] = [];
        }

        return $this->normalizer->denormalize($object, $type, $format, $context);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [ListResult::class => true];
    }
}