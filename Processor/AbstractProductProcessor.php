<?php

namespace Pim\Bundle\MagentoConnectorBundle\Processor;

use Symfony\Component\Validator\Constraints as Assert;
use Pim\Bundle\CatalogBundle\Manager\ChannelManager;
use Pim\Bundle\MagentoConnectorBundle\Guesser\WebserviceGuesser;
use Pim\Bundle\MagentoConnectorBundle\Guesser\NormalizerGuesser;
use Pim\Bundle\MagentoConnectorBundle\Validator\Constraints\HasValidDefaultLocale;
use Pim\Bundle\MagentoConnectorBundle\Validator\Constraints\HasValidCurrency;
use Pim\Bundle\MagentoConnectorBundle\Manager\LocaleManager;
use Pim\Bundle\MagentoConnectorBundle\Merger\MagentoMappingMerger;
use Pim\Bundle\MagentoConnectorBundle\Manager\CurrencyManager;
use Pim\Bundle\MagentoConnectorBundle\Manager\AttributeManager;
use Pim\Bundle\MagentoConnectorBundle\Webservice\MagentoSoapClientParametersRegistry;

/**
 * Abstract magento product processor
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 * @HasValidDefaultLocale(groups={"Execution"})
 * @HasValidCurrency(groups={"Execution"})
 */
abstract class AbstractProductProcessor extends AbstractProcessor
{
    const MAGENTO_VISIBILITY_CATALOG_SEARCH = 4;

    const MAGENTO_VISIBILITY_NONE = 1;

    /**
     * @var ProductNormalizer
     */
    protected $productNormalizer;

    /**
     * @var ChannelManager
     */
    protected $channelManager;

    /**
     * @var CurrencyManager
     */
    protected $currencyManager;

    /**
     * @var Currency
     * @Assert\NotBlank(groups={"Execution"})
     */
    protected $currency;

    /**
     * @Assert\NotBlank(groups={"Execution"})
     */
    protected $channel;

    /**
     * @var boolean
     */
    protected $enabled;

    /**
     * @var integer
     */
    protected $visibility = self::MAGENTO_VISIBILITY_CATALOG_SEARCH;

    /**
     * @var integer
     */
    protected $variantMemberVisibility = self::MAGENTO_VISIBILITY_NONE;

    /**
     * @var string
     */
    protected $categoryMapping;

    /**
     * @var MagentoMappingMerger
     */
    protected $categoryMappingMerger;

    /**
     * @var AttributeManager
     */
    protected $attributeManager;

    /**
     * @var string
     */
    protected $attributeCodeMapping;

    /**
     * @var MagentoMappingMerger
     */
    protected $attributeMappingMerger;

    /**
     * @var string
     */
    protected $smallImageAttribute;

    /**
     * @var string
     */
    protected $baseImageAttribute;

    /**
     * @var string
     */
    protected $thumbnailAttribute;

    /** @var boolean */
    protected $urlKey;

    /** @var  boolean */
    protected $skuFirst;

    /**
     * @param WebserviceGuesser        $webserviceGuesser
     * @param ProductNormalizerGuesser $normalizerGuesser
     * @param LocaleManager            $localeManager
     * @param MagentoMappingMerger     $storeViewMappingMerger
     * @param CurrencyManager          $currencyManager
     * @param ChannelManager           $channelManager
     * @param MagentoMappingMerger     $categoryMappingMerger
     * @param MagentoMappingMerger     $attributeMappingMerger
     */
    public function __construct(
        WebserviceGuesser $webserviceGuesser,
        NormalizerGuesser $normalizerGuesser,
        LocaleManager $localeManager,
        MagentoMappingMerger $storeViewMappingMerger,
        CurrencyManager $currencyManager,
        ChannelManager $channelManager,
        MagentoMappingMerger $categoryMappingMerger,
        MagentoMappingMerger $attributeMappingMerger,
        MagentoSoapClientParametersRegistry $clientParametersRegistry,
        AttributeManager $attributeManager
    ) {
        parent::__construct(
            $webserviceGuesser,
            $normalizerGuesser,
            $localeManager,
            $storeViewMappingMerger,
            $clientParametersRegistry
        );

        $this->currencyManager        = $currencyManager;
        $this->channelManager         = $channelManager;
        $this->categoryMappingMerger  = $categoryMappingMerger;
        $this->attributeManager       = $attributeManager;
        $this->attributeMappingMerger = $attributeMappingMerger;
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
     * @return AbstractProcessor
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;

        return $this;
    }

