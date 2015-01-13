<?php

namespace spec\Pim\Bundle\MagentoConnectorBundle\Processor;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Pim\Bundle\CatalogBundle\Entity\Family;
use Pim\Bundle\MagentoConnectorBundle\Manager\LocaleManager;
use Pim\Bundle\MagentoConnectorBundle\Merger\MagentoMappingMerger;
use Pim\Bundle\MagentoConnectorBundle\Guesser\WebserviceGuesser;
use Pim\Bundle\MagentoConnectorBundle\Guesser\NormalizerGuesser;
use Pim\Bundle\MagentoConnectorBundle\Normalizer\AbstractNormalizer;
use Pim\Bundle\MagentoConnectorBundle\Normalizer\FamilyNormalizer;
use Pim\Bundle\MagentoConnectorBundle\Webservice\Webservice;
use Pim\Bundle\MagentoConnectorBundle\Webservice\MagentoSoapClientParameters;
use Pim\Bundle\MagentoConnectorBundle\Webservice\MagentoSoapClientParametersRegistry;
use Akeneo\Bundle\BatchBundle\Entity\StepExecution;

class FamilyProcessorSpec extends ObjectBehavior
{
    function let(
        LocaleManager $localeManager,
        MagentoMappingMerger $storeViewMappingMerger,
        WebserviceGuesser $webserviceGuesser,
        NormalizerGuesser $normalizerGuesser,
        Webservice $webservice,
        FamilyNormalizer $familyNormalizer,
        StepExecution $stepExecution,
        MagentoSoapClientParametersRegistry $clientParametersRegistry,
        MagentoSoapClientParameters $clientParameters
    ) {
        $this->beConstructedWith(
            $webserviceGuesser,
            $normalizerGuesser,
            $localeManager,
            $storeViewMappingMerger,
            $clientParametersRegistry
        );
        $this->setStepExecution($stepExecution);

        $clientParametersRegistry->getInstance(null, null, null, '/api/soap/?wsdl', 'default', null, null)->willReturn($clientParameters);
        $webserviceGuesser->getWebservice($clientParameters)->willReturn($webservice);

        $normalizerGuesser->getFamilyNormalizer($clientParameters)->willReturn($familyNormalizer);
    }

    function it_normalizes_families(
        Family $family,
        Webservice $webservice,
        $familyNormalizer
    ) {
        $familyNormalizer->normalize(
            $family,
            AbstractNormalizer::MAGENTO_FORMAT,
            Argument::any()
        )->willReturn([
            'attributeSetName' => 'family_code',
        ]);
        $webservice->getAttributeSetList()->willReturn([]);
        $webservice->getStoreViewsList()->willReturn([]);
        $this->process($family)->shouldReturn([
            'family_object'        => $family,
            'attributes_in_family' => null,
            'families_to_create'   => ['attributeSetName' => 'family_code'],
        ]);
    }
}
