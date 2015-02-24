<?php

namespace spec\Pim\Bundle\MagentoConnectorBundle\Validator\Constraints;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ExecutionContextInterface;
use Pim\Bundle\MagentoConnectorBundle\Processor\AbstractProductProcessor;
use Pim\Bundle\CatalogBundle\Manager\ChannelManager;
use Pim\Bundle\MagentoConnectorBundle\Validator\Constraints\HasValidDefaultLocale;
use Pim\Bundle\CatalogBundle\Entity\Channel;
use Pim\Bundle\CatalogBundle\Entity\Locale;

/**
 * @author    Willy Mesnage <willy.mesnage@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class HasValidDefaultLocaleValidatorSpec extends ObjectBehavior
{
    function let(ExecutionContextInterface $context, ChannelManager $channelManager)
    {
        $this->beConstructedWith($channelManager);
        $this->initialize($context);
    }

    function it_does_nothing_with_something_else_than_abstract_product_element(
        $context,
        HasValidDefaultLocale $constraint,
        ChannelManager $value
    ) {
        $context->addViolationAt(Argument::cetera())->shouldNoTBeCalled();

        $this->validate($value, $constraint);
    }

    function it_success_if_the_locale_is_valid(
        $context,
        $channelManager,
        AbstractProductProcessor $value,
        HasValidDefaultLocale $constraint,
        Channel $channel,
        Locale $locale
    ) {
        $value->getChannel()->willReturn('channel');
        $channelManager->getChannelByCode(Argument::any())->willReturn($channel);
        $channel->getLocales()->willReturn([$locale]);
        $locale->getCode()->willReturn('fr_FR');
        $value->getDefaultLocale()->willReturn('fr_FR');

        $context->addViolationAt(Argument::cetera())->shouldNotBeCalled();

        $this->validate($value, $constraint);
    }

    function it_fails_if_the_locale_is_not_valid(
        $context,
        $channelManager,
        AbstractProductProcessor $value,
        HasValidDefaultLocale $constraint,
        Channel $channel,
        Locale $locale
    ) {
        $value->getChannel()->willReturn('channel');
        $channelManager->getChannelByCode(Argument::any())->willReturn($channel);
        $channel->getLocales()->willReturn([$locale]);
        $locale->getCode()->willReturn('fr_FR');
        $value->getDefaultLocale()->willReturn('us_US');

        $constraint->message = 'The given default locale is not valid (check that the selected locale is in channel\'s locales)';

        $context->addViolationAt('defaultLocale', 'The given default locale is not valid (check that the selected locale is in channel\'s locales)', ['defaultLocale'])->shouldBeCalled();

        $this->validate($value, $constraint);
    }

    function it_fails_if_no_channel_has_been_found(
        $context,
        $channelManager,
        AbstractProductProcessor $value,
        HasValidDefaultLocale $constraint,
        Channel $channel
    ) {
        $value->getChannel()->willReturn('channel');
        $channelManager->getChannelByCode('channel')->willReturn(false);

        $constraint->message = 'The given default locale is not valid (check that the selected locale is in channel\'s locales)';

        $context->addViolationAt('defaultLocale', 'The given default locale is not valid (check that the selected locale is in channel\'s locales)', ['defaultLocale'])->shouldBeCalled();

        $this->validate($value, $constraint);
    }
}
