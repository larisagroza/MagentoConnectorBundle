<?php

namespace spec\Pim\Bundle\MagentoConnectorBundle\Filter;

use PhpSpec\ObjectBehavior;
use Pim\Bundle\CatalogBundle\Entity\Channel;
use Pim\Bundle\CatalogBundle\Entity\Locale;
use Pim\Bundle\CatalogBundle\Model\AbstractCompleteness;
use Pim\Bundle\CatalogBundle\Model\ProductInterface;

class ExportableLocaleFilterSpec extends ObjectBehavior
{
    function it_sends_exportable_locales_from_a_product_and_a_channel(
        ProductInterface $product,
        Channel $channel,
        AbstractCompleteness $completeness1,
        AbstractCompleteness $completeness2,
        Locale $localeFr,
        Locale $localeEn
    ) {
        $product->getCompletenesses()->willReturn([$completeness1, $completeness2]);

        $completeness1->getRatio()->willReturn(100);
        $completeness2->getRatio()->willReturn(100);

        $completeness1->getChannel()->willReturn($channel);
        $completeness1->getLocale()->willReturn($localeEn);
        $completeness2->getChannel()->willReturn($channel);
        $completeness2->getLocale()->willReturn($localeFr);

        $channel->getId()->willReturn(2);

        $this->apply($product, $channel)->shouldReturn([$localeEn, $localeFr]);
    }

    function it_does_not_send_locales_which_are_not_in_channel(
        ProductInterface $product,
        Channel $channel,
        Channel $channel2,
        AbstractCompleteness $completeness1,
        AbstractCompleteness $completeness2,
        Locale $localeFr,
        Locale $localeEn
    ) {
        $product->getCompletenesses()->willReturn([$completeness1, $completeness2]);

        $completeness1->getRatio()->willReturn(100);
        $completeness2->getRatio()->willReturn(100);

        $completeness1->getChannel()->willReturn($channel);
        $completeness1->getLocale()->willReturn($localeEn);
        $completeness2->getChannel()->willReturn($channel2);
        $completeness2->getLocale()->willReturn($localeFr);

        $channel->getId()->willReturn(2);
        $channel2->getId()->willReturn(4);

        $this->apply($product, $channel)->shouldReturn([$localeEn]);
    }

    function it_does_not_send_locales_which_are_not_complete(
        ProductInterface $product,
        Channel $channel,
        AbstractCompleteness $completeness1,
        AbstractCompleteness $completeness2,
        Locale $localeFr,
        Locale $localeEn
    ) {
        $product->getCompletenesses()->willReturn([$completeness1, $completeness2]);

        $completeness1->getRatio()->willReturn(100);
        $completeness2->getRatio()->willReturn(90);

        $completeness1->getChannel()->willReturn($channel);
        $completeness1->getLocale()->willReturn($localeEn);
        $completeness2->getChannel()->willReturn($channel);
        $completeness2->getLocale()->willReturn($localeFr);

        $channel->getId()->willReturn(2);

        $this->apply($product, $channel)->shouldReturn([$localeEn]);
    }
}
