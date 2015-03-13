<?php

namespace Pim\Bundle\MagentoConnectorBundle\Writer;

use Symfony\Component\Validator\Constraints as Assert;
use Pim\Bundle\MagentoConnectorBundle\Webservice\Webservice;
use Pim\Bundle\MagentoConnectorBundle\Guesser\WebserviceGuesser;
use Pim\Bundle\CatalogBundle\Manager\ChannelManager;
use Pim\Bundle\MagentoConnectorBundle\Webservice\SoapCallException;
use Pim\Bundle\MagentoConnectorBundle\Webservice\MagentoSoapClientParametersRegistry;

/**
 * Magento product writer.
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProductWriter extends AbstractWriter
{
    /** @var ChannelManager */
    protected $channelManager;

    /**
     * @Assert\NotBlank(groups={"Execution"})
     */
    protected $channel;

    /**
     * @param WebserviceGuesser                   $webserviceGuesser
     * @param ChannelManager                      $channelManager
     * @param MagentoSoapClientParametersRegistry $clientParametersRegistry
     */
    public function __construct(
        WebserviceGuesser $webserviceGuesser,
        ChannelManager $channelManager,
        MagentoSoapClientParametersRegistry $clientParametersRegistry
    ) {
        parent::__construct($webserviceGuesser, $clientParametersRegistry);

        $this->channelManager = $channelManager;
    }

    /**
     * @return string
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @param string $channel
     *
     * @return ProductWriter
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
                try {
                    $this->computeProduct($product);
                } catch (SoapCallException $e) {
                    $this->addWarning($e->getMessage(), [], $product);
                }
            }
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
                'channel' => [
                    'type'    => 'choice',
                    'options' => [
                        'choices'  => $this->channelManager->getChannelChoices(),
                        'required' => true,
                        'help'     => 'pim_magento_connector.export.channel.help',
                        'label'    => 'pim_magento_connector.export.channel.label',
                    ],
                ],
            ]
        );
    }

    /**
     * Compute an individual product and all his parts (translations).
     *
     * @param array $product
     */
    protected function computeProduct($product)
    {
        $sku    = $this->getProductSku($product);
        $images = $this->webservice->getImages($sku, $this->defaultStoreView);

        $this->pruneImages($sku, $images);

        $pdfs = $this->webservice->getPdfs($sku, $this->defaultStoreView);
        $this->prunePdfs($pdfs);

        foreach (array_keys($product) as $storeViewCode) {
            $this->createCall($product[$storeViewCode], $storeViewCode);
        }
    }

    /**
     * Create a call for the given product part.
     *
     * @param array  $productPart   A product part
     * @param string $storeViewCode The store view code
     */
    protected function createCall($productPart, $storeViewCode)
    {
        switch ($storeViewCode) {
            case $this->getDefaultStoreView():
                $this->webservice->sendProduct($productPart);
                $this->stepExecution->incrementSummaryInfo('product_sent');
                break;
            case Webservice::IMAGES:
                $this->webservice->sendImages($productPart);
                $this->stepExecution->incrementSummaryInfo('product_image_sent');
                break;
            case Webservice::PDFS:
                $this->webservice->sendPdfs($productPart);
                $this->stepExecution->incrementSummaryInfo('product_pdf_sent');
                break;
            default:
                $this->webservice->updateProductPart($productPart);
                $this->stepExecution->incrementSummaryInfo('product_translation_sent');
        }
    }

    /**
     * Get the sku of the given normalized product.
     *
     * @param array $product
     *
     * @return string
     */
    protected function getProductSku($product)
    {
        $defaultStoreViewProduct = $product[$this->getDefaultStoreView()];

        if (count($defaultStoreViewProduct) == Webservice::CREATE_PRODUCT_SIZE ||
            'configurable' === $defaultStoreViewProduct[0]
        ) {
            return (string) $defaultStoreViewProduct[2];
        } else {
            return (string) $defaultStoreViewProduct[0];
        }
    }

    /**
     * Clean old images on magento product.
     *
     * @param string $sku
     * @param array  $images
     */
    protected function pruneImages($sku, array $images = [])
    {
        foreach ($images as $image) {
            $this->webservice->deleteImage($sku, $image['file']);
        }
    }
    /**
     * Clean old pdfs on magento product
     *
     * @param array  $pdfs
     */
    protected function prunePdfs(array $pdfs = [])
    {
        if (isset($pdfs['links']) && sizeof($pdfs['links'])) {
            foreach (@$pdfs['links'] as $pdf) {
                $this->webservice->deletePdf($pdf['link_id'], "link");
            }
        }
        if (isset($pdfs['samples']) && sizeof($pdfs['samples'])) {
            foreach (@$pdfs['samples'] as $pdf) {
                $this->webservice->deletePdf($pdf['sample_id'], "sample");
            }
        }
    }
}