<?php

namespace spec\Pim\Bundle\MagentoConnectorBundle\Processor;

use Akeneo\Bundle\BatchBundle\Item\InvalidItemException;
use PhpSpec\ObjectBehavior;
use Pim\Bundle\CatalogBundle\Entity\Attribute;
use Pim\Bundle\MagentoConnectorBundle\Entity\Repository\GroupRepository;
use Pim\Bundle\MagentoConnectorBundle\Guesser\NormalizerGuesser;
use Pim\Bundle\MagentoConnectorBundle\Guesser\WebserviceGuesser;
use Pim\Bundle\MagentoConnectorBundle\Manager\GroupManager;
use Pim\Bundle\MagentoConnectorBundle\Manager\LocaleManager;
use Pim\Bundle\MagentoConnectorBundle\Mapper\MappingCollection;
use Pim\Bundle\MagentoConnectorBundle\Merger\MagentoMappingMerger;
use Pim\Bundle\MagentoConnectorBundle\Normalizer\AttributeNormalizer;
use Pim\Bundle\MagentoConnectorBundle\Webservice\MagentoSoapClientParameters;
use Pim\Bundle\MagentoConnectorBundle\Webservice\MagentoSoapClientParametersRegistry;
use Pim\Bundle\MagentoConnectorBundle\Webservice\Webservice;
use Prophecy\Argument;

class AttributeProcessorSpec extends ObjectBehavior
{
    function let(
        GroupManager $groupManager,
        LocaleManager $localeManager,
        MagentoMappingMerger $storeViewMappingMerger,
        MagentoMappingMerger $attributeMappingMerger,
        MagentoSoapClientParametersRegistry $clientParametersRegistry,
        NormalizerGuesser $normalizerGuesser,
        WebserviceGuesser $webserviceGuesser
    ) {
        $this->beConstructedWith(
            $webserviceGuesser,
            $normalizerGuesser,
            $localeManager,
            $storeViewMappingMerger,
            $attributeMappingMerger,
            $clientParametersRegistry,
            $groupManager
        );
    }

    function it_is_configurable()
    {
        $this->setDefaultLocale('en_US');
        $this->setWebsite('my_web_site');

        $this->getDefaultLocale()->shouldReturn('en_US');
        $this->getWebsite()->shouldReturn('my_web_site');

        $this->setDefaultLocale('fr_FR');
        $this->setWebsite('mon_site_internet');

        $this->getDefaultLocale()->shouldReturn('fr_FR');
        $this->getWebsite()->shouldReturn('mon_site_internet');
    }

    function it_sets_a_store_view_mapping(
        $clientParametersRegistry,
        $storeViewMappingMerger,
        MagentoSoapClientParameters $clientParameters,
        MappingCollection $collection
    ) {
        $clientParametersRegistry->getInstance(Argument::cetera())->willReturn($clientParameters);

        $storeViewMappingMerger->setParameters($clientParameters, Argument::cetera())->shouldBeCalled();

        $storeViewMappingMerger->setMapping(
            json_decode('{"en_US":{"source":"en_US","target":"en_us"}}', true)
        )->shouldBeCalled();

        $storeViewMappingMerger->getMapping()->willReturn($collection);
        $collection->toArray()->willReturn(
            [
                'en_US' => [
                    'source' => 'en_US',
                    'target' => 'en_us',
                    'deletable' => true,
                ]
            ]
        );

        $this->setStoreviewMapping('{"en_US":{"source":"en_US","target":"en_us"}}')->shouldReturn($this);
    }

    function it_sets_an_attribute_code_mapping(
        $clientParametersRegistry,
        $attributeMappingMerger,
        MagentoSoapClientParameters $clientParameters,
        MappingCollection $collection
    ) {
        $clientParametersRegistry->getInstance(Argument::cetera())->willReturn($clientParameters);

        $attributeMappingMerger->setParameters($clientParameters, Argument::cetera())->shouldBeCalled();

        $attributeMappingMerger->setMapping(
            json_decode('{"Foo":{"source":"Foo","target":"foo"}}', true)
        )->shouldBeCalled();

        $attributeMappingMerger->getMapping()->willReturn($collection);
        $collection->toArray()->willReturn(
            [
                'Foo' => [
                    'source' => 'Foo',
                    'target' => 'foo',
                    'deletable' => true,
                ]
            ]
        );

        $this->setAttributeCodeMapping('{"Foo":{"source":"Foo","target":"foo"}}')->shouldReturn($this);
    }

