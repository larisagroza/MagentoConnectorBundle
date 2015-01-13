<?php

namespace spec\Pim\Bundle\MagentoConnectorBundle\Normalizer;

use Pim\Bundle\CatalogBundle\Manager\ChannelManager;
use Pim\Bundle\CatalogBundle\Entity\AttributeOption;
use Pim\Bundle\CatalogBundle\Entity\AttributeOptionValue;
use Pim\Bundle\CatalogBundle\Entity\Channel;
use Pim\Bundle\CatalogBundle\Entity\Attribute;
use Pim\Bundle\MagentoConnectorBundle\Mapper\MappingCollection;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class OptionNormalizerSpec extends ObjectBehavior
{
    function let(ChannelManager $channelManager, Channel $channel)
    {
        $this->beConstructedWith($channelManager);

        $channelManager->getChannelByCode('magento')->willReturn($channel);
    }

    function it_normalizes_given_option(
        AttributeOption $option,
        AttributeOption $optionUS,
        AttributeOption $optionFR,
        AttributeOption $optionDE,
        AttributeOptionValue $optionValueUS,
        AttributeOptionValue $optionValueFR,
        AttributeOptionValue $optionValueDE,
        Attribute $attribute,
        MappingCollection $storeViewMapping
    ) {
        $magentoStoreViews = [
            ['code' => 'default', 'store_id' => 1],
            ['code' => 'fr_fr',   'store_id' => 2],
            ['code' => 'test',    'store_id' => 3],
        ];

        $storeViewMapping->getTarget('en_US')->willReturn('en_us');
        $storeViewMapping->getTarget('fr_FR')->willReturn('fr_fr');
        $storeViewMapping->getTarget('de_DE')->willReturn('test');

        $option->getCode()->willReturn('red');

        $option->getOptionValues()->willReturn([$optionValueUS, $optionValueFR, $optionValueDE]);

        $option->getSortOrder()->willReturn(1);

        $option->setLocale('en_US')->willReturn($optionUS);
        $optionUS->getOptionValue()->willReturn($optionValueUS);
        $optionValueUS->getLocale()->willReturn('en_US');
        $optionValueUS->getLabel()->willReturn('Red');

        $option->setLocale('fr_FR')->willReturn($optionFR);
        $optionFR->getOptionValue()->willReturn($optionValueFR);
        $optionValueFR->getLocale()->willReturn('fr_FR');
        $optionValueFR->getLabel()->willReturn('Rouge');

        $option->setLocale('de_DE')->willReturn($optionDE);
        $optionDE->getOptionValue()->willReturn($optionValueDE);
        $optionValueDE->getLocale()->willReturn('de_DE');
        $optionValueDE->getLabel()->willReturn('Rot');

        $option->getAttribute()->willReturn($attribute);
        $attribute->getCode()->willReturn('color');

        $this->normalize($option, Argument::any(), [
            'magentoStoreViews' => $magentoStoreViews,
            'storeViewMapping'  => $storeViewMapping,
            'channel'           => 'magento',
            'defaultLocale'     => 'en_US',
            'attributeCode'     => 'attribute_code'
        ])->shouldReturn([
            'attribute_code',
            [
                'label' => [
                    [
                        'store_id' => '0',
                        'value'    => 'red',
                    ],
                    [
                        'store_id' => '1',
                        'value'    => 'Red'
                    ],
                    [
                        'store_id' => '2',
                        'value'    => 'Rouge'
                    ],
                    [
                        'store_id' => '3',
                        'value'    => 'Rot'
                    ],
                ],
                'order'      => 1
            ],
        ]);
    }
}
