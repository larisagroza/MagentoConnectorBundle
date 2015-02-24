<?php

namespace spec\Pim\Bundle\MagentoConnectorBundle\Manager;

use Pim\Bundle\CatalogBundle\Manager\CurrencyManager as BaseCurrencyManager;
use Pim\Bundle\CatalogBundle\Entity\Currency;
use Pim\Bundle\CatalogBundle\Entity\Repository\CurrencyRepository;
use PhpSpec\ObjectBehavior;

class CurrencyManagerSpec extends ObjectBehavior
{
    function let(BaseCurrencyManager $baseCurrencyManager)
    {
        $this->beConstructedWith($baseCurrencyManager);
    }

    function it_gives_currency_choices($baseCurrencyManager)
    {
        $baseCurrencyManager->getActiveCodes()->willReturn(['eur' => 'EUR']);

        $this->getCurrencyChoices()->shouldReturn(['EUR' => 'EUR']);
    }

    function it_returns_active_code_choices($baseCurrencyManager)
    {
        $baseCurrencyManager->getActiveCodes()->willReturn(['eur' => 'EUR']);

        $this->getActiveCodeChoices()->shouldReturn(['EUR' => 'EUR']);
    }

    function it_returns_empty_array_when_active_code_choices_not_found(
        CurrencyRepository $currencyRepository,
        Currency $currency
    ) {
        $currencyRepository->findBy(['activated' => true])->willReturn([]);
        $currency->getCode()->willReturn([]);

        $this->getActiveCodeChoices()->shouldReturn([]);
    }
}