    function it_processes_an_attribute(
        $attributeMappingMerger,
        $clientParametersRegistry,
        $groupManager,
        $normalizerGuesser,
        $storeViewMappingMerger,
        $webservice,
        $webserviceGuesser,
        Attribute $attribute,
        AttributeNormalizer $attributeNormalizer,
        GroupRepository $groupRepository,
        MagentoSoapClientParameters $clientParameters,
        MappingCollection $attributeMapping,
        MappingCollection $storeViewMapping,
        Webservice $webservice
    ) {
        $clientParametersRegistry->getInstance(Argument::cetera())->willReturn($clientParameters);

        $storeViewList = [
            0 => [
                'store_id' => '1',
                'code' => 'default',
                'website_id' => '1',
                'group_id' => '1',
                'name' => 'Default Store View',
                'sort_order' => '0',
                'is_active' => '1',
            ],
            1 => [
                'store_id' => '2',
                'code' => 'fr_fr',
                'website_id' => '1',
                'group_id' => '1',
                'name' => 'French Store View',
                'sort_order' => '0',
                'is_active' => '1',
            ],
        ];

        $normalizerGuesser->getAttributeNormalizer($clientParameters)->willReturn($attributeNormalizer);

        $webserviceGuesser->getWebservice($clientParameters)->willReturn($webservice);

        $attribute->getCode()->willReturn('attribute_code');

        $storeViewMappingMerger->getMapping()->willReturn($storeViewMapping);
        $webservice->getAllAttributes()->willReturn([]);
        $webservice->getAllAttributesOptions()->willReturn([]);
        $attributeMappingMerger->getMapping()->willReturn($attributeMapping);
        $webservice->getStoreViewsList()->willReturn($storeViewList);
        $groupManager->getRepository()->willReturn($groupRepository);
        $groupRepository->getAxisAttributes()->willReturn([['code' =>'configurable_attribute_code']]);

        $attributeMapping->getTarget('attribute_code')->willReturn('attribute_code_mapped');

        $context = [
            'defaultLocale'            => null,
            'storeViewMapping'         => $storeViewMapping,
            'defaultStoreView'         => 'default',
            'magentoAttributes'        => [],
            'magentoAttributesOptions' => [],
            'attributeCodeMapping'     => $attributeMapping,
            'magentoStoreViews'        => $storeViewList,
            'axisAttributes'           => ['configurable_attribute_code'],
            'create'                   => true,
        ];

        $attributeNormalizer->normalize($attribute, 'MagentoArray', $context)
            ->willReturn(
                [
                    'attribute_code_mapped',
                    [
                        'scope'                         => 'store',
                        'is_unique'                     => '0',
                        'is_required'                   => '0',
                        'apply_to'                      => '',
                        'is_configurable'               => '0',
                        'additional_fields'             => [],
                        'frontend_label'                => [['store_id' => 0, 'label' => 'attribute_code_mapped']],
                        'default_value'                 => '',
                    ],
                ]
            );

        $this->process($attribute)->shouldReturn(
            [
                $attribute,
                [
                    'attribute_code_mapped',
                    [
                        'scope'                         => 'store',
                        'is_unique'                     => '0',
                        'is_required'                   => '0',
                        'apply_to'                      => '',
                        'is_configurable'               => '0',
                        'additional_fields'             => [],
                        'frontend_label'                => [['store_id' => 0, 'label' => 'attribute_code_mapped']],
                        'default_value'                 => '',
                    ],
                ],
            ]
        );
    }

    function it_normalizes_an_attribute(
        $attributeMappingMerger,
        $clientParametersRegistry,
        $groupManager,
        $normalizerGuesser,
        $storeViewMappingMerger,
        $webservice,
        $webserviceGuesser,
        Attribute $attribute,
        AttributeNormalizer $attributeNormalizer,
        GroupRepository $groupRepository,
        MagentoSoapClientParameters $clientParameters,
        MappingCollection $attributeMapping,
        MappingCollection $storeViewMapping,
        Webservice $webservice
    ) {
        $clientParametersRegistry->getInstance(Argument::cetera())->willReturn($clientParameters);

        $storeViewList = [
            0 => [
                'store_id' => '1',
                'code' => 'default',
                'website_id' => '1',
                'group_id' => '1',
                'name' => 'Default Store View',
                'sort_order' => '0',
                'is_active' => '1',
            ],
            1 => [
                'store_id' => '2',
                'code' => 'fr_fr',
                'website_id' => '1',
                'group_id' => '1',
                'name' => 'French Store View',
                'sort_order' => '0',
                'is_active' => '1',
            ],
        ];

        $normalizerGuesser->getAttributeNormalizer($clientParameters)->willReturn($attributeNormalizer);

        $webserviceGuesser->getWebservice($clientParameters)->willReturn($webservice);

        $attribute->getCode()->willReturn('attribute_code');

        $storeViewMappingMerger->getMapping()->willReturn($storeViewMapping);
        $webservice->getAllAttributes()->willReturn([]);
        $webservice->getAllAttributesOptions()->willReturn([]);
        $attributeMappingMerger->getMapping()->willReturn($attributeMapping);
        $webservice->getStoreViewsList()->willReturn($storeViewList);
        $groupManager->getRepository()->willReturn($groupRepository);
        $groupRepository->getAxisAttributes()->willReturn([['code' =>'configurable_attribute_code']]);

        $attributeMapping->getTarget('attribute_code')->willReturn('attribute_code_mapped');

        $context = [
            'defaultLocale'            => null,
            'storeViewMapping'         => $storeViewMapping,
            'defaultStoreView'         => 'default',
            'magentoAttributes'        => [],
            'magentoAttributesOptions' => [],
            'attributeCodeMapping'     => $attributeMapping,
            'magentoStoreViews'        => $storeViewList,
            'axisAttributes'           => ['configurable_attribute_code'],
            'create'                   => true,
        ];

        $attributeNormalizer->normalize($attribute, 'MagentoArray', $context)->shouldBeCalled();

        $this->process($attribute);
    }

