<?php

namespace Pim\Bundle\MagentoConnectorBundle\Writer;

use Symfony\Component\Validator\Constraints as Assert;
use Pim\Bundle\MagentoConnectorBundle\Webservice\Webservice;
use Pim\Bundle\MagentoConnectorBundle\Webservice\MagentoSoapClientParameters;
use Pim\Bundle\MagentoConnectorBundle\Validator\Constraints\HasValidCredentials;
use Pim\Bundle\MagentoConnectorBundle\Guesser\WebserviceGuesser;
use Pim\Bundle\CatalogBundle\Manager\ChannelManager;
use Pim\Bundle\MagentoConnectorBundle\Webservice\SoapCallException;
use Oro\Bundle\BatchBundle\Item\InvalidItemException;

/**
 * Magento product writer
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 * @HasValidCredentials()
 */
class ProductWriter extends AbstractWriter
{
    /**
     * @var ChannelManager
     */
    protected $channelManager;

    /**
     * @Assert\NotBlank(groups={"Execution"})
     */
    protected $channel;

    /**
     * Constructor
     *
     * @param WebserviceGuesser $webserviceGuesser
     * @param ChannelManager    $channelManager
     */
    public function __construct(
        WebserviceGuesser $webserviceGuesser,
        ChannelManager $channelManager
    ) {
        parent::__construct($webserviceGuesser);

        $this->channelManager = $channelManager;
    }

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
     * @return AbstractWriter
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $products)
    {
        $this->beforeExecute();

        //creation for each product in the admin storeView (with default locale)
        foreach ($products as $batch) {
            foreach ($batch as $product) {
                $this->computeProduct($product);
            }
        }
    }

    /**
     * Compute an individual product and all his parts (translations)
     *
     * @param array $product The product and his parts
     */
    protected function computeProduct($product)
    {
        $this->pruneImages($product);

        foreach (array_keys($product) as $storeViewCode) {
            try {
                $this->createCall($product[$storeViewCode], $storeViewCode);
            } catch (SoapCallException $e) {
                throw new InvalidItemException($e->getMessage(), array($product));
            }
        }
    }

    /**
     * Create a call for the given product part
     *
     * @param array  $productPart   A product part
     * @param string $storeViewCode The storeview code
     */
    protected function createCall($productPart, $storeViewCode)
    {
        switch ($storeViewCode) {
            case Webservice::SOAP_DEFAULT_STORE_VIEW:
                $this->webservice->sendProduct($productPart);
                break;
            case Webservice::IMAGES:
                $this->webservice->sendImages($productPart);
                break;
            default:
                $this->webservice->updateProductPart($productPart);
        }
    }

    /**
     * Get the sku of the given normalized product
     *
     * @param array $product
     *
     * @return string
     */
    protected function getProductSku($product)
    {
        $defaultStoreviewProduct = $product[Webservice::SOAP_DEFAULT_STORE_VIEW];

        if (count($defaultStoreviewProduct) == Webservice::CREATE_PRODUCT_SIZE) {
            return (string) $defaultStoreviewProduct[2];
        } else {
            return (string) $defaultStoreviewProduct[0];
        }
    }

    /**
     * Clean old images on magento product
     *
     * @param array $product
     */
    protected function pruneImages($product)
    {
        $sku = $this->getProductSku($product);
        $images = $this->webservice->getImages($sku);

        foreach ($images as $image) {
            $this->webservice->deleteImage($sku, $image['file']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigurationFields()
    {
        return array_merge(
            parent::getConfigurationFields(),
            array(
                'channel' => array(
                    'type'    => 'choice',
                    'options' => array(
                        'choices'  => $this->channelManager->getChannelChoices(),
                        'required' => true
                    )
                )
            )
        );
    }
}