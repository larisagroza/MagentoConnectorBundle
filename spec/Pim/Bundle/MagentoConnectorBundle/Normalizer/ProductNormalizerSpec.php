<?php

namespace spec\Pim\Bundle\MagentoConnectorBundle\Normalizer;

use Doctrine\Common\Collections\ArrayCollection;
use PhpSpec\ObjectBehavior;
use Pim\Bundle\CatalogBundle\Entity\Category;
use Pim\Bundle\CatalogBundle\Entity\Channel;
use Pim\Bundle\CatalogBundle\Entity\Locale;
use Pim\Bundle\CatalogBundle\Manager\ChannelManager;
use Pim\Bundle\CatalogBundle\Manager\MediaManager;
use Pim\Bundle\CatalogBundle\Model\AbstractAttribute;
use Pim\Bundle\CatalogBundle\Model\Product;
use Pim\Bundle\CatalogBundle\Model\ProductMedia;
use Pim\Bundle\CatalogBundle\Model\ProductValue;
use Pim\Bundle\MagentoConnectorBundle\Manager\AssociationTypeManager;
use Pim\Bundle\MagentoConnectorBundle\Manager\CategoryMappingManager;
use Pim\Bundle\MagentoConnectorBundle\Mapper\MappingCollection;
use Pim\Bundle\MagentoConnectorBundle\Normalizer\ProductValueNormalizer;
use Prophecy\Argument;

class ProductNormalizerSpec extends ObjectBehavior
{
    protected $globalContext = [];

    function let(
        ChannelManager $channelManager,
        MediaManager $mediaManager,
        ProductValueNormalizer $productValueNormalizer,
        CategoryMappingManager $categoryMappingManager,
        AssociationTypeManager $associationTypeManager,
        MappingCollection $storeViewMapping,
        MappingCollection $categoryMapping,
        MappingCollection $attributeMapping,
        Product $product,
        ProductValue $productValue,
        ProductValue $imageValue,
        Channel $channel,
        Locale $localeFR,
        Locale $localeEN,
        Category $category
    ) {
        $this->beConstructedWith(
            $channelManager,
            $mediaManager,
            $productValueNormalizer,
            $categoryMappingManager,
            $associationTypeManager,
            1,
            4,
            1,
            'currency',
            'magento_url'
        );

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
            'pimGrouped'               => 'pim_grouped',
            'created_date'             => (new \DateTime()),
            'updated_date'             => (new \DateTime()),
            'defaultStoreView'         => 'default',
            'smallImageAttribute'      => 'small_image_attribute',
            'baseImageAttribute'       => 'image_attribute',
            'thumbnailAttribute'       => 'image_attribute',
        ];

        $attributeMapping->getTarget('visibility')->willReturn('visibility');
        $attributeMapping->getTarget('created_at')->willReturn('created_at');
        $attributeMapping->getTarget('updated_at')->willReturn('updated_at');
        $attributeMapping->getTarget('status')->willReturn('status');
        $attributeMapping->getTarget('categories')->willReturn('categories');
        $attributeMapping->getTarget('url_key')->willReturn('url_key');
        $attributeMapping->getSource('name')->willReturn('my-name');

        $channelManager->getChannelByCode('channel')->willReturn($channel);
        $channel->getLocales()->willReturn([$localeEN, $localeFR]);
        $localeEN->getCode()->willReturn('default_locale');
        $localeFR->getCode()->willReturn('fr_FR');
        $channel->getCode()->willReturn('channel_code');
        $channel->getCategory()->willReturn($category);

        $product->getCategories()->willReturn([$category]);
        $product->getIdentifier()->willReturn('sku-000');
        $product->getCreated()->willReturn($this->globalContext['created_date']);
        $product->getUpdated()->willReturn($this->globalContext['updated_date']);
        $product->getValues()->willReturn(new ArrayCollection([$productValue, $imageValue]));
        $product->getValue('my-name', Argument::any(), Argument::any())->willReturn('my-name');

        $storeViewMapping->getTarget('default_locale')->willReturn('default_locale');
        $storeViewMapping->getTarget('fr_FR')->willReturn('fr_fr');

        $categoryMappingManager->getIdFromCategory($category, 'magento_url', $categoryMapping)->willReturn(2);