    /**
     * get currency
     *
     * @return string currency
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Set currency
     *
     * @param string $currency currency
     *
     * @return AbstractProcessor
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * get enabled
     *
     * @return string enabled
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * Set enabled
     *
     * @param string $enabled enabled
     *
     * @return AbstractProcessor
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * get visibility
     *
     * @return string visibility
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * Set variant member visibility
     *
     * @param string $visibility visibility
     *
     * @return AbstractProcessor
     */
    public function setVariantMemberVisibility($visibility)
    {
        $this->variantMemberVisibility = $visibility;

        return $this;
    }

    /**
     * get visibility for variant member
     *
     * @return string visibility
     */
    public function getVariantMemberVisibility()
    {
        return $this->variantMemberVisibility;
    }

    /**
     * Set visibility
     *
     * @param string $visibility visibility
     *
     * @return AbstractProcessor
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * Get small image
     * @return string
     */
    public function getSmallImageAttribute()
    {
        return $this->smallImageAttribute;
    }

    /**
     * Set small image
     * @param string $smallImageAttribute
     *
     * @return ProductProcessor
     */
    public function setSmallImageAttribute($smallImageAttribute)
    {
        $this->smallImageAttribute = $smallImageAttribute;

        return $this;
    }

    /**
     * Get base image attribute
     * @return string
     */
    public function getBaseImageAttribute()
    {
        return $this->baseImageAttribute;
    }

    /**
     * Set base image attribute
     * @param string $baseImageAttribute
     *
     * @return ProductProcessor
     */
    public function setBaseImageAttribute($baseImageAttribute)
    {
        $this->baseImageAttribute = $baseImageAttribute;

        return $this;
    }

    /**
     * Get thumbnail attribute
     * @return string
     */
    public function getThumbnailAttribute()
    {
        return $this->thumbnailAttribute;
    }

    /**
     * Set thumbnail attribute
     * @param string $thumbnailAttribute
     *
     * @return ProductProcessor
     */
    public function setThumbnailAttribute($thumbnailAttribute)
    {
        $this->thumbnailAttribute = $thumbnailAttribute;

        return $this;
    }


    /**
     * get categoryMapping
     *
     * @return string categoryMapping
     */
    public function getCategoryMapping()
    {
        $mapping = null;

        if ($this->categoryMappingMerger->getMapping() !== null) {
            $mapping = json_encode($this->categoryMappingMerger->getMapping()->toArray());
        }

        return $mapping;
    }

    /**
     * Set categoryMapping
     *
     * @param string $categoryMapping categoryMapping
     *
     * @return AbstractProcessor
     */
    public function setCategoryMapping($categoryMapping)
    {
        $decodedCategoryMapping = json_decode($categoryMapping, true);

        if (!is_array($decodedCategoryMapping)) {
            $decodedCategoryMapping = [$decodedCategoryMapping];
        }

        $this->categoryMappingMerger->setParameters($this->getClientParameters(), $this->getDefaultStoreView());
        $this->categoryMappingMerger->setMapping($decodedCategoryMapping);
        $this->categoryMapping = $this->getCategoryMapping();

        return $this;
    }

    /**
     * get attribute code mapping
     *
     * @return string attributeCodeMapping
     */
    public function getAttributeCodeMapping()
    {
        $mapping = null;

        if ($this->attributeMappingMerger->getMapping() !== null) {
            $mapping = json_encode($this->attributeMappingMerger->getMapping()->toArray());
        }

        return $mapping;
    }

    /**
     * Set attribute code mapping
     *
     * @param string $attributeCodeMapping attributeCodeMapping
     *
     * @return AbstractProcessor
     */
    public function setAttributeCodeMapping($attributeCodeMapping)
    {
        $decodedAttributeCodeMapping = json_decode($attributeCodeMapping, true);

        if (!is_array($decodedAttributeCodeMapping)) {
            $decodedAttributeCodeMapping = [$decodedAttributeCodeMapping];
        }

        $this->attributeMappingMerger->setParameters($this->getClientParameters(), $this->getDefaultStoreView());
        $this->attributeMappingMerger->setMapping($decodedAttributeCodeMapping);
        $this->attributeCodeMapping = $this->getAttributeCodeMapping();

        return $this;
    }

    /**
     * Get url key
     *
     * @return boolean
     */
    public function isUrlKey()
    {
        return $this->urlKey;
    }

    /**
     * Set url key
     *
     * @param boolean $urlKey
     *
     * @return ProductProcessor
     */
    public function setUrlKey($urlKey)
    {
        $this->urlKey = $urlKey;

        return $this;
    }

    /**
     * Get skuFirst
     *
     * @return boolean
     */
    public function isSkuFirst()
    {
        return $this->skuFirst;
    }

    /**
     * Set skuFirst
     *
     * @param boolean $skuFirst
     *
     * @return ProductProcessor
     */
    public function setSkuFirst($skuFirst)
    {
        $this->skuFirst = $skuFirst;

        return $this;
    }

