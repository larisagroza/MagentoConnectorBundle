<?php

namespace Pim\Bundle\MagentoConnectorBundle\Normalizer;

/**
 * Registry of Magento normalizers
 *
 * @author Romain Monceau <romain@akeneo.com>
 */
class NormalizerRegistry
{
    /** @staticvar string */
    const ATTRIBUTE_NORMALIZER = 'attribute';

    /** @staticvar string */
    const CATEGORY_NORMALIZER  = 'category';

    /** @staticvar string */
    const FAMILY_NORMALIZER    = 'family';

    /** @staticvar string */
    const OPTION_NORMALIZER    = 'option';

    /** @staticvar string */
    const PRODUCT_NORMALIZER   = 'product';

    /** @staticvar string */
    const PRODUCT_NORMALIZER_16 = 'product16';

    /** @staticvar string */
    const CONFIGURABLE_NORMALIZER = 'configurable';

    /** @var NormalizerInterface[] */
    protected $normalizers = [];

    /**
     * @param string $key
     * @param NormalizerInterface $service
     */
    public function addNormalizer($key, $service)
    {
        $this->normalizers[$key] = $service;
    }

    /**
     * @param string $key
     *
     * @return NormalizerInterface
     * @throws \InvalidItemException
     */
    public function getNormalizer($key)
    {
        if (!isset($this->normalizers[$key])) {
            throw new \InvalidItemException(sprintf('Normalizer "%s" unknown', $key));
        }

        return $this->normalizers[$key];
    }
}
