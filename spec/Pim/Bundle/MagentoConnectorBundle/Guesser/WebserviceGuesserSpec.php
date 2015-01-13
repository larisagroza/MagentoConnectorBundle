<?php

namespace spec\Pim\Bundle\MagentoConnectorBundle\Guesser;

use Pim\Bundle\MagentoConnectorBundle\Webservice\MagentoSoapClient;
use Pim\Bundle\MagentoConnectorBundle\Webservice\MagentoSoapClientFactory;
use Pim\Bundle\MagentoConnectorBundle\Webservice\MagentoSoapClientParameters;
use Pim\Bundle\MagentoConnectorBundle\Webservice\MagentoSoapClientParametersRegistry;
use PhpSpec\ObjectBehavior;

class WebserviceGuesserSpec extends ObjectBehavior
{
    function let(
        MagentoSoapClientFactory $magentoSoapClientFactory,
        MagentoSoapClientParametersRegistry $clientParametersRegistry,
        MagentoSoapClientParameters $clientParameters
    ) {
        $this->beConstructedWith($magentoSoapClientFactory);

        $clientParametersRegistry->getInstance('soap_username', 'soap_api_key', 'http://magento.url', '/api/soap/?wsdl', 'default', null, null)->willReturn($clientParameters);

        $clientParameters->getSoapUrl()->willReturn('http://magento.url/api/soap/?wsdl');
        $clientParameters->getSoapUsername()->willReturn('soap_username');
        $clientParameters->getSoapApiKey()->willReturn('soap_api_key');
    }

    function it_guesses_webservice_for_parameters(
        $magentoSoapClientFactory,
        $clientParameters,
        MagentoSoapClient $magentoSoapClient
    ) {
        $magentoSoapClientFactory->getMagentoSoapClient($clientParameters)->willReturn($magentoSoapClient);

        $magentoSoapClient->call('core_magento.info')->willReturn(['magento_version' => '1.8']);

        $this->getWebservice($clientParameters)->shouldBeAnInstanceOf('Pim\Bundle\MagentoConnectorBundle\Webservice\Webservice');
    }

    function it_guesses_an_old_webservice_for_parameters(
        $magentoSoapClientFactory,
        $clientParameters,
        MagentoSoapClient $magentoSoapClient
    ) {
        $magentoSoapClientFactory->getMagentoSoapClient($clientParameters)->willReturn($magentoSoapClient);

        $magentoSoapClient->call('core_magento.info')->willReturn(['magento_version' => '1.6']);

        $this->getWebservice($clientParameters)->shouldBeAnInstanceOf('Pim\Bundle\MagentoConnectorBundle\Webservice\Webservice16');
    }

    function it_raises_an_exception_if_the_version_number_is_not_well_formed(
        $magentoSoapClientFactory,
        $clientParameters,
        MagentoSoapClient $magentoSoapClient
    ) {
        $magentoSoapClientFactory->getMagentoSoapClient($clientParameters)->willReturn($magentoSoapClient);

        $magentoSoapClient->call('core_magento.info')->willReturn(['magento_version' => 'v1.6']);

        $this->shouldThrow('Pim\Bundle\MagentoConnectorBundle\Guesser\NotSupportedVersionException')->during('getWebservice', [$clientParameters]);
    }
}
