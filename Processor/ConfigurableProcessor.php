<?php

namespace Pim\Bundle\MagentoConnectorBundle\Processor;

use Pim\Bundle\CatalogBundle\Manager\ChannelManager;
use Akeneo\Bundle\BatchBundle\Item\InvalidItemException;
use Pim\Bundle\MagentoConnectorBundle\Webservice\Webservice;
use Pim\Bundle\MagentoConnectorBundle\Manager\PriceMappingManager;
use Pim\Bundle\MagentoConnectorBundle\Guesser\WebserviceGuesser;
use Pim\Bundle\MagentoConnectorBundle\Guesser\NormalizerGuesser;
use Pim\Bundle\MagentoConnectorBundle\Manager\AttributeManager;
use Pim\Bundle\MagentoConnectorBundle\Manager\GroupManager;
use Pim\Bundle\MagentoConnectorBundle\Normalizer\AbstractNormalizer;
use Pim\Bundle\MagentoConnectorBundle\Manager\LocaleManager;
use Pim\Bundle\MagentoConnectorBundle\Merger\MagentoMappingMerger;
use Pim\Bundle\MagentoConnectorBundle\Manager\CurrencyManager;
use Pim\Bundle\MagentoConnectorBundle\Webservice\MagentoSoapClientParametersRegistry;

/**
 * Magento configurable processor
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ConfigurableProcessor extends AbstractProductProcessor
{
    /**
     * @var ConfigurableNormalizer
     */
    protected $configurableNormalizer;

    /**
     * @var GroupManager
     */
    protected $groupManager;

    /**
     * @param WebserviceGuesser                   $webserviceGuesser
     * @param NormalizerGuesser                   $normalizerGuesser
     * @param LocaleManager                       $localeManager
     * @param MagentoMappingMerger                $storeViewMappingMerger
     * @param CurrencyManager                     $currencyManager
     * @param ChannelManager                      $channelManager
     * @param MagentoMappingMerger                $categoryMappingMerger
     * @param MagentoMappingMerger                $attributeMappingMerger
     * @param GroupManager                        $groupManager
     * @param MagentoSoapClientParametersRegistry $clientParametersRegistry
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
        GroupManager $groupManager,
        MagentoSoapClientParametersRegistry $clientParametersRegistry,
        AttributeManager $attributeManager
    ) {
        parent::__construct(
            $webserviceGuesser,
            $normalizerGuesser,
            $localeManager,
            $storeViewMappingMerger,
            $currencyManager,
            $channelManager,
            $categoryMappingMerger,
            $attributeMappingMerger,
            $clientParametersRegistry,
            $attributeManager
        );

        $this->groupManager = $groupManager;
    }

    /**
     * Function called before all process
     */
    protected function beforeExecute()
    {
        parent::beforeExecute();

        $priceMappingManager          = new PriceMappingManager($this->defaultLocale, $this->currency, $this->channel);
        $this->configurableNormalizer = $this->normalizerGuesser->getConfigurableNormalizer(
            $this->getClientParameters(),
            $this->productNormalizer,
            $priceMappingManager,
            $this->visibility
        );
    }

    /**
     * {@inheritdoc}
     */
    public function process($items)
    {
        $items = is_array($items) ? $items : [$items];

        $this->beforeExecute();

        $processedItems = [];
        $groupsIds      = $this->getGroupRepository()->getVariantGroupIds();

        if (count($groupsIds) > 0) {
            $configurables        = $this->getProductsForGroups($items, $groupsIds);
            $magentoConfigurables = $this->webservice->getConfigurablesStatus($configurables);

            if (empty($configurables)) {
                throw new InvalidItemException('Groups didn\'t match with variants groups', [$configurables]);
            }

            foreach ($configurables as $configurable) {
                if (empty($configurable['products'])) {
                    $this->addWarning('The variant group is not associated to any products', [], $configurable);
                }

                if ($this->magentoConfigurableExist($configurable, $magentoConfigurables)) {
                    $context = array_merge(
                        $this->globalContext,
                        ['attributeSetId' => 0, 'create' => false]
                    );
                } else {
                    $groupFamily = $this->getGroupFamily($configurable);
                    $context     = array_merge(
                        $this->globalContext,
                        [
                            'attributeSetId' => $this->getAttributeSetId($groupFamily->getCode(), $configurable),
                            'create'         => true
                        ]
                    );
                }

                try {
                    $processedItems[] = $this->normalizeConfigurable($configurable, $context);
                } catch (\Exception $e) {
                    $this->addWarning($e->getMessage(), [], $configurable['group']);
                }
            }
        }

        return $processedItems;
    }

    /**
     * Normalize the given configurable
     *
     * @param array $configurable The given configurable
     * @param array $context      The context
     *
     * @throws InvalidItemException If a normalization error occured
     * @return array                processed item
     */
    protected function normalizeConfigurable($configurable, $context)
    {
        $processedItem = $this->configurableNormalizer->normalize(
            $configurable,
            AbstractNormalizer::MAGENTO_FORMAT,
            $context
        );

        return $processedItem;
    }

    /**
     * Test if a configurable allready exist on magento platform
     *
     * @param array $configurable         The configurable
     * @param array $magentoConfigurables Magento configurables
     *
     * @return bool
     */
    protected function magentoConfigurableExist($configurable, $magentoConfigurables)
    {
        foreach ($magentoConfigurables as $magentoConfigurable) {
            if ($magentoConfigurable['sku'] == sprintf(
                Webservice::CONFIGURABLE_IDENTIFIER_PATTERN,
                $configurable['group']->getCode()
            )) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the family of the given configurable
     *
     * @param array $configurable
     *
     * @throws InvalidItemException If there are two products with different families
     *
     * @return Family
     */
    protected function getGroupFamily($configurable)
    {
        $groupFamily = $configurable['products'][0]->getFamily();

        foreach ($configurable['products'] as $product) {
            if ($groupFamily != $product->getFamily()) {
                $this->addWarning(
                    'Your variant group contains products from different families. Magento cannot handle '.
                    'configurable products with heterogen attribute sets',
                    [],
                    $configurable
                );
            }
        }

        return $groupFamily;
    }

    /**
     * Get products association for each groups
     *
     * @param array $products
     * @param array $groupsIds
     *
     * @return array
     */
    protected function getProductsForGroups(array $products, array $groupsIds)
    {
        $groups = [];

        foreach ($products as $product) {
            foreach ($product->getGroups() as $group) {
                $groupId = $group->getId();

                if (in_array($groupId, $groupsIds)) {
                    if (!isset($groups[$groupId])) {
                        $groups[$groupId] = [
                            'group'    => $group,
                            'products' => [],
                        ];
                    }

                    $groups[$groupId]['products'][] = $product;
                }
            }
        }

        return $groups;
    }

    /**
     * Get the group repository
     *
     * @return \Doctrine\ORM\EntityRepository
     */
    protected function getGroupRepository()
    {
        return $this->groupManager->getRepository();
    }
}
