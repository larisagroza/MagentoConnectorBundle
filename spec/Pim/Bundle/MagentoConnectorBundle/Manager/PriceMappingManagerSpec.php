<?php

namespace spec\Pim\Bundle\MagentoConnectorBundle\Manager;

use Pim\Bundle\CatalogBundle\Entity\Group;
use Pim\Bundle\CatalogBundle\Model\Product;
use Pim\Bundle\CatalogBundle\Entity\Attribute;
use Pim\Bundle\CatalogBundle\Entity\AttributeOption;
use Pim\Bundle\CatalogBundle\Model\ProductValue;
use Pim\Bundle\CatalogBundle\Model\ProductPrice;
use Pim\Bundle\MagentoConnectorBundle\Mapper\MappingCollection;
use PhpSpec\ObjectBehavior;

class PriceMappingManagerSpec extends ObjectBehavior
{
    function let(
        Group $group,
        Product $product1,
        Product $product2,
        Product $product3,
        Attribute $attribute1,
        Attribute $attribute2,
        AttributeOption $attributeOption11,
        AttributeOption $attributeOption12,
        AttributeOption $attributeOption21,
        AttributeOption $attributeOption22,
        ProductValue $productValueOption11,
        ProductValue $productValueOption12,
        ProductValue $productValueOption21,
        ProductValue $productValueOption22,
        ProductValue $productValuePrice1,
        ProductPrice $productPrice1,
        ProductValue $productValuePrice2,
        ProductPrice $productPrice2,
        ProductValue $productValuePrice3,
        ProductPrice $productPrice3,
        MappingCollection $attributeMapping
    ) {
        $this->beConstructedWith('locale', 'currency', 'channel');

        $group->getAttributes()->willReturn([$attribute1, $attribute2]);

        //get attribute options
        $attribute1->getOptions()->willReturn([$attributeOption11, $attributeOption12]);
        $attribute1->getCode()->willReturn('attribute_1');

        $attributeOption11->getAttribute()->willReturn($attribute1);
        $attributeOption11->getCode()->willReturn('attribute_1_option_1');
        $productValueOption11->getData()->willReturn($attributeOption11);

        $attributeOption12->getAttribute()->willReturn($attribute1);
        $attributeOption12->getCode()->willReturn('attribute_1_option_2');
        $productValueOption12->getData()->willReturn($attributeOption12);

        $attribute2->getOptions()->willReturn([$attributeOption21, $attributeOption22]);
        $attribute2->getCode()->willReturn('attribute_2');

        $attributeOption21->getAttribute()->willReturn($attribute2);
        $attributeOption21->getCode()->willReturn('attribute_2_option_1');
        $productValueOption21->getData()->willReturn($attributeOption21);

        $attributeOption22->getAttribute()->willReturn($attribute2);
        $attributeOption22->getCode()->willReturn('attribute_2_option_2');
        $productValueOption22->getData()->willReturn($attributeOption22);

        //Get product prices
        $product1->getValue('price', 'locale', 'channel')->willReturn($productValuePrice1);
        $product1->getIdentifier()->willReturn('product_1');
        $productValuePrice1->getPrice('currency')->willReturn($productPrice1);
        $productPrice1->getData()->willReturn(5.0);

        $product2->getValue('price', 'locale', 'channel')->willReturn($productValuePrice2);
        $product2->getIdentifier()->willReturn('product_2');
        $productValuePrice2->getPrice('currency')->willReturn($productPrice2);
        $productPrice2->getData()->willReturn(15.0);

        $product3->getValue('price', 'locale', 'channel')->willReturn($productValuePrice3);
        $product3->getIdentifier()->willReturn('product_3');
        $productValuePrice3->getPrice('currency')->willReturn($productPrice3);
        $productPrice3->getData()->willReturn(10.0);

        $attributeMapping->getSource('attribute_1')->willReturn('attribute_1');
        $attributeMapping->getSource('attribute_2')->willReturn('attribute_2');
        $attributeMapping->getSource('price')->willReturn('price');
        $attributeMapping->getTarget('attribute_1')->willReturn('attribute_1');
        $attributeMapping->getTarget('attribute_2')->willReturn('attribute_2');
    }

    function it_gives_simple_price_mapping(
        $group,
        $product1,
        $productValueOption11,
        $productValueOption21,
        $attributeMapping
    ) {
        $product1->getValue('attribute_1', 'locale')->willReturn($productValueOption11);
        $product1->getValue('attribute_2', 'locale')->willReturn($productValueOption21);

        $this->getPriceMapping($group, [$product1], $attributeMapping)->shouldReturn(
            [
                'price_changes' => [
                    'attribute_1' => [
                        'attribute_1_option_1' => 0.0,
                    ],
                    'attribute_2' => [
                        'attribute_2_option_1' => 0.0,
                    ],
                ],
                'price' => 5.0,
            ]
        );
    }

