<?php

namespace Pim\Bundle\MagentoConnectorBundle\Cleaner\ORM;

use Doctrine\ORM\Query;

/**
 * Magento product cleaner for ORM
 *
 * @author    Romain Monceau <romain@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProductCleaner extends AbstractProductCleaner
{
    /**
     * {@inheritdoc}
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

    /**
     * {@inheritdoc}
     */
    protected function getProductsSkus(array $products)
    {
        $productsSkus = [];
        foreach ($products as $product) {
            $productsSkus[] = (string) reset($product);
        };

        return $productsSkus;
    }
}
