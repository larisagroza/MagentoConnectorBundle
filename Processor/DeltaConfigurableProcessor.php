<?php

namespace Pim\Bundle\MagentoConnectorBundle\Processor;

use Akeneo\Bundle\BatchBundle\Item\InvalidItemException;
use Doctrine\Common\Collections\Collection;
use Pim\Bundle\CatalogBundle\Entity\Channel;
use Pim\Bundle\CatalogBundle\Manager\ChannelManager;
use Pim\Bundle\CatalogBundle\Model\ProductInterface;
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
 * Magento delta configurable processor
 *
 * @author    Willy Mesnage <willy.mesnage@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class DeltaConfigurableProcessor extends AbstractProductProcessor
{
    /** @var \Pim\Bundle\MagentoConnectorBundle\Normalizer\ConfigurableNormalizer */
    protected $configurableNormalizer;

    /** @var GroupManager */
    protected $groupManager;

    /** @var array */
    protected $processedIds = [];

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
                $processedConfigurable = $this->processConfigurable($configurable, $magentoConfigurables);
                if ($processedConfigurable !== null) {
                    $processedItems[] = $processedConfigurable;
                }
            }
        }

        return $processedItems;
    }

    /**
     * Processes configurables
     *
     * @param array $configurable
     * @param array $magentoConfigurables
     *
     * @return array|null
     */
    protected function processConfigurable(array $configurable, array $magentoConfigurables)
    {
        if (empty($configurable['products'])) {
            $this->addWarning(
                'The variant group is not associated to any products or product has already been send.',
                [],
                $configurable
            );
            return;
        }

        if ($this->magentoConfigurableExist($configurable, $magentoConfigurables)) {
            $context = array_merge($this->globalContext, ['attributeSetId' => 0, 'create' => false]);
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
            $normalizedConfigurable = $this->normalizeConfigurable($configurable, $context);
        } catch (\Exception $e) {
            $this->addWarning($e->getMessage(), [], $configurable);
            return;
        }

        return $normalizedConfigurable;
    }

    /**
     * Normalize the given configurable
     *
     * @param array $configurable
     * @param array $context
     *
     * @return array
     */
    protected function normalizeConfigurable(array $configurable, array $context)
    {
        return $this->configurableNormalizer->normalize(
            $configurable,
            AbstractNormalizer::MAGENTO_FORMAT,
            $context
        );
    }

    /**
     * Test if a configurable already exist on magento platform
     *
     * @param array $configurable         The configurable
     * @param array $magentoConfigurables Magento configurables
     *
     * @return bool
     */
    protected function magentoConfigurableExist(array $configurable, array $magentoConfigurables)
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
     * @return \Pim\Bundle\CatalogBundle\Entity\Family;
     */
    protected function getGroupFamily(array $configurable)
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
        $channel = $this->channelManager->getChannelByCode($this->getChannel());
        $groups  = [];

        foreach ($products as $product) {
            foreach ($product->getGroups() as $group) {
                $groupId = $group->getId();

                if (in_array($groupId, $groupsIds)) {
                    $groupProducts = $group->getProducts();
                    $exportableProducts = $this->getExportableProducts(
                        $channel,
                        $groupProducts
                    );

                    if (!isset($groups[$groupId])) {
                        $groups[$groupId] = [
                            'group'    => $group,
                            'products' => [],
                        ];
                    }

                    if (!isset($this->processedIds[$groupId])) {
                        $this->processedIds[$groupId] = [];
                    }

                    foreach ($exportableProducts as $exportableProduct) {
                        $exportableProductId = $exportableProduct->getId();
                        if (!in_array($exportableProductId, $this->processedIds[$groupId])) {
                            $groups[$groupId]['products'][] = $exportableProduct;
                            $this->processedIds[$groupId][] = $exportableProductId;
                        }
                    }
                }
            }
        }

        return $groups;
    }

    /**
     *
     * Returns ready to export variant group products
     *
     * @param Channel    $channel
     * @param Collection $groupProducts
     *
     * @return array
     */
    protected function getExportableProducts(Channel $channel, Collection $groupProducts)
    {
        $exportableProducts = [];
        $rootCategoryId     = $channel->getCategory()->getId();

        foreach ($groupProducts as $product) {
            $productCategories = $product->getCategories()->toArray();
            if ($this->isProductComplete($product, $channel) &&
                false !== $productCategories &&
                $this->doesProductBelongToChannel($productCategories, $rootCategoryId)
            ) {
                $exportableProducts[] = $product;
            }
        }

        return $exportableProducts;

    }

    /**
     * Is the given product complete for the given channel?
     *
     * @param ProductInterface $product
     * @param Channel $channel
     *
     * @return bool
     */
    protected function isProductComplete(ProductInterface $product, Channel $channel)
    {
        $completenesses = $product->getCompletenesses()->toArray();
        foreach ($completenesses as $completeness) {
            if ($completeness->getChannel()->getId() === $channel->getId() &&
                $completeness->getRatio() < 100
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Compute the belonging of a product to a channel.
     * Validating one of its categories has the same root as the channel root category.
     *
     * @param \ArrayIterator|array $productCategories
     * @param int                  $rootCategoryId
     *
     * @return bool
     */
    protected function doesProductBelongToChannel($productCategories, $rootCategoryId)
    {
        foreach ($productCategories as $category) {
            if ($category->getRoot() === $rootCategoryId) {
                return true;
            }
        }

        return false;
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
