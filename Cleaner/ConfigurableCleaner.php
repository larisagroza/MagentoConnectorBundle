<?php

namespace Pim\Bundle\MagentoConnectorBundle\Cleaner;

use Pim\Bundle\MagentoConnectorBundle\Webservice\MagentoSoapClientParametersRegistry;
use Pim\Bundle\CatalogBundle\Manager\ChannelManager;
use Pim\Bundle\MagentoConnectorBundle\Guesser\WebserviceGuesser;
use Pim\Bundle\CatalogBundle\Manager\ProductManager;
use Pim\Bundle\MagentoConnectorBundle\Normalizer\AbstractNormalizer;
use Pim\Bundle\MagentoConnectorBundle\Manager\GroupManager;

/**
 * Magento configurable cleaner
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ConfigurableCleaner extends AbstractProductCleaner
{
    /** @var GroupManager */
    protected $groupManager;

    /**
     * @param WebserviceGuesser                   $webserviceGuesser
     * @param MagentoSoapClientParametersRegistry $clientParametersRegistry
     * @param ChannelManager                      $channelManager
     * @param ProductManager                      $productManager
     * @param GroupManager                        $groupManager
     */
    public function __construct(
        WebserviceGuesser $webserviceGuesser,
        MagentoSoapClientParametersRegistry $clientParametersRegistry,
        ChannelManager $channelManager,
        ProductManager $productManager,
        GroupManager $groupManager
    ) {
        parent::__construct($webserviceGuesser, $clientParametersRegistry, $channelManager, $productManager);

        $this->groupManager = $groupManager;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        parent::beforeExecute();

        $magentoProducts  = $this->webservice->getProductsStatus();
        $pimConfigurables = $this->getPimConfigurablesSkus();

        foreach ($magentoProducts as $product) {
            if ($product['type'] === AbstractNormalizer::MAGENTO_CONFIGURABLE_PRODUCT_KEY &&
                !in_array($product['sku'], $pimConfigurables)
            ) {
                $this->handleProductNotInPimAnymore($product);
            }
        }
    }

    /**
     * Get all variant group skus
     *
     * @return array
     */
    protected function getPimConfigurablesSkus()
    {
        return $this->groupManager->getRepository()->getVariantGroupSkus();
    }

    /**
     * {@inheritdoc}
     * TODO: Move in specific class
     */
    protected function getExportedProductsSkus()
    {
        return $this->productManager->getProductRepository()
            ->buildByChannelAndCompleteness($this->getChannelByCode())
            ->select('Value.varchar as sku')
            ->andWhere('Attribute.attributeType = :identifier_type')
            ->setParameter(':identifier_type', 'pim_catalog_identifier')
            ->getQuery()
            ->setHydrationMode(Query::HYDRATE_ARRAY)
            ->getResult();
    }

    /**
     * {@inheritdoc}
     * TODO: Move in specific class
     */
    protected function getPimProductsSkus()
    {
        return $this->productManager->getProductRepository()
            ->buildByScope($this->channel)
            ->select('Value.varchar as sku')
            ->andWhere('Attribute.attributeType = :identifier_type')
            ->setParameter(':identifier_type', 'pim_catalog_identifier')
            ->getQuery()
            ->setHydrationMode(Query::HYDRATE_ARRAY)
            ->getResult();
    }

}
