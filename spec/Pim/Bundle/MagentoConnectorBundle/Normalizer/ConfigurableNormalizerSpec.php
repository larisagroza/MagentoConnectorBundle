<?php

namespace spec\Pim\Bundle\MagentoConnectorBundle\Normalizer;

use PhpSpec\ObjectBehavior;
use Pim\Bundle\CatalogBundle\Entity\Channel;
use Pim\Bundle\CatalogBundle\Entity\Group;
use Pim\Bundle\CatalogBundle\Entity\Locale;
use Pim\Bundle\CatalogBundle\Manager\ChannelManager;
use Pim\Bundle\CatalogBundle\Model\ProductInterface;
use Pim\Bundle\MagentoConnectorBundle\Manager\PriceMappingManager;
use Pim\Bundle\MagentoConnectorBundle\Mapper\MappingCollection;
use Pim\Bundle\MagentoConnectorBundle\Normalizer\ProductNormalizer;
use Prophecy\Argument;

class ConfigurableNormalizerSpec extends ObjectBehavior
{
    protected $globalContext = [];

    function let(
        ChannelManager $channelManager,
        ProductNormalizer $productNormalizer,
        PriceMappingManager $priceMappingManager,
        MappingCollection $categoryMapping,
        MappingCollection $attributeMapping,
        MappingCollection $storeViewMapping,
        ProductInterface $product,
        Channel $channel,
        Locale $localeFR,
        Locale $localeEN,
        Group $group
    ) {
        $this->beConstructedWith($channelManager, $productNormalizer, $priceMappingManager, 4);

        $this->globalContext = [
            'attributeSetId'           => 0,
            'magentoAttributes'        => [],
            'magentoAttributesOptions' => [],
            'storeViewMapping'         => $storeViewMapping,
            'magentoStoreViews'        => [['code' => 'fr_fr']],
            'defaultLocale'            => 'default_locale',
            'website'                  => 'website',
            'channel'                  => 'channel',
            'categoryMapping'          => $categoryMapping,
            'attributeCodeMapping'     => $attributeMapping,
            'create'                   => true,
            'defaultStoreView'         => 'default',
            'smallImageAttribute'      => 'smallImageAttr',
            'baseImageAttribute'       => 'baseImageAttr',
            'thumbnailAttribute'       => 'thumbnailAttr'
        ];

        $productNormalizer
            ->getNormalizedImages($product, 'conf-group_code', 'smallImageAttr', 'baseImageAttr', 'thumbnailAttr')
            ->willReturn([]);
        $productNormalizer
            ->getValues(Argument::cetera())
            ->willReturn([ProductNormalizer::URL_KEY => 'my-url-key']);

        $channelManager->getChannelByCode('channel')->willReturn($channel);
        $channel->getLocales()->willReturn([$localeEN, $localeFR]);
        $localeEN->getCode()->willReturn('default_locale');
        $localeFR->getCode()->willReturn('fr_FR');
        $channel->getCode()->willReturn('channel_code');

        $storeViewMapping->getTarget('default_locale')->willReturn('default_locale');
        $storeViewMapping->getTarget('fr_FR')->willReturn('fr_fr');

        $group->getId()->willReturn(44);
        $group->getCode()->willReturn('group_code');
        $product->getIdentifier()->willReturn('sku-000');
    }

    function it_normalizes_a_new_configurable_product($group, $product, $priceMappingManager, $attributeMapping)
    {
        $products = [$product];

        $priceMappingManager->getPriceMapping($group, $products, $attributeMapping)->willReturn(
            ['price_changes' => [], 'price' => []]
        );
        $priceMappingManager->validatePriceMapping($products, [], [], $attributeMapping)->willReturn(true);

        $this->normalize(
            [
                'group'    => $group,
                'products' => $products,
            ],
            'MagentoArray',
            $this->globalContext
        )->shouldReturn(
                [
                    'default' => [
                        'configurable',
                        0,
                        'conf-group_code',
                        [
                            'url_key'         => 'my-url-key-conf-44',
                            'visibility'      => 4,
                            'price_changes'   => [],
                            'price'           => [],
                            'associated_skus' => ['sku-000'],
                            'websites'        => ['website']
                        ],
                    ],
                    'fr_fr'   => [
                        'conf-group_code',
                        ['url_key' => 'my-url-key'],
                        'fr_fr',
                    ],
                ]
            );
    }

    function it_normalizes_a_updated_configurable_product(
        $group,
        $product,
        $priceMappingManager,
        $attributeMapping
    ) {
        $this->globalContext['create']           = false;
        $this->globalContext['defaultStoreView'] = 'default';

        $products = [$product];

        $priceMappingManager->getPriceMapping($group, $products, $attributeMapping)->willReturn(
            ['price_changes' => [], 'price' => []]
        );
        $priceMappingManager->validatePriceMapping($products, [], [], $attributeMapping)->willReturn(true);

        $this->normalize(
            [
                'group'    => $group,
                'products' => $products,
            ],
            'MagentoArray',
            $this->globalContext
        )->shouldReturn(
                [
                    'default' => [
                        'conf-group_code',
                        [
                            'url_key'         => 'my-url-key-conf-44',
                            'visibility'      => 4,
                            'price_changes'   => [],
                            'price'           => [],
                            'associated_skus' => ['sku-000'],
                            'websites'        => ['website']
                        ],
                    ],
                    'fr_fr'   => [
                        'conf-group_code',
                        ['url_key' => 'my-url-key'],
                        'fr_fr',
                    ],
                ]
            );
    }

    function it_raises_an_expcetion_if_the_locale_does_not_have_a_corresponding_storeview(
        $group,
        $product,
        $priceMappingManager,
        $attributeMapping
    ) {
        $this->globalContext['create']            = false;
        $this->globalContext['magentoStoreViews'] = [];
        $this->globalContext['magentoStoreView']  = 'default';

        $products = [$product];

        $priceMappingManager->getPriceMapping($group, $products, $attributeMapping)->willReturn(
            ['price_changes' => [], 'price' => []]
        );
        $priceMappingManager->validatePriceMapping($products, [], [], $attributeMapping)->willReturn(true);

        $this->shouldThrow('Pim\Bundle\MagentoConnectorBundle\Normalizer\Exception\LocaleNotMatchedException')->during(
            'normalize',
            [
                [
                    'group'    => $group,
                    'products' => $products,
                ],
                'MagentoArray',
                $this->globalContext
            ]
        );
    }

    function it_raises_an_expcetion_if_the_price_mapping_is_not_valid(
        $group,
        $product,
        $priceMappingManager,
        $attributeMapping
    ) {
        $this->globalContext['create']           = false;
        $this->globalContext['magentoStoreView'] = 'default';

        $products = [$product];

        $priceMappingManager->getPriceMapping($group, $products, $attributeMapping)->willReturn(
            ['price_changes' => [], 'price' => []]
        );
        $priceMappingManager->validatePriceMapping($products, [], [], $attributeMapping)->willThrow(
            'Pim\Bundle\MagentoConnectorBundle\Manager\ComputedPriceNotMatchedException'
        );

        $this->shouldThrow(
            'Pim\Bundle\MagentoConnectorBundle\Normalizer\Exception\InvalidPriceMappingException'
        )->during(
                'normalize',
                [
                    [
                        'group'    => $group,
                        'products' => $products,
                    ],
                    'MagentoArray',
                    $this->globalContext
                ]
            );
    }
}
