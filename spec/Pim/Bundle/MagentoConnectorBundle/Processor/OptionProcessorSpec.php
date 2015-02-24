<?php

namespace spec\Pim\Bundle\MagentoConnectorBundle\Processor;

use Pim\Bundle\MagentoConnectorBundle\Manager\LocaleManager;
use Pim\Bundle\MagentoConnectorBundle\Merger\MagentoMappingMerger;
use Pim\Bundle\MagentoConnectorBundle\Mapper\MappingCollection;
use Pim\Bundle\MagentoConnectorBundle\Webservice\MagentoSoapClientParametersRegistry;
use Pim\Bundle\MagentoConnectorBundle\Webservice\MagentoSoapClientParameters;
use Pim\Bundle\MagentoConnectorBundle\Guesser\WebserviceGuesser;
use Pim\Bundle\MagentoConnectorBundle\Guesser\NormalizerGuesser;
use Pim\Bundle\MagentoConnectorBundle\Webservice\Webservice;
use Pim\Bundle\MagentoConnectorBundle\Normalizer\OptionNormalizer;
use Pim\Bundle\CatalogBundle\Entity\Attribute;
use Pim\Bundle\CatalogBundle\Entity\AttributeOption;
use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class OptionProcessorSpec extends ObjectBehavior
{
    function let(
        LocaleManager $localeManager,
        MagentoMappingMerger $storeViewMappingMerger,
        MagentoMappingMerger $attributeMappingMerger,
        MappingCollection $attributeMapping,
        WebserviceGuesser $webserviceGuesser,
        NormalizerGuesser $normalizerGuesser,
        Webservice $webservice,
        OptionNormalizer $optionNormalizer,
        StepExecution $stepExecution,
        MagentoSoapClientParametersRegistry $clientParametersRegistry,
        MagentoSoapClientParameters $clientParameters
    ) {
        $this->beConstructedWith(
            $webserviceGuesser,
            $normalizerGuesser,
            $localeManager,
            $storeViewMappingMerger,
            $attributeMappingMerger,
            $clientParametersRegistry
        );
        $this->setStepExecution($stepExecution);

        $clientParametersRegistry->getInstance(null, null, null, '/api/soap/?wsdl', 'default', null, null)->willReturn($clientParameters);
        $webserviceGuesser->getWebservice($clientParameters)->willReturn($webservice);

        $attributeMappingMerger->getMapping()->willReturn($attributeMapping);
        $attributeMapping->getTarget('color')->willReturn('color');
        $webserviceGuesser->getWebservice($clientParameters)->willReturn($webservice);
        $normalizerGuesser->getOptionNormalizer($clientParameters)->willReturn($optionNormalizer);
    }

    function it_normalizes_given_grouped_options(
        AttributeOption $optionRed,
        AttributeOption $optionBlue,
        Attribute $attribute,
        $optionNormalizer,
        $webservice
    ) {
        $optionRed->getAttribute()->willReturn($attribute);
        $attribute->getCode()->willReturn('color');

        $optionRed->getCode()->willReturn('red');
        $optionBlue->getCode()->willReturn('blue');

        $webservice->getStoreViewsList()->shouldBeCalled();
        $webservice->getAttributeOptions('color')->willReturn(['red']);

        $optionNormalizer->normalize($optionRed, Argument::cetera())->willReturn(['foo']);
        $optionNormalizer->normalize($optionBlue, Argument::cetera())->willReturn(['bar']);

        $this->process([
            $optionRed,
            $optionBlue,
        ])->shouldReturn([['foo'], ['bar']]);
    }

    function it_raises_an_exception_if_it_can_not_get_option_list_from_webservice(
        AttributeOption $optionRed,
        Attribute $attribute,
        $optionNormalizer,
        $webservice
    ) {
        $optionRed->getAttribute()->willReturn($attribute);
        $attribute->getCode()->willReturn('color');
        $attribute->getLabel()->willReturn('Color');
        $attribute->getAttributeType()->willReturn('pim_catalog_simpleselect');

        $optionRed->getCode()->willReturn('red');

        $webservice->getStoreViewsList()->shouldBeCalled();
        $webservice->getAttributeOptions('color')->willThrow('Pim\Bundle\MagentoConnectorBundle\Webservice\SoapCallException');

        $optionNormalizer->normalize($optionRed, Argument::cetera())->willReturn(['foo']);

        $this->shouldThrow('Akeneo\Bundle\BatchBundle\Item\InvalidItemException')->during('process', [[$optionRed]]);
    }

    function it_raises_an_exception_if_a_error_occure_during_normalization_process(
        AttributeOption $optionRed,
        Attribute $attribute,
        $optionNormalizer,
        $webservice
    ) {
        $optionRed->getAttribute()->willReturn($attribute);
        $attribute->getCode()->willReturn('color');

        $optionRed->getCode()->willReturn('red');

        $webservice->getStoreViewsList()->shouldBeCalled();
        $webservice->getAttributeOptions('color')->willReturn(['red']);

        $optionNormalizer->normalize($optionRed, Argument::cetera())->willThrow('Pim\Bundle\MagentoConnectorBundle\Normalizer\Exception\NormalizeException');

        $this->shouldThrow('Akeneo\Bundle\BatchBundle\Item\InvalidItemException')->during('process', [[$optionRed]]);
    }

    function it_gives_a_proper_configuration_for_fields($storeViewMappingMerger, $attributeMappingMerger)
    {
        $storeViewMappingMerger->getConfigurationField()->willReturn(['fooo' => 'baar']);
        $attributeMappingMerger->getConfigurationField()->willReturn(['foo' => 'bar']);

        $this->getConfigurationFields()->shouldReturn([
            'soapUsername' => [
                'options' => [
                    'required' => true,
                    'help'     => 'pim_magento_connector.export.soapUsername.help',
                    'label'    => 'pim_magento_connector.export.soapUsername.label',
                ],
            ],
            'soapApiKey'   => [
                //Should be remplaced by a password formType but who doesn't
                //empty the field at each edit
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
                    'data'     => MagentoSoapClientParameters::SOAP_WSDL_URL,
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
                    'data'     => $this->getDefaultStoreView(),
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
