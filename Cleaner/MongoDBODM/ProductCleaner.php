<?php

namespace Pim\Bundle\MagentoConnectorBundle\Cleaner\MongoDBODM;

use Pim\Bundle\CatalogBundle\Entity\Repository\AttributeRepository;
use Pim\Bundle\CatalogBundle\Manager\ChannelManager;
use Pim\Bundle\CatalogBundle\Manager\ProductManager;
use Pim\Bundle\MagentoConnectorBundle\Cleaner\AbstractProductCleaner;
use Pim\Bundle\MagentoConnectorBundle\Guesser\WebserviceGuesser;
use Pim\Bundle\MagentoConnectorBundle\Webservice\MagentoSoapClientParametersRegistry;

/**
 * Magento product cleaner for MongoDB
 *
 * @author    Romain Monceau <romain@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProductCleaner extends AbstractProductCleaner
{
    /** @var AttributeRepository */
    protected $attributeRepository;

    /**
     * @param WebserviceGuesser                   $webserviceGuesser
     * @param ChannelManager                      $channelManager
     * @param ProductManager                      $productManager
     * @param MagentoSoapClientParametersRegistry $clientParametersRegistry
     * @param AttributeRepository                 $attributeRepository
     */
    public function __construct(
        WebserviceGuesser $webserviceGuesser,
        ChannelManager $channelManager,
        ProductManager $productManager,
        MagentoSoapClientParametersRegistry $clientParametersRegistry,
        AttributeRepository $attributeRepository
    ) {
        parent::__construct($webserviceGuesser, $channelManager, $productManager, $clientParametersRegistry);

        $this->attributeRepository = $attributeRepository;
    }
    /**
     * {@inheritdoc}
     * TODO: Move in specific class
     */
    protected function getExportedProductsSkus()
    {
        $identifierCode = $this->getIdentifierAttributeCode();

        return $this->productManager->getProductRepository()
            ->buildByChannelAndCompleteness($this->getChannelByCode())
            ->select([sprintf("normalizedData.%s", $identifierCode)])
            ->hydrate(false)
            ->getQuery()
            ->execute()
            ->toArray();
    }

    /**
     * {@inheritdoc}
     * TODO: Move in specific class
     */
    protected function getPimProductsSkus()
    {
        $identifierCode = $this->getIdentifierAttributeCode();

        $qb = $this->productManager->getProductRepository()->createQueryBuilder('p');
        /** @var \Doctrine\ODM\MongoDB\Query\Builder $qb */
        return $qb
            ->addAnd($qb->expr()->field('enabled')->equals(true))
            ->select([sprintf("normalizedData.%s", $identifierCode)])
            ->hydrate(false)
            ->getQuery()
            ->execute()
            ->toArray();
    }

    /**
     * {@inheritdoc}
     * TODO: Move in specific class
     */
    protected function getProductsSkus(array $products)
    {
        $identifierCode = $this->getIdentifierAttributeCode();

        $skus = [];
        foreach ($products as $product) {
            $skus[] = $product['normalizedData'][$identifierCode];
        }

        return $skus;
    }

    /**
     * Get the identifier attribute code
     *
     * @return string
     */
    protected function getIdentifierAttributeCode()
    {
        return $this->attributeRepository->getIdentifierCode();
    }
}