    function it_raises_an_exception_if_an_error_occures_during_normalization_process(
        $attributeMappingMerger,
        $clientParametersRegistry,
        $groupManager,
        $normalizerGuesser,
        $storeViewMappingMerger,
        $webservice,
        $webserviceGuesser,
        Attribute $attribute,
        AttributeNormalizer $attributeNormalizer,
        GroupRepository $groupRepository,
        MagentoSoapClientParameters $clientParameters,
        MappingCollection $attributeMapping,
        MappingCollection $storeViewMapping,
        Webservice $webservice
    ) {
        $clientParametersRegistry->getInstance(Argument::cetera())->willReturn($clientParameters);

        $storeViewList = [
            0 => [
                'store_id' => '1',
                'code' => 'default',
                'website_id' => '1',
                'group_id' => '1',
                'name' => 'Default Store View',
                'sort_order' => '0',
                'is_active' => '1',
            ],
            1 => [
                'store_id' => '2',
                'code' => 'fr_fr',
                'website_id' => '1',
                'group_id' => '1',
                'name' => 'French Store View',
                'sort_order' => '0',
                'is_active' => '1',
            ],
        ];

        $normalizerGuesser->getAttributeNormalizer($clientParameters)->willReturn($attributeNormalizer);

        $webserviceGuesser->getWebservice($clientParameters)->willReturn($webservice);

        $attribute->getCode()->willReturn('attribute_code');

        $storeViewMappingMerger->getMapping()->willReturn($storeViewMapping);
        $webservice->getAllAttributes()->willReturn([]);
        $webservice->getAllAttributesOptions()->willReturn([]);
        $attributeMappingMerger->getMapping()->willReturn($attributeMapping);
        $webservice->getStoreViewsList()->willReturn($storeViewList);
        $groupManager->getRepository()->willReturn($groupRepository);
        $groupRepository->getAxisAttributes()->willReturn([['code' =>'configurable_attribute_code']]);

        $attributeMapping->getTarget('attribute_code')->willReturn('attribute_code_mapped');

        $context = [
            'defaultLocale'            => null,
            'storeViewMapping'         => $storeViewMapping,
            'defaultStoreView'         => 'default',
            'magentoAttributes'        => [],
            'magentoAttributesOptions' => [],
            'attributeCodeMapping'     => $attributeMapping,
            'magentoStoreViews'        => $storeViewList,
            'axisAttributes'           => ['configurable_attribute_code'],
            'create'                   => true,
        ];

        $attributeNormalizer->normalize($attribute, 'MagentoArray', $context)->willThrow(
            new InvalidItemException('Something goes horribly wrong!', [[]])
        );

        $this
            ->shouldThrow(new InvalidItemException('Something goes horribly wrong!', [[]]))
            ->during('process', [$attribute]);
    }

    function it_gives_a_proper_configuration_for_fields($attributeMappingMerger, $storeViewMappingMerger)
    {
        $attributeMappingMerger->getConfigurationField()->willReturn(['foo' => 'bar']);
        $storeViewMappingMerger->getConfigurationField()->willReturn(['fooo' => 'baar']);
        $this->getConfigurationFields()->shouldReturn([
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
                    'data'     => '/api/soap/?wsdl',
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
            ],
            'defaultLocale' => [
                'type' => 'choice',
                'options' => [
                    'choices' => null,
                    'required' => true,
                    'attr' => ['class' => 'select2'],
                    'help'     => 'pim_magento_connector.export.defaultLocale.help',
                    'label'    => 'pim_magento_connector.export.defaultLocale.label',
                ],
            ],
            'website' => [
                'type' => 'text',
                'options' => [
                    'required' => true,
                    'help'     => 'pim_magento_connector.export.website.help',
                    'label'    => 'pim_magento_connector.export.website.label',
                ],
            ],
            'fooo' => 'baar',
            'foo' => 'bar',
        ]);
    }
}
