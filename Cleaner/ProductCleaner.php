<?php

namespace Pim\Bundle\MagentoConnectorBundle\Cleaner;

use Akeneo\Bundle\BatchBundle\Item\InvalidItemException;
use Doctrine\ORM\Query;
use Pim\Bundle\CatalogBundle\Manager\ChannelManager;
use Pim\Bundle\CatalogBundle\Manager\ProductManager;
use Pim\Bundle\MagentoConnectorBundle\Guesser\WebserviceGuesser;
use Pim\Bundle\MagentoConnectorBundle\Validator\Constraints\HasValidCredentials;
use Pim\Bundle\MagentoConnectorBundle\Webservice\MagentoSoapClientParametersRegistry;
use Pim\Bundle\MagentoConnectorBundle\Normalizer\AbstractNormalizer;
use Pim\Bundle\MagentoConnectorBundle\Webservice\SoapCallException;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Magento product cleaner
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 * @HasValidCredentials()
 */
class ProductCleaner extends Cleaner
{
    /**
     * @Assert\NotBlank(groups={"Execution"})
     */
    protected $channel;

    /**
     * get channel
     *
     * @return string channel
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * Set channel
     *
     * @param string $channel channel
     *
     * @return ProductCleaner
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;

        return $this;
    }

    /** @var string */
    protected $notCompleteAnymoreAction;

    /**
     * get notCompleteAnymoreAction
     *
     * @return string notCompleteAnymoreAction
     */
    public function getNotCompleteAnymoreAction()
    {
        return $this->notCompleteAnymoreAction;
    }

    /**
     * Set notCompleteAnymoreAction
     *
     * @param string $notCompleteAnymoreAction notCompleteAnymoreAction
     *
     * @return ProductCleaner
     */
    public function setNotCompleteAnymoreAction($notCompleteAnymoreAction)
    {
        $this->notCompleteAnymoreAction = $notCompleteAnymoreAction;

        return $this;
    }

    /**
     * @param WebserviceGuesser                   $webserviceGuesser
     * @param ChannelManager                      $channelManager
     * @param ProductManager                      $productManager
     * @param MagentoSoapClientParametersRegistry $clientParametersRegistry
     */
    public function __construct(
        WebserviceGuesser $webserviceGuesser,
        ChannelManager $channelManager,
        ProductManager $productManager,
        MagentoSoapClientParametersRegistry $clientParametersRegistry
    ) {
        parent::__construct($webserviceGuesser, $clientParametersRegistry);

        $this->channelManager = $channelManager;
        $this->productManager = $productManager;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        parent::beforeExecute();

        $magentoProducts  = $this->webservice->getProductsStatus();
        $exportedProducts = $this->getProductsSkus($this->getExportedProductsSkus());
        $pimProducts      = $this->getProductsSkus($this->getPimProductsSkus());

        foreach ($magentoProducts as $product) {
            try {
                if ($product['type'] === AbstractNormalizer::MAGENTO_SIMPLE_PRODUCT_KEY) {
                    if (!in_array($product['sku'], $pimProducts)) {
                        $this->handleProductNotInPimAnymore($product);
                    } elseif (!in_array($product['sku'], $exportedProducts)) {
                        $this->handleProductNotCompleteAnymore($product);
                    }
                }
            } catch (SoapCallException $e) {
                throw new InvalidItemException($e->getMessage(), [json_encode($product)]);
            }
        }
    }

    /**
     * Get all products' skus in channel
     * @return array
     */
    protected function getExportedProductsSkus()
    {
        return $this->productManager->getProductRepository()
            ->buildByChannelAndCompleteness($this->channelManager->getChannelByCode($this->channel))
            ->select('Value.varchar as sku')
            ->andWhere('Attribute.attributeType = :identifier_type')
            ->setParameter(':identifier_type', 'pim_catalog_identifier')
            ->getQuery()
            ->setHydrationMode(Query::HYDRATE_ARRAY)
            ->getResult();
    }

    /**
     * Get all products' skus
     * @return array
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
     * Get skus for the given products
     * @param array $products
     *
     * @return array
     */
    protected function getProductsSkus(array $products)
    {
        $productsSkus = [];

        foreach ($products as $product) {
            $productsSkus[] = (string) reset($product);
        };

        return $productsSkus;
    }

    /**
     * Handle products that are not in pim anymore
     * @param array $product
     */
    protected function handleProductNotInPimAnymore(array $product)
    {
        $this->handleProduct($product, $this->notInPimAnymoreAction);
    }

    /**
     * Handle products that are not in channel anymore
     * @param array $product
     */
    protected function handleProductNotCompleteAnymore(array $product)
    {
        $this->handleProduct($product, $this->notCompleteAnymoreAction);
    }

    /**
     * Handle product for the given action
     * @param array  $product
     * @param string $action
     */
    protected function handleProduct(array $product, $action)
    {
        if ($action === self::DISABLE) {
            $this->webservice->disableProduct($product['sku']);
            $this->stepExecution->incrementSummaryInfo('product_disabled');
        } elseif ($action === self::DELETE) {
            $this->webservice->deleteProduct($product['sku']);
            $this->stepExecution->incrementSummaryInfo('product_deleted');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigurationFields()
    {
        return array_merge(
            parent::getConfigurationFields(),
            [
                'notCompleteAnymoreAction' => [
                    'type'    => 'choice',
                    'options' => [
                        'choices'  => [
                            Cleaner::DO_NOTHING => 'pim_magento_connector.export.do_nothing.label',
                            Cleaner::DISABLE    => 'pim_magento_connector.export.disable.label',
                            Cleaner::DELETE     => 'pim_magento_connector.export.delete.label',
                        ],
                        'required' => true,
                        'help'     => 'pim_magento_connector.export.notCompleteAnymoreAction.help',
                        'label'    => 'pim_magento_connector.export.notCompleteAnymoreAction.label',
                        'attr'     => ['class' => 'select2'],
                    ],
                ],
                'channel'      => [
                    'type'    => 'choice',
                    'options' => [
                        'choices'  => $this->channelManager->getChannelChoices(),
                        'required' => true,
                    ],
                ]
            ]
        );
    }
}
