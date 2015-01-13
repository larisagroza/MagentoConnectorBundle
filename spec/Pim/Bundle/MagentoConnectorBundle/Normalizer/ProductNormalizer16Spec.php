<?php

namespace spec\Pim\Bundle\MagentoConnectorBundle\Normalizer;

use Pim\Bundle\CatalogBundle\Manager\ChannelManager;
use Pim\Bundle\CatalogBundle\Manager\MediaManager;
use Pim\Bundle\MagentoConnectorBundle\Normalizer\ProductValueNormalizer;
use Pim\Bundle\MagentoConnectorBundle\Manager\CategoryMappingManager;
use Pim\Bundle\MagentoConnectorBundle\Manager\AssociationTypeManager;
use PhpSpec\ObjectBehavior;

class ProductNormalizer16Spec extends ObjectBehavior
{
    function let(
        ChannelManager $channelManager,
        MediaManager $mediaManager,
        ProductValueNormalizer $productValueNormalizer,
        CategoryMappingManager $categoryMappingManager,
        AssociationTypeManager $associationTypeManager
    ) {
        $this->beConstructedWith(
            $channelManager,
            $mediaManager,
            $productValueNormalizer,
            $categoryMappingManager,
            $associationTypeManager,
            1,
            4,
            1,
            'currency',
            'magento_url'
        );
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Pim\Bundle\MagentoConnectorBundle\Normalizer\ProductNormalizer16');
    }
}