    function it_gives_complexe_price_mapping_from_upper_price(
        $group,
        $product1,
        $product2,
        $product3,
        $productValueOption11,
        $productValueOption12,
        $productValueOption21,
        $productValueOption22,
        $attributeMapping
    ) {
        $products = [$product1, $product2, $product3];

        //Product values
        $product1->getValue('attribute_1', 'locale')->willReturn($productValueOption11);
        $product1->getValue('attribute_2', 'locale')->willReturn($productValueOption21);

        $product2->getValue('attribute_1', 'locale')->willReturn($productValueOption11);
        $product2->getValue('attribute_2', 'locale')->willReturn($productValueOption22);

        $product3->getValue('attribute_1', 'locale')->willReturn($productValueOption12);
        $product3->getValue('attribute_2', 'locale')->willReturn($productValueOption22);

        $priceMapping = $this->getPriceMapping($group, $products, $attributeMapping)->shouldReturn(
            [
                'price_changes' => [
                    'attribute_1' => [
                        'attribute_1_option_1' => 0.0,
                        'attribute_1_option_2' => -5.0,
                    ],
                    'attribute_2' => [
                        'attribute_2_option_1' => -10.0,
                        'attribute_2_option_2' => 0.0,
                    ],
                ],
                'price' => 15.0,
            ]
        );

        $this->validatePriceMapping($products, $priceMapping['price_changes'], $priceMapping['price'], $attributeMapping);
    }

    function it_gives_complexe_price_mapping_from_lower_price(
        $group,
        $product1,
        $product2,
        $product3,
        $productValueOption11,
        $productValueOption12,
        $productValueOption21,
        $productValueOption22,
        $attributeMapping
    ) {
        //Product values
        $product1->getValue('attribute_1', 'locale')->willReturn($productValueOption12);
        $product1->getValue('attribute_2', 'locale')->willReturn($productValueOption21);

        $product2->getValue('attribute_1', 'locale')->willReturn($productValueOption11);
        $product2->getValue('attribute_2', 'locale')->willReturn($productValueOption22);

        $product3->getValue('attribute_1', 'locale')->willReturn($productValueOption12);
        $product3->getValue('attribute_2', 'locale')->willReturn($productValueOption22);

        $products = [$product3, $product2, $product1];

        $priceMapping = $this->getPriceMapping($group, $products, $attributeMapping)->shouldReturn(
            [
                'price_changes' => [
                    'attribute_1' => [
                        'attribute_1_option_1' => 5.0,
                        'attribute_1_option_2' => 0.0,
                    ],
                    'attribute_2' => [
                        'attribute_2_option_1' => 0.0,
                        'attribute_2_option_2' => 5.0,
                    ],
                ],
                'price' => 5.0,
            ]
        );

        $this->validatePriceMapping($products, $priceMapping['price_changes'], $priceMapping['price'], $attributeMapping);
    }

    function it_gives_an_other_complexe_price_mapping_from_lower_price(
        $group,
        $product1,
        $product2,
        $product3,
        $productValueOption11,
        $productValueOption12,
        $productValueOption21,
        $productValueOption22,
        $attributeMapping
    ) {
        //Product values
        $product1->getValue('attribute_1', 'locale')->willReturn($productValueOption12);
        $product1->getValue('attribute_2', 'locale')->willReturn($productValueOption21);

        $product2->getValue('attribute_1', 'locale')->willReturn($productValueOption12);
        $product2->getValue('attribute_2', 'locale')->willReturn($productValueOption22);

        $product3->getValue('attribute_1', 'locale')->willReturn($productValueOption11);
        $product3->getValue('attribute_2', 'locale')->willReturn($productValueOption21);

        $products = [$product3, $product2, $product1];

        $priceMapping = $this->getPriceMapping($group, $products, $attributeMapping)->shouldReturn(
            [
                'price_changes' => [
                    'attribute_1' => [
                        'attribute_1_option_1' => 5.0,
                        'attribute_1_option_2' => 0.0,
                    ],
                    'attribute_2' => [
                        'attribute_2_option_1' => 0.0,
                        'attribute_2_option_2' => 10.0,
                    ],
                ],
                'price' => 5.0,
            ]
        );

        $this->validatePriceMapping($products, $priceMapping['price_changes'], $priceMapping['price'], $attributeMapping);
    }
}
