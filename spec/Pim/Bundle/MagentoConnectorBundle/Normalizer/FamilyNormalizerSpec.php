<?php

namespace spec\Pim\Bundle\MagentoConnectorBundle\Normalizer;

use Pim\Bundle\CatalogBundle\Entity\Family;
use PhpSpec\ObjectBehavior;

class FamilyNormalizerSpec extends ObjectBehavior
{
    protected $globalContext;

    function let()
    {
        $this->globalContext = [
            'magentoFamilies' => [],
            'magentoUrl'        => 'soap_url',
            'defaultLocale'     => 'default_locale',
            'magentoStoreViews' => [],
        ];
    }

    function it_normalizes_a_family(Family $family)
    {
        $family->getCode()->willReturn('family_code');
        $this->normalize($family)->shouldReturn(['attributeSetName' => 'family_code']);
    }
}
