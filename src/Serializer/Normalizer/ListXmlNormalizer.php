<?php
declare(strict_types=1);

namespace PBergman\AzureFileBundle\Serializer\Normalizer;

use PBergman\AzureFileBundle\Model\ListResult;
use PBergman\AzureFileBundle\Serializer\Encoder\ListXmlDecoder;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class ListXmlNormalizer implements ContextAwareDenormalizerInterface
{
    private ObjectNormalizer $normalizer;

    public function __construct(ObjectNormalizer $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    public function supportsDenormalization($data, $type, $format = null, array $context = [])
    {
        return $type === ListResult::class && $format === ListXmlDecoder::FORMAT;
    }

    public function denormalize($data, $type, $format = null, array $context = [])
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
}