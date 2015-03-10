<?php

namespace Pim\Bundle\MagentoConnectorBundle\Guesser;

use Pim\Bundle\MagentoConnectorBundle\Normalizer\AbstractNormalizer;
use Pim\Bundle\MagentoConnectorBundle\Normalizer\NormalizerRegistry;
use Pim\Bundle\MagentoConnectorBundle\Webservice\MagentoSoapClientParameters;
use Pim\Bundle\MagentoConnectorBundle\Webservice\MagentoSoapClientFactory;
use Pim\Bundle\MagentoConnectorBundle\Manager\PriceMappingManager;
use Pim\Bundle\MagentoConnectorBundle\Normalizer\ProductNormalizerInterface;

/**
 * A magento guesser to get the proper normalizer.
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class NormalizerGuesser extends AbstractGuesser
{
    /** @var MagentoSoapClientFactory */
    protected $magentoSoapClientFactory;

    /** @var \Pim\Bundle\MagentoConnectorBundle\Normalizer\NormalizerRegistry */
    protected $normalizerRegistry;

    /**
     * Constructor
     * @param MagentoSoapClientFactory $magentoSoapClientFactory
     * @param NormalizerRegistry       $registryNormalizer
     */
    public function __construct(
        MagentoSoapClientFactory $magentoSoapClientFactory,
        NormalizerRegistry $normalizerRegistry
    ) {
        $this->magentoSoapClientFactory = $magentoSoapClientFactory;
        $this->normalizerRegistry       = $normalizerRegistry;
    }

    /**
     * Get the product normalizer corresponding to the given Magento parameters.
     *
     * @param MagentoSoapClientParameters $clientParameters
     * @param boolean                     $enabled
     * @param boolean                     $visibility
     * @param boolean                     $variantMemberVisibility
     * @param string                      $currencyCode
     *
     * @throws NotSupportedVersionException If the magento version is not supported
     *
     * @return AbstractNormalizer
     */
    public function getProductNormalizer(
        MagentoSoapClientParameters $clientParameters,
        $enabled,
        $visibility,
        $variantMemberVisibility,
        $currencyCode
    ) {
        $client         = $this->magentoSoapClientFactory->getMagentoSoapClient($clientParameters);
        $magentoVersion = $this->getMagentoVersion($client);

        switch ($magentoVersion) {
            case AbstractGuesser::MAGENTO_VERSION_1_14:
            case AbstractGuesser::MAGENTO_VERSION_1_13:
            case AbstractGuesser::MAGENTO_VERSION_1_12:
            case AbstractGuesser::MAGENTO_VERSION_1_11:
            case AbstractGuesser::MAGENTO_VERSION_1_9:
            case AbstractGuesser::MAGENTO_VERSION_1_8:
            case AbstractGuesser::MAGENTO_VERSION_1_7:
                $normalizerKey = NormalizerRegistry::PRODUCT_NORMALIZER;
                break;
            case AbstractGuesser::MAGENTO_VERSION_1_6:
                $normalizerKey = NormalizerRegistry::PRODUCT_NORMALIZER_16;
                break;
            default:
                throw new NotSupportedVersionException(AbstractGuesser::MAGENTO_VERSION_NOT_SUPPORTED_MESSAGE);
        }

        $productNormalizer = $productNormalizer = $this->normalizerRegistry->getNormalizer($normalizerKey);
        $productNormalizer
            ->setEnabled($enabled)
            ->setVisibility($visibility)
            ->setVariantMemberVisibility($variantMemberVisibility)
            ->setCurrencyCode($currencyCode)
            ->setMagentoUrl($clientParameters->getSoapUrl());

        return $productNormalizer;
    }

    /**
     * Get the configurable normalizer corresponding to the given Magento parameters.
     *
     * @param MagentoSoapClientParameters $clientParameters
     * @param ProductNormalizerInterface  $productNormalizer
     * @param PriceMappingManager         $priceMappingManager
     * @param boolean                     $visibility
     *
     * @return ConfigurableNormalizer
     *
     * @throws NotSupportedVersionException
     */
    public function getConfigurableNormalizer(
        MagentoSoapClientParameters $clientParameters,
        ProductNormalizerInterface $productNormalizer,
        PriceMappingManager $priceMappingManager,
        $visibility
    ) {
        $client         = $this->magentoSoapClientFactory->getMagentoSoapClient($clientParameters);
        $magentoVersion = $this->getMagentoVersion($client);

        switch ($magentoVersion) {
            case AbstractGuesser::MAGENTO_VERSION_1_14:
            case AbstractGuesser::MAGENTO_VERSION_1_13:
            case AbstractGuesser::MAGENTO_VERSION_1_12:
            case AbstractGuesser::MAGENTO_VERSION_1_11:
            case AbstractGuesser::MAGENTO_VERSION_1_9:
            case AbstractGuesser::MAGENTO_VERSION_1_8:
            case AbstractGuesser::MAGENTO_VERSION_1_7:
            case AbstractGuesser::MAGENTO_VERSION_1_6:
                $configurableNormalizer = $this
                    ->normalizerRegistry
                    ->getNormalizer(NormalizerRegistry::CONFIGURABLE_NORMALIZER);
                $configurableNormalizer
                    ->setPriceMappingManager($priceMappingManager)
                    ->setVisibility($visibility);

                return $configurableNormalizer;
            default:
                throw new NotSupportedVersionException(AbstractGuesser::MAGENTO_VERSION_NOT_SUPPORTED_MESSAGE);
        }
    }

    /**
     * Get the Category normalizer corresponding to the given Magento parameters.
     *
     * @param MagentoSoapClientParameters $clientParameters
     *
     * @return AbstractNormalizer
     */
    public function getCategoryNormalizer(MagentoSoapClientParameters $clientParameters)
    {
        $client         = $this->magentoSoapClientFactory->getMagentoSoapClient($clientParameters);
        $magentoVersion = $this->getMagentoVersion($client);

        switch ($magentoVersion) {
            case AbstractGuesser::MAGENTO_VERSION_1_14:
            case AbstractGuesser::MAGENTO_VERSION_1_13:
            case AbstractGuesser::MAGENTO_VERSION_1_12:
            case AbstractGuesser::MAGENTO_VERSION_1_11:
            case AbstractGuesser::MAGENTO_VERSION_1_9:
            case AbstractGuesser::MAGENTO_VERSION_1_8:
            case AbstractGuesser::MAGENTO_VERSION_1_7:
            case AbstractGuesser::MAGENTO_VERSION_1_6:
                return $this->normalizerRegistry->getNormalizer(NormalizerRegistry::CATEGORY_NORMALIZER);
            default:
                throw new NotSupportedVersionException(AbstractGuesser::MAGENTO_VERSION_NOT_SUPPORTED_MESSAGE);
        }
    }

    /**
     * Get the option normalizer corresponding to the given Magento parameters.
     *
     * @param MagentoSoapClientParameters $clientParameters
     *
     * @return AbstractNormalizer
     */
    public function getOptionNormalizer(MagentoSoapClientParameters $clientParameters)
    {
        $client         = $this->magentoSoapClientFactory->getMagentoSoapClient($clientParameters);
        $magentoVersion = $this->getMagentoVersion($client);

        switch ($magentoVersion) {
            case AbstractGuesser::MAGENTO_VERSION_1_14:
            case AbstractGuesser::MAGENTO_VERSION_1_13:
            case AbstractGuesser::MAGENTO_VERSION_1_12:
            case AbstractGuesser::MAGENTO_VERSION_1_11:
            case AbstractGuesser::MAGENTO_VERSION_1_9:
            case AbstractGuesser::MAGENTO_VERSION_1_8:
            case AbstractGuesser::MAGENTO_VERSION_1_7:
            case AbstractGuesser::MAGENTO_VERSION_1_6:
                return $this->normalizerRegistry->getNormalizer(NormalizerRegistry::OPTION_NORMALIZER);
            default:
                throw new NotSupportedVersionException(AbstractGuesser::MAGENTO_VERSION_NOT_SUPPORTED_MESSAGE);
        }
    }

    /**
     * Get the attribute normalizer corresponding to the given Magento parameters.
     *
     * @param MagentoSoapClientParameters $clientParameters
     *
     * @return AbstractNormalizer
     */
    public function getAttributeNormalizer(MagentoSoapClientParameters $clientParameters)
    {
        $client         = $this->magentoSoapClientFactory->getMagentoSoapClient($clientParameters);
        $magentoVersion = $this->getMagentoVersion($client);

        switch ($magentoVersion) {
            case AbstractGuesser::MAGENTO_VERSION_1_14:
            case AbstractGuesser::MAGENTO_VERSION_1_13:
            case AbstractGuesser::MAGENTO_VERSION_1_12:
            case AbstractGuesser::MAGENTO_VERSION_1_11:
            case AbstractGuesser::MAGENTO_VERSION_1_9:
            case AbstractGuesser::MAGENTO_VERSION_1_8:
            case AbstractGuesser::MAGENTO_VERSION_1_7:
            case AbstractGuesser::MAGENTO_VERSION_1_6:
                return $this->normalizerRegistry->getNormalizer(NormalizerRegistry::ATTRIBUTE_NORMALIZER);
            default:
                throw new NotSupportedVersionException(AbstractGuesser::MAGENTO_VERSION_NOT_SUPPORTED_MESSAGE);
        }
    }

    /**
     * Get the family normalizer corresponding to the given Magento parameters.
     *
     * @param MagentoSoapClientParameters $clientParameters
     *
     * @throws NotSupportedVersionException
     *
     * @return FamilyNormalizer
     */
    public function getFamilyNormalizer(MagentoSoapClientParameters $clientParameters)
    {
        $client         = $this->magentoSoapClientFactory->getMagentoSoapClient($clientParameters);
        $magentoVersion = $this->getMagentoVersion($client);

        switch ($magentoVersion) {
            case AbstractGuesser::MAGENTO_VERSION_1_14:
            case AbstractGuesser::MAGENTO_VERSION_1_13:
            case AbstractGuesser::MAGENTO_VERSION_1_12:
            case AbstractGuesser::MAGENTO_VERSION_1_11:
            case AbstractGuesser::MAGENTO_VERSION_1_9:
            case AbstractGuesser::MAGENTO_VERSION_1_8:
            case AbstractGuesser::MAGENTO_VERSION_1_7:
            case AbstractGuesser::MAGENTO_VERSION_1_6:
                return $this->normalizerRegistry->getNormalizer(NormalizerRegistry::FAMILY_NORMALIZER);
            default:
                throw new NotSupportedVersionException(AbstractGuesser::MAGENTO_VERSION_NOT_SUPPORTED_MESSAGE);
        }
    }
}
