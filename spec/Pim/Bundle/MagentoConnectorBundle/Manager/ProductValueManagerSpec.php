<?php

namespace spec\Pim\Bundle\MagentoConnectorBundle\Manager;

use Pim\Bundle\CatalogBundle\Entity\Attribute;
use PhpSpec\ObjectBehavior;

class ProductValueManagerSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith('Pim\Bundle\CatalogBundle\Model\ProductValue');
    }

    function it_creates_default_product_for_default_option(Attribute $attribute)
    {
        $this->createProductValueForDefaultOption($attribute)->shouldReturnAnInstanceOf('Pim\Bundle\CatalogBundle\Model\ProductValue');
    }
}
