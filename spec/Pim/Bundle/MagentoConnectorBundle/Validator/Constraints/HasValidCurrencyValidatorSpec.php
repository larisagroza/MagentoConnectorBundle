<?php

namespace spec\Pim\Bundle\MagentoConnectorBundle\Validator\Constraints;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ExecutionContextInterface;
use Pim\Bundle\MagentoConnectorBundle\Processor\AbstractProductProcessor;
use Pim\Bundle\CatalogBundle\Manager\ChannelManager;
use Pim\Bundle\MagentoConnectorBundle\Validator\Constraints\HasValidCurrency;
use Pim\Bundle\CatalogBundle\Entity\Channel;
use Pim\Bundle\CatalogBundle\Entity\Currency;

/**
 * @author    Willy Mesnage <willy.mesnage@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class HasValidCurrencyValidatorSpec extends ObjectBehavior
{
    function let(ExecutionContextInterface $context, ChannelManager $channelManager)
    {
        $this->beConstructedWith($channelManager);
        $this->initialize($context);
    }

    function it_does_nothing_with_something_else_than_abstract_product_element(
        $context,
        HasValidCurrency $constraint,
        ChannelManager $value
    ) {
        $context->addViolationAt(Argument::cetera())->shouldNoTBeCalled();

        $this->validate($value, $constraint);
    }

    function it_success_if_the_currency_is_valid(
        $context,
        $channelManager,
        AbstractProductProcessor $value,
        HasValidCurrency $constraint,
        Channel $channel,
        Currency $currency
    ) {
        $value->getChannel()->willReturn('channel');
        $channelManager->getChannelByCode(Argument::any())->willReturn($channel);
        $channel->getCurrencies()->willReturn([$currency]);
        $currency->getCode()->willReturn('euro');
        $value->getCurrency()->willReturn('euro');

        $context->addViolationAt(Argument::cetera())->shouldNotBeCalled();

        $this->validate($value, $constraint);
    }

    function it_fails_if_the_currency_is_not_valid(
        $context,
        $channelManager,
        AbstractProductProcessor $value,
        HasValidCurrency $constraint,
        Channel $channel,
        Currency $currency
    ) {
        $value->getChannel()->willReturn('channel');
        $channelManager->getChannelByCode('channel')->willReturn($channel);
        $channel->getCurrencies()->willReturn([$currency]);
        $currency->getCode()->willReturn('dollar');
        $value->getCurrency()->willReturn('euro');

        $constraint->message = 'The given currency is not valid (check that the selected currency is in channel\'s currencies)';

        $context->addViolationAt('currency', 'The given currency is not valid (check that the selected currency is in channel\'s currencies)', ['currency'])->shouldBeCalled();

        $this->validate($value, $constraint);
    }

    function it_fails_if_no_channel_has_been_found(
        $context,
        $channelManager,
        AbstractProductProcessor $value,
        HasValidCurrency $constraint,
        Channel $channel
    ) {
        $value->getChannel()->willReturn('channel');
        $channelManager->getChannelByCode('channel')->willReturn(false);

        $constraint->message = 'The given currency is not valid (check that the selected currency is in channel\'s currencies)';

        $context->addViolationAt('currency', 'The given currency is not valid (check that the selected currency is in channel\'s currencies)', ['currency'])->shouldBeCalled();

        $this->validate($value, $constraint);
    }
}
