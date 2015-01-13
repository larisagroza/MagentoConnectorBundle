<?php

namespace spec\Pim\Bundle\MagentoConnectorBundle\Processor;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Event\EventInterface;
use PhpSpec\ObjectBehavior;
use Pim\Bundle\CatalogBundle\Entity\Channel;
use Pim\Bundle\CatalogBundle\Entity\Family;
use Pim\Bundle\CatalogBundle\Manager\ChannelManager;
use Pim\Bundle\CatalogBundle\Model\AbstractAttribute;
use Pim\Bundle\CatalogBundle\Model\Product;
use Pim\Bundle\CatalogBundle\Model\ProductValue;
use Pim\Bundle\MagentoConnectorBundle\Guesser\NormalizerGuesser;
use Pim\Bundle\MagentoConnectorBundle\Guesser\WebserviceGuesser;
use Pim\Bundle\MagentoConnectorBundle\Manager\AssociationTypeManager;
use Pim\Bundle\MagentoConnectorBundle\Manager\AttributeManager;
use Pim\Bundle\MagentoConnectorBundle\Manager\CurrencyManager;
use Pim\Bundle\MagentoConnectorBundle\Manager\LocaleManager;
use Pim\Bundle\MagentoConnectorBundle\Mapper\MappingCollection;
use Pim\Bundle\MagentoConnectorBundle\Merger\MagentoMappingMerger;
use Pim\Bundle\MagentoConnectorBundle\Normalizer\ProductNormalizer;
use Pim\Bundle\MagentoConnectorBundle\Webservice\MagentoSoapClientParameters;
use Pim\Bundle\MagentoConnectorBundle\Webservice\MagentoSoapClientParametersRegistry;
use Pim\Bundle\MagentoConnectorBundle\Webservice\Webservice;
use Pim\Bundle\TransformBundle\Converter\MetricConverter;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @author    Willy Mesnage <willy.mesnage@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProductProcessorSpec extends ObjectBehavior
{
    function let(
        WebserviceGuesser $webserviceGuesser,
        NormalizerGuesser $normalizerGuesser,
        LocaleManager $localeManager,
        MagentoMappingMerger $storeViewMappingMerger,
        CurrencyManager $currencyManager,
        ChannelManager $channelManager,
        MagentoMappingMerger $categoryMappingMerger,
        MagentoMappingMerger $attributeMappingMerger,
        MetricConverter $metricConverter,
        AssociationTypeManager $associationTypeManager,
        Webservice $webservice,
        MappingCollection $mappingCollection,
        NormalizerGuesser $normalizerGuesser,
        ProductNormalizer $productNormalizer,
        Product $product,
        Channel $channel,
        MagentoSoapClientParametersRegistry $clientParametersRegistry,
        MagentoSoapClientParameters $clientParameters,
        AttributeManager $attributeManager,
        EventDispatcher $eventDispatcher,
        StepExecution $stepExecution
    ) {
        $this->beConstructedWith(
            $webserviceGuesser,
            $normalizerGuesser,
            $localeManager,
            $storeViewMappingMerger,
            $currencyManager,
            $channelManager,
            $categoryMappingMerger,
            $attributeMappingMerger,
            $metricConverter,
            $associationTypeManager,
            $clientParametersRegistry,
            $attributeManager
        );
        $this->setStepExecution($stepExecution);
        $this->setEventDispatcher($eventDispatcher);

        $clientParametersRegistry->getInstance(null, null, null, '/api/soap/?wsdl', 'default', null, null)->willReturn(
            $clientParameters
        );
        $webserviceGuesser->getWebservice($clientParameters)->willReturn($webservice);

        $storeViewMappingMerger->getMapping()->willReturn($mappingCollection);

        $webservice->getStoreViewsList()->willReturn(
            [
                [
                    'store_id'   => '1',
                    'code'       => 'default',
                    'website_id' => '1',
                    'group_id'   => '1',
                    'name'       => 'Default Store View',
                    'sort_order' => '0',
                    'is_active'  => '1',
                ],
            ]
        );

        $webservice->getAllAttributes()->willReturn(
            [
                'name' => [
                    'attribute_id' => '71',
                    'code'         => 'name',
                    'type'         => 'text',
                    'required'     => '1',
                    'scope'        => 'store',
                ],
            ]
        );

        $normalizerGuesser->getProductNormalizer(
            $clientParameters,
            null,
            4,
            1,
            null
        )
            ->willReturn($productNormalizer);

        $webservice->getAllAttributesOptions()->willReturn([]);
        $webservice->getProductsStatus([$product])->willReturn(
            [
                [
                    'product_id'   => '1',
                    'sku'          => 'sku-000',
                    'name'         => 'Product example',
                    'set'          => '4',
                    'type'         => 'simple',
                    'category_ids' => ['207'],
                    'website_ids'  => ['1'],
                ],
            ]
        );

        $channelManager->getChannelByCode(null)->willReturn($channel);
    }

    function it_is_configurable(
        $categoryMappingMerger,
        $attributeMappingMerger,
        $mappingCollection
    ) {
        $this->setChannel('channel');
        $this->setCurrency('EUR');
        $this->setEnabled('true');
        $this->setVisibility('4');
        $this->setCategoryMapping('{"categoryMapping" : "category"}');
        $this->setAttributeCodeMapping('{"attributeCodeMapping" : "attribute"}');
        $this->setPimGrouped('group');

        $categoryMappingMerger->setMapping(['categoryMapping' => 'category'])->shouldBeCalled();
        $categoryMappingMerger->getMapping()->shouldBeCalled()->willReturn($mappingCollection);
        $this->getCategoryMapping();

        $attributeMappingMerger->setMapping(['attributeCodeMapping' => 'attribute'])->shouldBeCalled();
        $attributeMappingMerger->getMapping()->shouldBeCalled()->willReturn($mappingCollection);
        $this->getAttributeCodeMapping();

        $this->getChannel()->shouldReturn('channel');
        $this->getCurrency()->shouldReturn('EUR');
        $this->getEnabled()->shouldReturn('true');
        $this->getVisibility()->shouldReturn('4');
        $this->getPimGrouped()->shouldReturn('group');
    }

    function it_processes_new_products(
        $webservice,
        $attributeMappingMerger,
        $categoryMappingMerger,
        $productNormalizer,
        $mappingCollection,
        Product $product,
        Channel $channel,
        Family $family,
        MetricConverter $metricConverter,
        ProductValue $sku
    ) {
        $categoryMappingMerger->getMapping()->willReturn($mappingCollection);
        $attributeMappingMerger->getMapping()->willReturn($mappingCollection);

        $product->getFamily()->shouldBeCalled()->willReturn($family);
        $family->getCode()->shouldBeCalled()->willReturn('family_code');

        $webservice->getAttributeSetId('family_code')->shouldBeCalled()->willReturn('4');

        $product->getIdentifier()->shouldBeCalled()->willReturn($sku);
        $sku->getData()->willReturn('sku-001');

        $metricConverter->convert($product, $channel)->shouldBeCalled();

        $productNormalizer->normalize(
            Argument::type('\Pim\Bundle\CatalogBundle\Model\Product'),
            'MagentoArray',
            Argument::type('array')
        )->shouldBeCalled();

        $this->process($product);
    }

    function it_processes_already_created_products(
        $webservice,
        $attributeMappingMerger,
        $categoryMappingMerger,
        $productNormalizer,
        $mappingCollection,
        Product $product,
        Channel $channel,
        Family $family,
        MetricConverter $metricConverter,
        ProductValue $sku
    ) {
        $categoryMappingMerger->getMapping()->willReturn($mappingCollection);
        $attributeMappingMerger->getMapping()->willReturn($mappingCollection);

        $product->getFamily()->shouldBeCalled()->willReturn($family);
        $family->getCode()->shouldBeCalled()->willReturn('family_code');

        $webservice->getAttributeSetId('family_code')->shouldBeCalled()->willReturn('4');

        $product->getIdentifier()->shouldBeCalled()->willReturn($sku);
        $sku->getData()->willReturn('sku-000');

        $metricConverter->convert($product, $channel)->shouldBeCalled();

        $productNormalizer->normalize(
            Argument::type('\Pim\Bundle\CatalogBundle\Model\Product'),
            'MagentoArray',
            Argument::type('array')
        )->shouldBeCalled();

        $this->process($product);
    }

    function it_throws_an_exception_if_something_went_wrong_during_normalization(
        $webservice,
        $attributeMappingMerger,
        $categoryMappingMerger,
        $productNormalizer,
        $mappingCollection,
        EventDispatcher $eventDispatcher,
        Product $product,
        Channel $channel,
        Family $family,
        MetricConverter $metricConverter,
        AbstractAttribute $skuAttribute,
        ProductValue $sku
    ) {
        $categoryMappingMerger->getMapping()->willReturn($mappingCollection);
        $attributeMappingMerger->getMapping()->willReturn($mappingCollection);

        $product->getFamily()->shouldBeCalled()->willReturn($family);
        $family->getCode()->shouldBeCalled()->willReturn('family_code');

        $webservice->getAttributeSetId('family_code')->shouldBeCalled()->willReturn('4');

        $product->getIdentifier()->shouldBeCalled()->willReturn($sku);
        $sku->getData()->willReturn('sku-000');
        $sku->getAttribute()->willReturn($skuAttribute);
        $skuAttribute->getCode()->willReturn('SKU');

        $product->getId()->willReturn(12);
        $product->getLabel()->willReturn('my product');

        $metricConverter->convert($product, $channel)->shouldBeCalled();

        $productNormalizer
            ->normalize(
                Argument::type('\Pim\Bundle\CatalogBundle\Model\Product'),
                'MagentoArray',
                Argument::type('array')
            )
            ->shouldBeCalled()
            ->willThrow('\Pim\Bundle\MagentoConnectorBundle\Normalizer\Exception\NormalizeException');

        $eventDispatcher->dispatch(
            EventInterface::INVALID_ITEM,
            Argument::type('Akeneo\Bundle\BatchBundle\Event\InvalidItemEvent')
        )->shouldBeCalled();

        $this->process($product);
    }
}