        $productValueNormalizer->normalize($productValue, Argument::cetera())->willReturn(
            ['value' => 'productValueNormalized']
        );
        $productValueNormalizer->normalize($imageValue, Argument::cetera())->willReturn(null);
    }

    function it_normalizes_the_given_new_product($product)
    {
        $product->getGroups()->willReturn([]);
        $product->getAssociationForTypeCode('pim_grouped')->willReturn(null);

        $this->normalize($product, 'MagentoArray', $this->globalContext)->shouldReturn(
            [
                'default' => [
                    'simple',
                    0,
                    'sku-000',
                    [
                        'categories' => [2],
                        'created_at' => $this->globalContext['created_date']->format('Y-m-d H:i:s'),
                        'status'     => 1,
                        'updated_at' => $this->globalContext['updated_date']->format('Y-m-d H:i:s'),
                        'url_key'    => 'my-name-sku-000',
                        'value'      => 'productValueNormalized',
                        'visibility' => 4,
                        'websites'   => ['website']
                    ],
                    'default',
                ],
                'fr_fr'   => [
                    'sku-000',
                    [
                        'categories' => [2],
                        'created_at' => $this->globalContext['created_date']->format('Y-m-d H:i:s'),
                        'status'     => 1,
                        'updated_at' => $this->globalContext['updated_date']->format('Y-m-d H:i:s'),
                        'url_key'    => 'my-name-sku-000',
                        'value'      => 'productValueNormalized',
                        'visibility' => 4
                    ],
                    'fr_fr',
                    'sku',
                ],
            ]
        );
    }

    function it_raises_an_exception_if_product_category_is_not_found(
        $product,
        $categoryMappingManager,
        $category,
        $categoryMapping
    ) {
        $product->getGroups()->willReturn([]);
        $categoryMappingManager->getIdFromCategory($category, 'magento_url', $categoryMapping)->willReturn(null);

        $this
            ->shouldThrow('Pim\Bundle\MagentoConnectorBundle\Normalizer\Exception\CategoryNotFoundException')
            ->during(
                'normalize',
                [$product, 'MagentoArray', $this->globalContext]
            );
    }

    function it_raises_an_exception_if_a_storeview_is_missing($product)
    {
        $product->getGroups()->willReturn([]);
        $product->getAssociationForTypeCode('pim_grouped')->willReturn(null);

        $this->globalContext['magentoStoreViews'] = [];
        $this->globalContext['magentoStoreView']  = 'default';

        $this
            ->shouldThrow('Pim\Bundle\MagentoConnectorBundle\Normalizer\Exception\LocaleNotMatchedException')
            ->during(
                'normalize',
                [$product, 'MagentoArray', $this->globalContext]
            );
    }

    function it_normalizes_images_for_given_product(
        $product,
        $imageValue,
        ProductMedia $image,
        ArrayCollection $productValues,
        AbstractAttribute $imageAttribute,
        $mediaManager
    ) {
        $product->getValues()->willReturn($productValues);
        $productValues->filter(Argument::any())->willReturn([$imageValue]);
        $imageValue->getAttribute()->willReturn($imageAttribute);
        $imageAttribute->getCode()->willReturn('small_image_attribute');
        $imageValue->getData()->willReturn($image);

        $mediaManager->getBase64($image)->willReturn('image_data');

        $image->getFilename()->willReturn('image_filename');
        $image->getMimeType()->willReturn('jpeg');

        $this->getNormalizedImages($product, 'sku-000', 'small_image_attribute')->shouldReturn(
            [
                [
                    'sku-000',
                    [
                        'file'     => [
                            'name'    => 'image_filename',
                            'content' => 'image_data',
                            'mime'    => 'jpeg',
                        ],
                        'label'    => 'image_filename',
                        'position' => 0,
                        'types'    => ['small_image'],
                        'exclude'  => 0
                    ],
                    0,
                    'sku',
                ],
            ]
        );
    }

    function it_normalizes_the_given_updated_product($product)
    {
        $this->globalContext['create']           = false;
        $this->globalContext['defaultStoreView'] = 'default';
        $product->getGroups()->willReturn([]);

        $this->normalize($product, 'MagentoArray', $this->globalContext)->shouldReturn(
            [
                'default' => [
                    'sku-000',
                    [
                        'categories' => [2],
                        'created_at' => $this->globalContext['created_date']->format('Y-m-d H:i:s'),
                        'status'     => 1,
                        'updated_at' => $this->globalContext['updated_date']->format('Y-m-d H:i:s'),
                        'url_key'    => 'my-name-sku-000',
                        'value'      => 'productValueNormalized',
                        'visibility' => 4,
                        'websites'   => ['website'],
                    ],
                    'default',
                    'sku',
                ],
                'fr_fr'   => [
                    'sku-000',
                    [
                        'categories' => [2],
                        'created_at' => $this->globalContext['created_date']->format('Y-m-d H:i:s'),
                        'status'     => 1,
                        'updated_at' => $this->globalContext['updated_date']->format('Y-m-d H:i:s'),
                        'url_key'    => 'my-name-sku-000',
                        'value'      => 'productValueNormalized',
                        'visibility' => 4,
                    ],
                    'fr_fr',
                    'sku',
                ],
            ]
        );
    }
}
