<?php

namespace spec\Pim\Bundle\MagentoConnectorBundle\Processor;

use Pim\Bundle\CatalogBundle\Manager\ChannelManager;
use Pim\Bundle\MagentoConnectorBundle\Guesser\WebserviceGuesser;
use Pim\Bundle\MagentoConnectorBundle\Guesser\NormalizerGuesser;
use Pim\Bundle\MagentoConnectorBundle\Webservice\Webservice;
use Pim\Bundle\MagentoConnectorBundle\Normalizer\OptionNormalizer;
use Pim\Bundle\CatalogBundle\Entity\Attribute;
use Pim\Bundle\CatalogBundle\Entity\AttributeOption;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class OptionProcessorSpec extends ObjectBehavior
{
    function let(
        ChannelManager $channelManager,
        WebserviceGuesser $webserviceGuesser,
        NormalizerGuesser $normalizerGuesser,
        Webservice $webservice,
        OptionNormalizer $optionNormalizer
    ) {
        $this->beConstructedWith(
            $channelManager,
            $webserviceGuesser,
            $normalizerGuesser
        );

        $webserviceGuesser->getWebservice(Argument::any())->willReturn($webservice);
        $normalizerGuesser->getOptionNormalizer(Argument::cetera())->willReturn($optionNormalizer);
    }

    function it_normalizes_given_grouped_options(
        AttributeOption $optionRed,
        AttributeOption $optionBlue,
        Attribute $attribute,
        $optionNormalizer,
        $webservice
    ) {
        $optionRed->getAttribute()->willReturn($attribute);
        $attribute->getCode()->willReturn('size');

        $optionRed->getCode()->willReturn('red');
        $optionBlue->getCode()->willReturn('blue');

        $webservice->getStoreViewsList()->shouldBeCalled();
        $webservice->getOptionsStatus('size')->willReturn(array('red'));

        $optionNormalizer->normalize($optionRed, Argument::cetera())->willReturn(array('foo'));
        $optionNormalizer->normalize($optionBlue, Argument::cetera())->willReturn(array('bar'));

        $this->process(array(
            $optionRed,
            $optionBlue
        ))->shouldReturn(array(array('bar')));
    }
}
