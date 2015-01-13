<?php

namespace spec\Pim\Bundle\MagentoConnectorBundle\Mapper;

use PhpSpec\ObjectBehavior;
use Pim\Bundle\CatalogBundle\Model\AbstractAttribute;
use Pim\Bundle\MagentoConnectorBundle\Mapper\MappingCollection;
use Pim\Bundle\MagentoConnectorBundle\Entity\MagentoAttributeMapping;
use Pim\Bundle\MagentoConnectorBundle\Manager\AttributeMappingManager;
use Pim\Bundle\MagentoConnectorBundle\Merger\MagentoMappingMerger;
use Pim\Bundle\MagentoConnectorBundle\Validator\Constraints\HasValidCredentialsValidator;
use Pim\Bundle\MagentoConnectorBundle\Webservice\MagentoSoapClientParameters;
use Prophecy\Argument;

class ORMExportedAttributeMapperSpec extends ObjectBehavior
{
    protected $clientParameters;

    function let(
        HasValidCredentialsValidator $hasValidCredentialsValidator,
        AttributeMappingManager $attributeMappingManager,
        MagentoSoapClientParameters $clientParameters,
        MagentoMappingMerger $magentoAttributeMappingMerger,
        MagentoAttributeMapping $attributeMapping,
        AbstractAttribute $attribute
    ) {
        $this->beConstructedWith($hasValidCredentialsValidator, $attributeMappingManager, $magentoAttributeMappingMerger, 'generic');
        $this->setParameters($clientParameters, '');

        $hasValidCredentialsValidator->areValidSoapCredentials(Argument::any())->willReturn(true);
        $clientParameters->getSoapUrl()->willReturn('http://test.dev/api');
        $attributeMapping->getMagentoAttributeId()->willReturn(12);
        $attributeMapping->getAttribute()->willReturn($attribute);
        $attribute->getCode()->willReturn('attribute_code');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Pim\Bundle\MagentoConnectorBundle\Mapper\ORMExportedAttributeMapper');
    }

    function it_extends_mapper()
    {
        $this->shouldBeAnInstanceOf('Pim\Bundle\MagentoConnectorBundle\Mapper\Mapper');
    }

    function it_returns_a_mapping_collection_on_get_mapping(AttributeMappingManager $attributeMappingManager)
    {
        $attributeMappingManager->getAllMagentoAttribute('http://test.dev/api')->willReturn([]);
        $this->getMapping()->shouldReturnAnInstanceOf('Pim\Bundle\MagentoConnectorBundle\Mapper\MappingCollection');
    }

    function it_gets_mapping_from_exported_attributes_table(
        $attributeMappingManager,
        MappingCollection $mapping,
        $attributeMapping,
        $magentoAttributeMappingMerger
    ) {
        $attributeMappingManager->getAllMagentoAttribute('http://test.dev/api')->willReturn([$attributeMapping]);

        $magentoAttributeMappingMerger->getMapping()->willReturn($mapping);
        $mapping->getTarget('attribute_code')->willReturn('attribute_code');

        $this->getMapping()->shouldBeAnInstanceOf('Pim\Bundle\MagentoConnectorBundle\Mapper\MappingCollection');
        $mappingCollection = $this->getMapping();

        $mappingCollection->getSource(12)->shouldReturn('attribute_code');
        $mappingCollection->getTarget('attribute_code')->shouldReturn(12);
    }

    function it_gets_mapping_from_exported_attributes_table_with_a_mapped_attribute(
        $attributeMappingManager,
        MappingCollection $mapping,
        $attributeMapping,
        $magentoAttributeMappingMerger
    ) {
        $attributeMappingManager->getAllMagentoAttribute('http://test.dev/api')->willReturn([$attributeMapping]);

        $magentoAttributeMappingMerger->getMapping()->willReturn($mapping);
        $mapping->getTarget('attribute_code')->willReturn('attribute_code_mapped');

        $this->getMapping()->shouldBeAnInstanceOf('Pim\Bundle\MagentoConnectorBundle\Mapper\MappingCollection');
        $mappingCollection = $this->getMapping();

        $mappingCollection->getSource(12)->shouldReturn('attribute_code_mapped');
        $mappingCollection->getTarget('attribute_code_mapped')->shouldReturn(12);
    }
}
