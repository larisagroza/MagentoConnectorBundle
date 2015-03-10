<?php

namespace spec\Pim\Bundle\MagentoConnectorBundle\Processor;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Event\EventInterface;
use Akeneo\Bundle\BatchBundle\Item\InvalidItemException;
use PhpSpec\ObjectBehavior;
use Pim\Bundle\CatalogBundle\Entity\Channel;
use Pim\Bundle\CatalogBundle\Entity\Family;
use Pim\Bundle\CatalogBundle\Entity\Group;
use Pim\Bundle\CatalogBundle\Manager\ChannelManager;
use Pim\Bundle\CatalogBundle\Model\Product;
use Pim\Bundle\CatalogBundle\Model\ProductInterface;
use Pim\Bundle\MagentoConnectorBundle\Entity\Repository\GroupRepository;
use Pim\Bundle\MagentoConnectorBundle\Filter\ExportableProductFilter;
use Pim\Bundle\MagentoConnectorBundle\Guesser\NormalizerGuesser;
use Pim\Bundle\MagentoConnectorBundle\Guesser\WebserviceGuesser;
use Pim\Bundle\MagentoConnectorBundle\Manager\AssociationTypeManager;
use Pim\Bundle\MagentoConnectorBundle\Manager\AttributeManager;
use Pim\Bundle\MagentoConnectorBundle\Manager\CurrencyManager;
use Pim\Bundle\MagentoConnectorBundle\Manager\GroupManager;
use Pim\Bundle\MagentoConnectorBundle\Manager\LocaleManager;
use Pim\Bundle\MagentoConnectorBundle\Merger\MagentoMappingMerger;
use Pim\Bundle\MagentoConnectorBundle\Normalizer\ConfigurableNormalizer;
use Pim\Bundle\MagentoConnectorBundle\Normalizer\ProductNormalizer;
use Pim\Bundle\MagentoConnectorBundle\Webservice\MagentoSoapClientParameters;
use Pim\Bundle\MagentoConnectorBundle\Webservice\MagentoSoapClientParametersRegistry;
use Pim\Bundle\MagentoConnectorBundle\Webservice\Webservice;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @author    Willy Mesnage <willy.mesnage@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ConfigurableProcessorSpec extends ObjectBehavior
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
        MagentoSoapClientParametersRegistry $clientParametersRegistry,
        AttributeManager $attributeManager,
        AssociationTypeManager $associationTypeManager,
        GroupManager $groupManager,
        ExportableProductFilter $productFilter
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
            $clientParametersRegistry,
            $attributeManager,
            $associationTypeManager,
            $groupManager,
            $productFilter
        );
    }

    function it_throws_an_exception_if_group_does_not_match_with_variant_groups(
        $clientParametersRegistry,
        $normalizerGuesser,
        $groupManager,
        $channelManager,
        Group $group,
        Channel $channel,
        ProductInterface $product,
        MagentoSoapClientParameters $clientParameters,
        WebserviceGuesser $webserviceGuesser,
        Webservice $webservice,
        ProductNormalizer $productNormalizer,
        ConfigurableNormalizer $configurableNormalizer,
        GroupRepository $groupRepository
    ) {
        $clientParametersRegistry->getInstance(Argument::cetera())->willReturn($clientParameters);
        $webserviceGuesser->getWebservice($clientParameters)->willReturn($webservice);
        $normalizerGuesser->getProductNormalizer(Argument::cetera())->willReturn($productNormalizer);
        $normalizerGuesser->getConfigurableNormalizer(Argument::cetera())->willReturn($configurableNormalizer);

        $webservice->getStoreViewsList()->willReturn([
            [
                'store_id' => '1',
                'code' => 'default',
                'website_id' => '1',
                'group_id' => '1',
                'name' => 'Default Store View',
                'sort_order' => '0',
                'is_active' => '1',
            ],
        ]);
        $webservice->getAllAttributes()->willReturn([
            'name' => [
                'attribute_id' => '71',
                'code'         => 'name',
                'type'         => 'text',
                'required'     => '1',
                'scope'        => 'store',
            ],
        ]);
        $webservice->getAllAttributesOptions()->willReturn([]);
        $webservice->getConfigurablesStatus([])->willReturn(['status']);

        $groupManager->getRepository()->willReturn($groupRepository);
        $groupRepository->getVariantGroupIds()->willReturn([1]);

        $channelManager->getChannelByCode('magento')->willReturn($channel);

        $product->getGroups()->willReturn([$group]);

        $group->getId()->willReturn(2);

        $this->setChannel('magento');
        $this
            ->shouldThrow(new InvalidItemException('Groups didn\'t match with variants groups', [[]]))
            ->during('process', [$product]);
    }

    function it_processes_a_product_to_update_a_configurable_product_in_magento(
        $clientParametersRegistry,
        $normalizerGuesser,
        $groupManager,
        $channelManager,
        $productFilter,
        Group $group,
        Channel $channel,
        ProductInterface $product1,
        ProductInterface $product2,
        ProductInterface $product3,
        MagentoSoapClientParameters $clientParameters,
        WebserviceGuesser $webserviceGuesser,
        Webservice $webservice,
        ProductNormalizer $productNormalizer,
        ConfigurableNormalizer $configurableNormalizer,
        GroupRepository $groupRepository
    ) {
        $clientParametersRegistry->getInstance(Argument::cetera())->willReturn($clientParameters);
        $webserviceGuesser->getWebservice($clientParameters)->willReturn($webservice);
        $normalizerGuesser->getProductNormalizer(Argument::cetera())->willReturn($productNormalizer);
        $normalizerGuesser->getConfigurableNormalizer(Argument::cetera())->willReturn($configurableNormalizer);

        $webservice->getStoreViewsList()->willReturn([
            [
                'store_id'   => '1',
                'code'       => 'default',
                'website_id' => '1',
                'group_id'   => '1',
                'name'       => 'Default Store View',
                'sort_order' => '0',
                'is_active'  => '1',
            ],
        ]);
        $webservice->getAllAttributes()->willReturn([
            'name' => [
                'attribute_id' => '71',
                'code'         => 'name',
                'type'         => 'text',
                'required'     => '1',
                'scope'        => 'store',
            ],
        ]);
        $webservice->getAllAttributesOptions()->willReturn([]);
        $webservice
            ->getConfigurablesStatus([1 => ['group' => $group, 'products' => [$product1, $product2, $product3]]])
            ->willReturn([
                [
                    'product_id' => '6',
                    'sku' => 'conf-groupCode',
                    'name' => 'foo',
                    'set' => '3',
                    'type' => 'configurable',
                    'category_ids' => ['7'],
                    'website_ids' => ['1']
                ]
            ]);

        $groupManager->getRepository()->willReturn($groupRepository);
        $groupRepository->getVariantGroupIds()->willReturn([1]);

        $channelManager->getChannelByCode('magento')->willReturn($channel);

        $group->getId()->willReturn(1);
        $group->getProducts()->willReturn([$product1, $product2, $product3]);
        $group->getCode()->willReturn('groupCode');

        $product1->getGroups()->willReturn([$group]);
        $product1->getId()->willReturn(10);
        $product2->getId()->willReturn(11);
        $product3->getId()->willReturn(12);

        $productFilter
            ->apply($channel, [$product1, $product2, $product3])
            ->willReturn([$product1, $product2, $product3]);

        $configurableNormalizer->normalize(Argument::cetera())->willReturn(['bar']);

        $this->setChannel('magento');
        $this->process([$product1])->shouldReturn([['bar']]);
    }

    function it_processes_a_product_to_create_a_configurable_product_in_magento(
        $clientParametersRegistry,
        $normalizerGuesser,
        $groupManager,
        $channelManager,
        $productFilter,
        Group $group,
        Channel $channel,
        ProductInterface $product1,
        ProductInterface $product2,
        ProductInterface $product3,
        MagentoSoapClientParameters $clientParameters,
        WebserviceGuesser $webserviceGuesser,
        Webservice $webservice,
        ProductNormalizer $productNormalizer,
        ConfigurableNormalizer $configurableNormalizer,
        GroupRepository $groupRepository,
        Family $family
    ) {
        $clientParametersRegistry->getInstance(Argument::cetera())->willReturn($clientParameters);
        $webserviceGuesser->getWebservice($clientParameters)->willReturn($webservice);
        $normalizerGuesser->getProductNormalizer(Argument::cetera())->willReturn($productNormalizer);
        $normalizerGuesser->getConfigurableNormalizer(Argument::cetera())->willReturn($configurableNormalizer);

        $webservice->getStoreViewsList()->willReturn([
            [
                'store_id'   => '1',
                'code'       => 'default',
                'website_id' => '1',
                'group_id'   => '1',
                'name'       => 'Default Store View',
                'sort_order' => '0',
                'is_active'  => '1',
            ],
        ]);
        $webservice->getAllAttributes()->willReturn([
            'name' => [
                'attribute_id' => '71',
                'code'         => 'name',
                'type'         => 'text',
                'required'     => '1',
                'scope'        => 'store',
            ],
        ]);
        $webservice->getAllAttributesOptions()->willReturn([]);
        $webservice
            ->getConfigurablesStatus([1 => ['group' => $group, 'products' => [$product1, $product2, $product3]]])
            ->willReturn([]);
        $webservice->getAttributeSetId('familyCode')->willReturn(5);

        $groupManager->getRepository()->willReturn($groupRepository);
        $groupRepository->getVariantGroupIds()->willReturn([1]);

        $channelManager->getChannelByCode('magento')->willReturn($channel);
        $channel->getId()->willReturn(3);

        $group->getId()->willReturn(1);
        $group->getProducts()->willReturn([$product1, $product2, $product3]);
        $group->getCode()->willReturn('groupCode');

        $product1->getGroups()->willReturn([$group]);
        $product1->getId()->willReturn(10);
        $product1->getFamily()->willReturn($family);
        $product2->getId()->willReturn(11);
        $product2->getFamily()->willReturn($family);
        $product3->getId()->willReturn(12);
        $product3->getFamily()->willReturn($family);

        $family->getCode()->willReturn('familyCode');

        $productFilter
            ->apply($channel, [$product1, $product2, $product3])
            ->willReturn([$product1, $product2, $product3]);

        $configurableNormalizer->normalize(Argument::cetera())->willReturn(['bar']);

        $this->setChannel('magento');
        $this->process([$product1])->shouldReturn([['bar']]);
    }

    function it_processes_a_product_once_to_update_a_configurable_product_in_magento(
        $clientParametersRegistry,
        $normalizerGuesser,
        $groupManager,
        $channelManager,
        $productFilter,
        Group $group,
        Channel $channel,
        ProductInterface $product1,
        ProductInterface $product2,
        ProductInterface $product3,
        MagentoSoapClientParameters $clientParameters,
        WebserviceGuesser $webserviceGuesser,
        Webservice $webservice,
        ProductNormalizer $productNormalizer,
        ConfigurableNormalizer $configurableNormalizer,
        GroupRepository $groupRepository,
        StepExecution $stepExecution,
        EventDispatcher $eventDispatcher
    ) {
        $clientParametersRegistry->getInstance(Argument::cetera())->willReturn($clientParameters);
        $webserviceGuesser->getWebservice($clientParameters)->willReturn($webservice);
        $normalizerGuesser->getProductNormalizer(Argument::cetera())->willReturn($productNormalizer);
        $normalizerGuesser->getConfigurableNormalizer(Argument::cetera())->willReturn($configurableNormalizer);

        $webservice->getStoreViewsList()->willReturn([
            [
                'store_id'   => '1',
                'code'       => 'default',
                'website_id' => '1',
                'group_id'   => '1',
                'name'       => 'Default Store View',
                'sort_order' => '0',
                'is_active'  => '1',
            ],
        ]);
        $webservice->getAllAttributes()->willReturn([
            'name' => [
                'attribute_id' => '71',
                'code'         => 'name',
                'type'         => 'text',
                'required'     => '1',
                'scope'        => 'store',
            ],
        ]);
        $webservice->getAllAttributesOptions()->willReturn([]);
        $webservice
            ->getConfigurablesStatus([1 => ['group' => $group, 'products' => [$product1, $product2, $product3]]])
            ->willReturn([
                [
                    'product_id' => '6',
                    'sku' => 'conf-groupCode',
                    'name' => 'foo',
                    'set' => '3',
                    'type' => 'configurable',
                    'category_ids' => ['7'],
                    'website_ids' => ['1']
                ]
            ]);
        $webservice
            ->getConfigurablesStatus([1 => ['group' => $group, 'products' => []]])
            ->willReturn([
                [
                    'product_id' => '6',
                    'sku' => 'conf-groupCode',
                    'name' => 'foo',
                    'set' => '3',
                    'type' => 'configurable',
                    'category_ids' => ['7'],
                    'website_ids' => ['1']
                ]
            ]);

        $groupManager->getRepository()->willReturn($groupRepository);
        $groupRepository->getVariantGroupIds()->willReturn([1]);

        $channelManager->getChannelByCode('magento')->willReturn($channel);
        $channel->getId()->willReturn(3);

        $group->getId()->willReturn(1);
        $group->getProducts()->willReturn([$product1, $product2, $product3]);
        $group->getCode()->willReturn('groupCode');

        $product1->getGroups()->willReturn([$group]);
        $product1->getId()->willReturn(10);

        $product2->getGroups()->willReturn([$group]);
        $product2->getId()->willReturn(11);
        $product3->getId()->willReturn(12);

        $productFilter
            ->apply($channel, [$product1, $product2, $product3])
            ->willReturn([$product1, $product2, $product3]);

        $configurableNormalizer->normalize(Argument::cetera())->willReturn(['normalizedConfigurable']);

        $this->setChannel('magento');
        $this->setStepExecution($stepExecution);
        $this->setEventDispatcher($eventDispatcher);
        $this->process([$product1])->shouldReturn([['normalizedConfigurable']]);
        $this->process([$product2])->shouldReturn([]);
    }

    function it_associates_only_complete_products_to_configurable(
        $clientParametersRegistry,
        $normalizerGuesser,
        $groupManager,
        $channelManager,
        $productFilter,
        Group $group,
        Channel $channel,
        ProductInterface $product1,
        ProductInterface $product2,
        ProductInterface $product3,
        MagentoSoapClientParameters $clientParameters,
        WebserviceGuesser $webserviceGuesser,
        Webservice $webservice,
        ProductNormalizer $productNormalizer,
        ConfigurableNormalizer $configurableNormalizer,
        GroupRepository $groupRepository,
        StepExecution $stepExecution,
        EventDispatcher $eventDispatcher
    ) {
        $clientParametersRegistry->getInstance(Argument::cetera())->willReturn($clientParameters);
        $webserviceGuesser->getWebservice($clientParameters)->willReturn($webservice);
        $normalizerGuesser->getProductNormalizer(Argument::cetera())->willReturn($productNormalizer);
        $normalizerGuesser->getConfigurableNormalizer(Argument::cetera())->willReturn($configurableNormalizer);

        $webservice->getStoreViewsList()->willReturn([
            [
                'store_id'   => '1',
                'code'       => 'default',
                'website_id' => '1',
                'group_id'   => '1',
                'name'       => 'Default Store View',
                'sort_order' => '0',
                'is_active'  => '1',
            ],
        ]);
        $webservice->getAllAttributes()->willReturn([
            'name' => [
                'attribute_id' => '71',
                'code'         => 'name',
                'type'         => 'text',
                'required'     => '1',
                'scope'        => 'store',
            ],
        ]);
        $webservice->getAllAttributesOptions()->willReturn([]);
        $webservice
            ->getConfigurablesStatus([1 => ['group' => $group, 'products' => [$product1, $product2]]])
            ->willReturn([
                [
                    'product_id' => '6',
                    'sku' => 'conf-groupCode',
                    'name' => 'foo',
                    'set' => '3',
                    'type' => 'configurable',
                    'category_ids' => ['7'],
                    'website_ids' => ['1']
                ]
            ]);

        $groupManager->getRepository()->willReturn($groupRepository);
        $groupRepository->getVariantGroupIds()->willReturn([1]);

        $channelManager->getChannelByCode('magento')->willReturn($channel);
        $channel->getId()->willReturn(3);

        $group->getId()->willReturn(1);
        $group->getProducts()->willReturn([$product1, $product2, $product3]);
        $group->getCode()->willReturn('groupCode');

        $product1->getGroups()->willReturn([$group]);
        $product1->getId()->willReturn(10);
        $product2->getId()->willReturn(11);
        $product3->getId()->willReturn(12);

        $productFilter
            ->apply($channel, [$product1, $product2, $product3])
            ->willReturn([$product1, $product2]);

        $configurableNormalizer
            ->normalize(
                ['group' => $group, 'products' => [$product1, $product2]],
                'MagentoArray',
                Argument::type('array')
            )
            ->shouldBeCalled();

        $this->setStepExecution($stepExecution);
        $this->setEventDispatcher($eventDispatcher);
        $this->setChannel('magento');
        $this->process([$product1]);
    }

    function it_associates_only_products_in_channel_to_configurable(
        $clientParametersRegistry,
        $normalizerGuesser,
        $groupManager,
        $channelManager,
        $productFilter,
        Group $group,
        Channel $channel,
        ProductInterface $product1,
        ProductInterface $product2,
        ProductInterface $product3,
        MagentoSoapClientParameters $clientParameters,
        WebserviceGuesser $webserviceGuesser,
        Webservice $webservice,
        ProductNormalizer $productNormalizer,
        ConfigurableNormalizer $configurableNormalizer,
        GroupRepository $groupRepository,
        StepExecution $stepExecution,
        EventDispatcher $eventDispatcher
    ) {
        $clientParametersRegistry->getInstance(Argument::cetera())->willReturn($clientParameters);
        $webserviceGuesser->getWebservice($clientParameters)->willReturn($webservice);
        $normalizerGuesser->getProductNormalizer(Argument::cetera())->willReturn($productNormalizer);
        $normalizerGuesser->getConfigurableNormalizer(Argument::cetera())->willReturn($configurableNormalizer);

        $webservice->getStoreViewsList()->willReturn([
            [
                'store_id'   => '1',
                'code'       => 'default',
                'website_id' => '1',
                'group_id'   => '1',
                'name'       => 'Default Store View',
                'sort_order' => '0',
                'is_active'  => '1',
            ],
        ]);
        $webservice->getAllAttributes()->willReturn([
            'name' => [
                'attribute_id' => '71',
                'code'         => 'name',
                'type'         => 'text',
                'required'     => '1',
                'scope'        => 'store',
            ],
        ]);
        $webservice->getAllAttributesOptions()->willReturn([]);
        $webservice
            ->getConfigurablesStatus([1 => ['group' => $group, 'products' => [$product1, $product2]]])
            ->willReturn([
                [
                    'product_id' => '6',
                    'sku' => 'conf-groupCode',
                    'name' => 'foo',
                    'set' => '3',
                    'type' => 'configurable',
                    'category_ids' => ['7'],
                    'website_ids' => ['1']
                ]
            ]);

        $groupManager->getRepository()->willReturn($groupRepository);
        $groupRepository->getVariantGroupIds()->willReturn([1]);

        $channelManager->getChannelByCode('magento')->willReturn($channel);
        $channel->getId()->willReturn(3);

        $group->getId()->willReturn(1);
        $group->getProducts()->willReturn([$product1, $product2, $product3]);
        $group->getCode()->willReturn('groupCode');

        $product1->getGroups()->willReturn([$group]);
        $product1->getId()->willReturn(10);
        $product2->getId()->willReturn(11);
        $product3->getId()->willReturn(12);

        $productFilter
            ->apply($channel, [$product1, $product2, $product3])
            ->willReturn([$product1, $product2]);

        $configurableNormalizer
            ->normalize(
                ['group' => $group, 'products' => [$product1, $product2]],
                'MagentoArray',
                Argument::type('array')
            )
            ->shouldBeCalled();

        $this->setStepExecution($stepExecution);
        $this->setEventDispatcher($eventDispatcher);
        $this->setChannel('magento');
        $this->process([$product1]);
    }

    function it_returns_configuration_fields(
        $associationTypeManager,
        $channelManager,
        $currencyManager,
        $attributeManager,
        $categoryMappingMerger,
        $attributeMappingMerger,
        $localeManager,
        $storeViewMappingMerger
    ) {
        $associationTypeChoices = [
            'X_SELL'  => 'Cross sell',
            'UPSELL'  => 'Upsell',
            'RELATED' => 'Related',
            'PACK'    => 'Pack'
        ];
        $channelChoice   = ['magento' => 'Magento'];
        $currencyChoices = ['EUR' => 'EUR', 'USD' => 'USD'];
        $imageChoices    = [];
        $localeChoices   = ['en_US' => 'en_US', 'fr_FR' => 'fr_FR'];
        $categoryMapping = [
            'categoryMapping' => [
                'type' => 'textarea',
                'options' => [
                    'required' => false,
                    'attr' => [
                        'class' => 'mapping-field',
                        'data-sources' => '{"sources":[]}',
                        'data-targets' => '{"targets":[],"allowAddition":false}',
                        'data-name' => 'category'
                    ],
                    'label' => 'pim_magento_connector.export.categoryMapping.label',
                    'help'  => 'pim_magento_connector.export.categoryMapping.help'
                ]
            ]
        ];
        $attributeMapping = [
            'attributeCodeMapping' => [
                'type' => 'textarea',
                'options' => [
                    'required' => false,
                    'attr' => [
                        'class' => 'mapping-field',
                        'data-sources' => '{"sources":[]}',
                        'data-targets' => '{"targets":[],"allowAddition":true}',
                        'data-name' => 'attributeCode'
                    ],
                    'label' => 'pim_magento_connector.export.attributeCodeMapping.label',
                    'help' => 'pim_magento_connector.export.attributeCodeMapping.help'
                ]
            ]
        ];
        $storeViewMapping = [
            'storeviewMapping' => [
                'type' => 'textarea',
                'options' => [
                    'required' => false,
                    'attr' => [
                        'class' => 'mapping-field',
                        'data-sources' => '{"sources":[]}',
                        'data-targets' => '{"targets":[],"allowAddition":true}',
                        'data-name' => 'storeview'
                    ],
                    'label' => 'pim_magento_connector.export.storeviewMapping.label',
                    'help' => 'pim_magento_connector.export.storeviewMapping.help'
                ]
            ]
        ];

        $associationTypeManager->getAssociationTypeChoices()->willReturn($associationTypeChoices);
        $channelManager->getChannelChoices()->willReturn($channelChoice);
        $currencyManager->getCurrencyChoices()->willReturn($currencyChoices);
        $attributeManager->getImageAttributeChoice()->willReturn($imageChoices);
        $localeManager->getLocaleChoices()->willReturn($localeChoices);
        $categoryMappingMerger->getConfigurationField()->willReturn($categoryMapping);
        $attributeMappingMerger->getConfigurationField()->willReturn($attributeMapping);
        $storeViewMappingMerger->getConfigurationField()->willReturn($storeViewMapping);

        $this->setWsdlUrl('wsdlUrl');
        $this->setDefaultStoreView('default');
        $this->getConfigurationFields()->shouldReturn(array_merge(
            [
                'soapUsername' => [
                    'options' => [
                        'required' => true,
                        'help'     => 'pim_magento_connector.export.soapUsername.help',
                        'label'    => 'pim_magento_connector.export.soapUsername.label',
                    ],
                ],
                'soapApiKey'   => [
                    'type'    => 'text',
                    'options' => [
                        'required' => true,
                        'help'     => 'pim_magento_connector.export.soapApiKey.help',
                        'label'    => 'pim_magento_connector.export.soapApiKey.label',
                    ],
                ],
                'magentoUrl' => [
                    'options' => [
                        'required' => true,
                        'help'     => 'pim_magento_connector.export.magentoUrl.help',
                        'label'    => 'pim_magento_connector.export.magentoUrl.label',
                    ],
                ],
                'wsdlUrl' => [
                    'options' => [
                        'required' => true,
                        'help'     => 'pim_magento_connector.export.wsdlUrl.help',
                        'label'    => 'pim_magento_connector.export.wsdlUrl.label',
                        'data'     => 'wsdlUrl',
                    ],
                ],
                'httpLogin' => [
                    'options' => [
                        'required' => false,
                        'help'     => 'pim_magento_connector.export.httpLogin.help',
                        'label'    => 'pim_magento_connector.export.httpLogin.label',
                    ],
                ],
                'httpPassword' => [
                    'options' => [
                        'required' => false,
                        'help'     => 'pim_magento_connector.export.httpPassword.help',
                        'label'    => 'pim_magento_connector.export.httpPassword.label',
                    ],
                ],
                'defaultStoreView' => [
                    'options' => [
                        'required' => false,
                        'help'     => 'pim_magento_connector.export.defaultStoreView.help',
                        'label'    => 'pim_magento_connector.export.defaultStoreView.label',
                        'data'     => 'default',
                    ],
                ]
            ],
            [
                'defaultLocale' => [
                    'type'    => 'choice',
                    'options' => [
                        'choices'  => $localeChoices,
                        'required' => true,
                        'attr' => [
                            'class' => 'select2',
                        ],
                        'help'  => 'pim_magento_connector.export.defaultLocale.help',
                        'label' => 'pim_magento_connector.export.defaultLocale.label',
                    ],
                ],
                'website' => [
                    'type'    => 'text',
                    'options' => [
                        'required' => true,
                        'help'  => 'pim_magento_connector.export.website.help',
                        'label' => 'pim_magento_connector.export.website.label',
                    ],
                ]
            ],
            $storeViewMapping,
            [
                'channel' => [
                    'type'    => 'choice',
                    'options' => [
                        'choices'  => $channelChoice,
                        'required' => true,
                        'help'     => 'pim_magento_connector.export.channel.help',
                        'label'    => 'pim_magento_connector.export.channel.label',
                    ],
                ],
                'enabled' => [
                    'type'    => 'switch',
                    'options' => [
                        'required' => true,
                        'help'     => 'pim_magento_connector.export.enabled.help',
                        'label'    => 'pim_magento_connector.export.enabled.label',
                    ],
                ],
                'visibility' => [
                    'type'    => 'text',
                    'options' => [
                        'required' => true,
                        'help'     => 'pim_magento_connector.export.visibility.help',
                        'label'    => 'pim_magento_connector.export.visibility.label',
                    ],
                ],
                'variantMemberVisibility' => [
                    'type'    => 'text',
                    'options' => [
                        'required' => true,
                        'help'     => 'pim_magento_connector.export.variant_member_visibility.help',
                        'label'    => 'pim_magento_connector.export.variant_member_visibility.label',
                    ],
                ],
                'currency' => [
                    'type'    => 'choice',
                    'options' => [
                        'choices'  => $currencyChoices,
                        'required' => true,
                        'help'     => 'pim_magento_connector.export.currency.help',
                        'label'    => 'pim_magento_connector.export.currency.label',
                        'attr' => [
                            'class' => 'select2',
                        ],
                    ],
                ],
                'smallImageAttribute' => [
                    'type' => 'choice',
                    'options' => [
                        'choices' => $imageChoices,
                        'help'    => 'pim_magento_connector.export.smallImageAttribute.help',
                        'label'   => 'pim_magento_connector.export.smallImageAttribute.label',
                        'attr' => [
                            'class' => 'select2',
                        ],
                    ],
                ],
                'baseImageAttribute' => [
                    'type' => 'choice',
                    'options' => [
                        'choices' => $imageChoices,
                        'help'    => 'pim_magento_connector.export.baseImageAttribute.help',
                        'label'   => 'pim_magento_connector.export.baseImageAttribute.label',
                        'attr' => [
                            'class' => 'select2',
                        ],
                    ],
                ],
                'thumbnailAttribute' => [
                    'type' => 'choice',
                    'options' => [
                        'choices' => $imageChoices,
                        'help'    => 'pim_magento_connector.export.thumbnailAttribute.help',
                        'label'   => 'pim_magento_connector.export.thumbnailAttribute.label',
                        'attr' => [
                            'class' => 'select2',
                        ],
                    ],
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
                ]
            ],
            $categoryMapping,
            $attributeMapping,
            [
                'pimGrouped' => [
                    'type'    => 'choice',
                    'options' => [
                        'choices' => $associationTypeChoices,
                        'help'    => 'pim_magento_connector.export.pimGrouped.help',
                        'label'   => 'pim_magento_connector.export.pimGrouped.label',
                        'attr' => [
                            'class' => 'select2',
                        ],
                    ],
                ]
            ])
        );
    }
}
