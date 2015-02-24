<?php

namespace spec\Pim\Bundle\MagentoConnectorBundle\Writer;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use PhpSpec\ObjectBehavior;
use Pim\Bundle\CatalogBundle\Entity\Family;
use Pim\Bundle\MagentoConnectorBundle\Guesser\WebserviceGuesser;
use Pim\Bundle\MagentoConnectorBundle\Manager\AttributeMappingManager;
use Pim\Bundle\MagentoConnectorBundle\Manager\FamilyMappingManager;
use Pim\Bundle\MagentoConnectorBundle\Webservice\MagentoSoapClientParameters;
use Pim\Bundle\MagentoConnectorBundle\Webservice\MagentoSoapClientParametersRegistry;
use Pim\Bundle\MagentoConnectorBundle\Webservice\Webservice;
use Prophecy\Argument;

class AttributeSetWriterSpec extends ObjectBehavior
{
    function let(
        WebserviceGuesser $webserviceGuesser,
        FamilyMappingManager $familyMappingManager,
        AttributeMappingManager $attributeMappingManager,
        Webservice $webservice,
        StepExecution $stepExecution,
        MagentoSoapClientParametersRegistry $clientParametersRegistry,
        MagentoSoapClientParameters $clientParameters
    ) {
        $clientParametersRegistry->getInstance(null, null, null, '/api/soap/?wsdl', 'default', null, null)->willReturn(
            $clientParameters
        );
        $webserviceGuesser->getWebservice($clientParameters)->willReturn($webservice);

        $this->beConstructedWith(
            $webserviceGuesser,
            $familyMappingManager,
            $attributeMappingManager,
            $clientParametersRegistry
        );
        $this->setStepExecution($stepExecution);
    }

    function it_sends_families_to_create_on_magento_webservice(
        Family $family,
        $webservice,
        FamilyMappingManager $familyMappingManager
    ) {
        $batches = [
            [
                'families_to_create' => [
                    'attributeSetName' => 'family_code',
                ],
                'family_object'      => $family,
            ],
        ];

        $webservice->createAttributeSet('family_code')->willReturn(12);
        $familyMappingManager->registerFamilyMapping($family, 12, '/api/soap/?wsdl')->shouldBeCalled();

        $this->setMagentoUrl(null);
        $this->setWsdlUrl('/api/soap/?wsdl');

        $this->write($batches);
    }

    function it_increments_summary_info_with_family_exists_if_it_exists(
        $webservice,
        $stepExecution,
        Family $family,
        FamilyMappingManager $familyMappingManager
    ) {
        $batches = [
            [
                'families_to_create' => [
                    'attributeSetName' => 'family_code',
                ],
                'family_object'      => $family,
            ],
        ];

        $webservice
            ->createAttributeSet('family_code')
            ->willThrow(
                '\Pim\Bundle\MagentoConnectorBundle\Webservice\SoapCallException'
            );
        $familyMappingManager->registerFamilyMapping(Argument::cetera())->shouldNotBeCalled();
        $stepExecution->incrementSummaryInfo('family_exists')->shouldBeCalled();

        $this->write($batches);
    }
}