    /**
     * Function called before all process
     */
    protected function beforeExecute()
    {
        parent::beforeExecute();

        $this->productNormalizer = $this->normalizerGuesser->getProductNormalizer(
            $this->getClientParameters(),
            $this->enabled,
            $this->visibility,
            $this->variantMemberVisibility,
            $this->currency
        );

        $magentoStoreViews        = $this->webservice->getStoreViewsList();
        $magentoAttributes        = $this->webservice->getAllAttributes();
        $magentoAttributesOptions = $this->webservice->getAllAttributesOptions();

        $this->globalContext = array_merge(
            $this->globalContext,
            [
                'channel'                  => $this->channel,
                'website'                  => $this->website,
                'magentoAttributes'        => $magentoAttributes,
                'magentoAttributesOptions' => $magentoAttributesOptions,
                'magentoStoreViews'        => $magentoStoreViews,
                'categoryMapping'          => $this->categoryMappingMerger->getMapping(),
                'attributeCodeMapping'     => $this->attributeMappingMerger->getMapping(),
                'smallImageAttribute'      => $this->smallImageAttribute,
                'baseImageAttribute'       => $this->baseImageAttribute,
                'thumbnailAttribute'       => $this->thumbnailAttribute,
                'urlKey'                   => $this->urlKey,
                'skuFirst'                 => $this->skuFirst,
            ]
        );
    }

    /**
     * Called after the configuration is set
     */
    protected function afterConfigurationSet()
    {
        parent::afterConfigurationSet();

        $this->categoryMappingMerger->setParameters($this->getClientParameters(), $this->getDefaultStoreView());
        $this->attributeMappingMerger->setParameters($this->getClientParameters(), $this->getDefaultStoreView());
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
                        'label'    => 'pim_magento_connector.export.channel.label'
                    ]
                ],
                'enabled' => [
                    'type'    => 'switch',
                    'options' => [
                        'required' => true,
                        'help'     => 'pim_magento_connector.export.enabled.help',
                        'label'    => 'pim_magento_connector.export.enabled.label'
                    ]
                ],
                'visibility' => [
                    'type'    => 'text',
                    'options' => [
                        'required' => true,
                        'help'     => 'pim_magento_connector.export.visibility.help',
                        'label'    => 'pim_magento_connector.export.visibility.label'
                    ]
                ],
                'variantMemberVisibility' => [
                    'type'    => 'text',
                    'options' => [
                        'required' => true,
                        'help'     => 'pim_magento_connector.export.variant_member_visibility.help',
                        'label'    => 'pim_magento_connector.export.variant_member_visibility.label'
                    ]
                ],
                'currency' => [
                    'type'    => 'choice',
                    'options' => [
                        'choices'  => $this->currencyManager->getCurrencyChoices(),
                        'required' => true,
                        'help'     => 'pim_magento_connector.export.currency.help',
                        'label'    => 'pim_magento_connector.export.currency.label',
                        'attr' => [
                            'class' => 'select2'
                        ]
                    ]
                ],
                'smallImageAttribute' => [
                    'type' => 'choice',
                    'options' => [
                        'choices' => $this->attributeManager->getImageAttributeChoice(),
                        'help'    => 'pim_magento_connector.export.smallImageAttribute.help',
                        'label'   => 'pim_magento_connector.export.smallImageAttribute.label',
                        'attr' => [
                            'class' => 'select2'
                        ]
                    ]
                ],
                'baseImageAttribute' => [
                    'type' => 'choice',
                    'options' => [
                        'choices' => $this->attributeManager->getImageAttributeChoice(),
                        'help'    => 'pim_magento_connector.export.baseImageAttribute.help',
                        'label'   => 'pim_magento_connector.export.baseImageAttribute.label',
                        'attr' => [
                            'class' => 'select2'
                        ]
                    ]
                ],
                'thumbnailAttribute' => [
                    'type' => 'choice',
                    'options' => [
                        'choices' => $this->attributeManager->getImageAttributeChoice(),
                        'help'    => 'pim_magento_connector.export.thumbnailAttribute.help',
                        'label'   => 'pim_magento_connector.export.thumbnailAttribute.label',
                        'attr' => [
                            'class' => 'select2'
                        ]
                    ]
                ],
                'urlKey' => [
                    'type'    => 'checkbox',
                    'options' => [
                        'help'  => 'pim_magento_connector.export.urlKey.help',
                        'label' => 'pim_magento_connector.export.urlKey.label',
                    ],
                ],
                'skuFirst' => [
                    'type'    => 'checkbox',
                    'options' => [
                        'help'  => 'pim_magento_connector.export.skuFirst.help',
                        'label' => 'pim_magento_connector.export.skuFirst.label',
                    ],
                ],
            ],
            $this->categoryMappingMerger->getConfigurationField(),
            $this->attributeMappingMerger->getConfigurationField()
        );
    }
}
