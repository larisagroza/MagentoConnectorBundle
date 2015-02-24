<?php

namespace spec\Pim\Bundle\MagentoConnectorBundle\Mapper;

use Pim\Bundle\MagentoConnectorBundle\Webservice\MagentoSoapClientParametersRegistry;
use Pim\Bundle\MagentoConnectorBundle\Webservice\MagentoSoapClientParameters;
use Pim\Bundle\MagentoConnectorBundle\Manager\SimpleMappingManager;
use Pim\Bundle\MagentoConnectorBundle\Validator\Constraints\HasValidCredentialsValidator;
use Pim\Bundle\MagentoConnectorBundle\Manager\LocaleManager;
use Pim\Bundle\CatalogBundle\Entity\Locale;
use PhpSpec\ObjectBehavior;

class ORMStoreViewMapperSpec extends ObjectBehavior
{
    protected $clientParameters;

    function let(
        HasValidCredentialsValidator $hasValidCredentialsValidator,
        SimpleMappingManager $simpleMappingManager,
        LocaleManager $localeManager,
        MagentoSoapClientParametersRegistry $clientParametersRegistry,
        MagentoSoapClientParameters $clientParameters
    ) {
        $this->beConstructedWith($hasValidCredentialsValidator, $simpleMappingManager, 'storeview', $localeManager);

        $clientParametersRegistry->getInstance(null, null, null, '/api/soap/?wsdl', 'default', null, null)->willReturn($clientParameters);

        $this->setParameters($clientParameters, '');
    }

    function it_returns_all_locales_from_database_as_sources(
            Locale $locale,
            $localeManager,
            $hasValidCredentialsValidator,
            $clientParameters
    ) {
        $hasValidCredentialsValidator->areValidSoapCredentials($clientParameters)->willReturn(true);

        $localeManager->getActiveCodes()->willReturn(['foo']);

        $locale->getCode()->willReturn('foo');

        $this->getAllSources()->shouldReturn([['id' => 'foo', 'text' => 'foo']]);
    }

    function it_returns_nothing_as_sources_if_it_is_not_well_configured($localeManager)
    {
        $localeManager->getActiveCodes()->willReturn([]);
        $this->getAllSources()->shouldReturn([]);
    }
}
