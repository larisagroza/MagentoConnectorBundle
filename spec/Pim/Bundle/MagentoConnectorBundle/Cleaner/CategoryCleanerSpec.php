<?php

namespace spec\Pim\Bundle\MagentoConnectorBundle\Cleaner;

use Pim\Bundle\MagentoConnectorBundle\Guesser\WebserviceGuesser;
use Pim\Bundle\MagentoConnectorBundle\Manager\CategoryMappingManager;
use Pim\Bundle\MagentoConnectorBundle\Webservice\MagentoSoapClientParametersRegistry;
use Pim\Bundle\MagentoConnectorBundle\Webservice\MagentoSoapClientParameters;
use Pim\Bundle\MagentoConnectorBundle\Webservice\Webservice;
use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class CategoryCleanerSpec extends ObjectBehavior
{
    function let(
        WebserviceGuesser $webserviceGuesser,
        CategoryMappingManager $categoryMappingManager,
        Webservice $webservice,
        StepExecution $stepExecution,
        MagentoSoapClientParametersRegistry $clientParametersRegistry,
        MagentoSoapClientParameters $clientParameters
    ) {
        $this->beConstructedWith($webserviceGuesser, $categoryMappingManager, $clientParametersRegistry);
        $this->setStepExecution($stepExecution);

        $clientParametersRegistry->getInstance(null, null, null, '/api/soap/?wsdl', 'default', null, null)->willReturn($clientParameters);
        $webserviceGuesser->getWebservice($clientParameters)->willReturn($webservice);
    }

    function it_asks_soap_client_to_delete_categories_that_are_not_in_pim_anymore($webservice, $categoryMappingManager)
    {
        $this->setNotInPimAnymoreAction('delete');

        $webservice->getCategoriesStatus()->willReturn(
            [
                ['category_id' => '1'],
                ['category_id' => '12', 'level' => '0'],
                ['category_id' => '13', 'level' => '2'],
            ]
        );

        $categoryMappingManager->magentoCategoryExists('1', Argument::cetera())->willReturn(true);
        $categoryMappingManager->magentoCategoryExists('12', Argument::cetera())->willReturn(false);
        $categoryMappingManager->magentoCategoryExists('13', Argument::cetera())->willReturn(false);

        $webservice->deleteCategory('13')->shouldBeCalled();

        $this->execute();
    }

    function it_asks_soap_client_to_disable_categories_that_are_not_in_pim_anymore($webservice, $categoryMappingManager)
    {
        $this->setNotInPimAnymoreAction('disable');

        $webservice->getCategoriesStatus()->willReturn(
            [
                ['category_id' => '13', 'level' => '2'],
            ]
        );

        $categoryMappingManager->magentoCategoryExists('13', Argument::cetera())->willReturn(false);

        $webservice->disableCategory('13')->shouldBeCalled();

        $this->execute();
    }

    function it_raises_invalid_item_exception_if_something_goes_wrong_with_the_soap_api($webservice, $categoryMappingManager)
    {
        $this->setNotInPimAnymoreAction('disable');

        $webservice->getCategoriesStatus()->willReturn(
            [
                ['category_id' => '13', 'level' => '2'],
            ]
        );

        $categoryMappingManager->magentoCategoryExists('13', Argument::cetera())->willReturn(false);

        $webservice->disableCategory('13')->willThrow('Pim\Bundle\MagentoConnectorBundle\Webservice\SoapCallException');

        $this->shouldThrow('Akeneo\Bundle\BatchBundle\Item\InvalidItemException')->during('execute');
    }

    function it_is_configurable_via_magento_item_step()
    {
        $this->setSoapUsername('soap');
        $this->getSoapUsername()->shouldReturn('soap');

        $this->setDefaultStoreView('default');
        $this->getDefaultStoreView()->shouldReturn('default');

        $this->setSoapApiKey('key');
        $this->getSoapApiKey()->shouldReturn('key');

        $this->setMagentoUrl('http://magento.url');
        $this->getMagentoUrl()->shouldReturn('http://magento.url');

        $this->setHttpLogin('login');
        $this->getHttpLogin()->shouldReturn('login');

        $this->setHttpPassword('passwd');
        $this->getHttpPassword()->shouldReturn('passwd');
    }
}
