<?php

namespace spec\Pim\Bundle\MagentoConnectorBundle\Manager;

use Pim\Bundle\CatalogBundle\Manager\LocaleManager as BaseLocaleManager;
use PhpSpec\ObjectBehavior;

class LocaleManagerSpec extends ObjectBehavior
{
    function let(BaseLocaleManager $baseLocaleManager)
    {
        $this->beConstructedWith($baseLocaleManager);
    }

    function it_gives_locale_choices($baseLocaleManager)
    {
        $baseLocaleManager->getActiveCodes()->willReturn(['en_us' => 'en_US']);

        $this->getLocaleChoices()->shouldReturn(['en_US' => 'en_US']);
    }
}
