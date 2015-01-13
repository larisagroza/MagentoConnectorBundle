<?php

namespace spec\Pim\Bundle\MagentoConnectorBundle\Writer;

use Pim\Bundle\MagentoConnectorBundle\Guesser\WebserviceGuesser;
use Pim\Bundle\MagentoConnectorBundle\Webservice\MagentoSoapClientParameters;
use Pim\Bundle\MagentoConnectorBundle\Webservice\MagentoSoapClientParametersRegistry;
use Pim\Bundle\MagentoConnectorBundle\Webservice\Webservice;
use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class OptionWriterSpec extends ObjectBehavior
{
    function let(
        WebserviceGuesser $webserviceGuesser,
        Webservice $webservice,
        StepExecution $stepExecution,
        MagentoSoapClientParametersRegistry $clientParametersRegistry,
        MagentoSoapClientParameters $clientParameters
    ) {
        $clientParametersRegistry->getInstance(null, null, null, '/api/soap/?wsdl', 'default', null, null)->willReturn($clientParameters);
        $webserviceGuesser->getWebservice($clientParameters)->willReturn($webservice);

        $this->beConstructedWith($webserviceGuesser, $clientParametersRegistry);
        $this->setStepExecution($stepExecution);
    }

    function it_calls_soap_client_to_create_options($webservice)
    {
        $webservice->createOption(['foo'])->shouldBeCalled();
        $webservice->createOption(['bar'])->shouldBeCalled();

        $this->write([[['foo'], ['bar']]]);
    }

    function it_fails_if_something_went_wrong_during_create_option_call($webservice, $stepExecution)
    {
        $webservice->createOption(['foo'])->willThrow('\Pim\Bundle\MagentoConnectorBundle\Webservice\SoapCallException');
        $stepExecution->incrementSummaryInfo(Argument::any())->shouldNotBeCalled();

        $this->shouldThrow('\Akeneo\Bundle\BatchBundle\Item\InvalidItemException')->duringWrite([[['foo'], ['bar']]]);
    }
}
