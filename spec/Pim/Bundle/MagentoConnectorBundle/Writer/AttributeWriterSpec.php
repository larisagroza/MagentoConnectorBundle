<?php

namespace spec\Pim\Bundle\MagentoConnectorBundle\Writer;

use Pim\Bundle\CatalogBundle\Entity\AttributeGroup;
use Pim\Bundle\CatalogBundle\Entity\Family;
use Pim\Bundle\MagentoConnectorBundle\Mapper\MappingCollection;
use Pim\Bundle\MagentoConnectorBundle\Guesser\WebserviceGuesser;
use Pim\Bundle\MagentoConnectorBundle\Manager\AttributeMappingManager;
use Pim\Bundle\MagentoConnectorBundle\Manager\FamilyMappingManager;
use Pim\Bundle\MagentoConnectorBundle\Manager\AttributeGroupMappingManager;
use Pim\Bundle\MagentoConnectorBundle\Merger\MagentoMappingMerger;
use Pim\Bundle\MagentoConnectorBundle\Webservice\Webservice;
use Pim\Bundle\MagentoConnectorBundle\Webservice\MagentoSoapClientParameters;
use Pim\Bundle\MagentoConnectorBundle\Webservice\MagentoSoapClientParametersRegistry;
use Pim\Bundle\CatalogBundle\Model\AbstractAttribute;
use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class AttributeWriterSpec extends ObjectBehavior
{
    function let(
        WebserviceGuesser $webserviceGuesser,
        FamilyMappingManager $familyMappingManager,
        AttributeMappingManager $attributeMappingManager,
        AttributeGroupMappingManager $attributeGroupMappingManager,
        Webservice $webservice,
        StepExecution $stepExecution,
        MagentoMappingMerger $magentoMappingMerger,
        MagentoSoapClientParametersRegistry $clientParametersRegistry,
        MagentoSoapClientParameters $clientParameters

    ) {
        $clientParametersRegistry->getInstance(null, null, null, '/api/soap/?wsdl', 'default', null, null)->willReturn($clientParameters);
        $webserviceGuesser->getWebservice($clientParameters)->willReturn($webservice);

        $this->beConstructedWith(
            $webserviceGuesser,
            $familyMappingManager,
            $attributeMappingManager,
            $attributeGroupMappingManager,
            $magentoMappingMerger,
            $clientParametersRegistry
        );
        $this->setStepExecution($stepExecution);
    }

    function it_sends_attribute_to_create_on_magento_webservice(
        $webservice,
        AbstractAttribute $attribute,
        AttributeMappingManager $attributeMappingManager,
        $magentoMappingMerger,
        MappingCollection $mapping
    ) {
        $attributes = [
            [
                $attribute,
                [
                    'create' => [
                        'attributeName' => 'attribute_code',
                    ],
                ],
            ],
        ];
        $this->setMagentoUrl(null);
        $this->setWsdlUrl('/api/soap/?wsdl');

        $attribute->getCode()->willReturn('attributeName');
        $attribute->getFamilies()->willReturn([]);
        $attribute->getGroup()->willReturn(null);

        $webservice->createAttribute(Argument::any())->willReturn(12);

        $magentoMappingMerger->getMapping()->willReturn($mapping);
        $mapping->getTarget(Argument::any())->willReturn(12);
        $mapping->getSource(12)->willReturn(12);

        $attributeMappingManager->registerAttributeMapping($attribute, 12, '/api/soap/?wsdl')->shouldBeCalled();

        $this->write($attributes);
    }

    function it_sends_attribute_with_group_and_family_to_create_on_magento_webservice(
        $webservice,
        AbstractAttribute $attribute,
        AttributeMappingManager $attributeMappingManager,
        AttributeGroupMappingManager $attributeGroupMappingManager,
        FamilyMappingManager $familyMappingManager,
        AttributeGroup $group,
        Family $family,
        $magentoMappingMerger,
        MappingCollection $mapping
    ) {
        $attributes = [
            [
                $attribute,
                [
                    'create' => [
                        'attributeName' => 'attribute_code',
                    ],
                ],
            ],
        ];

        $this->setMagentoUrl(null);
        $this->setWsdlUrl('/api/soap/?wsdl');

        $attribute->getCode()->willReturn('attributeName');
        $attribute->getFamilies()->willReturn([$family]);
        $attribute->getGroup()->willReturn($group);

        $magentoMappingMerger->getMapping()->willReturn($mapping);
        $mapping->getTarget($attribute)->willReturn(12);
        $mapping->getSource(12)->willReturn(12);

        $group->getCode()->willReturn('group_name');

        $familyMappingManager->getIdFromFamily(Argument::any(), '/api/soap/?wsdl')->willReturn(414);

        $webservice->addAttributeGroupToAttributeSet(414, 'group_name')->shouldBeCalled()->willReturn(797);
        $webservice->createAttribute(Argument::any())->willReturn(12);
        $webservice->addAttributeToAttributeSet(12, 414, 797)->shouldBeCalled();

        $attributeGroupMappingManager->registerGroupMapping($group, $family, 797, '/api/soap/?wsdl')->shouldBeCalled();
        $attributeGroupMappingManager->getIdFromGroup($group, $family, '/api/soap/?wsdl')->willReturn(797);

        $attributeMappingManager->registerAttributeMapping($attribute, 12, '/api/soap/?wsdl')->shouldBeCalled();

        $this->write($attributes);
    }
}
