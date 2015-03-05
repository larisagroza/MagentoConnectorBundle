<?php

namespace spec\Pim\Bundle\MagentoConnectorBundle\Filter;

use Doctrine\Common\Collections\ArrayCollection;
use PhpSpec\ObjectBehavior;
use Pim\Bundle\CatalogBundle\Entity\Category;
use Pim\Bundle\CatalogBundle\Entity\Channel;
use Pim\Bundle\CatalogBundle\Model\AbstractCompleteness;
use Pim\Bundle\CatalogBundle\Model\ProductInterface;

class ExportableProductFilterSpec extends ObjectBehavior
{
    function it_filters_exportable_products(
        ProductInterface $product1,
        ProductInterface $product2,
        Channel $channel,
        ArrayCollection $productCategories,
        ArrayCollection $completenesses,
        Category $rootCategory,
        Category $category1,
        Category $category2,
        AbstractCompleteness $completeness1,
        AbstractCompleteness $completeness2
    ) {
        $channel->getCategory()->willReturn($rootCategory);
        $channel->getId()->willReturn(2);

        $rootCategory->getId()->willReturn(1);

        $product1->getCategories()->willReturn($productCategories);
        $product2->getCategories()->willReturn($productCategories);
        $product1->getCompletenesses()->willReturn($completenesses);
        $product2->getCompletenesses()->willReturn($completenesses);

        $completenesses->toArray()->willReturn([$completeness1, $completeness2]);
        $completeness1->getChannel()->willReturn($channel);
        $completeness2->getChannel()->willReturn($channel);
        $completeness1->getRatio()->willReturn(100);
        $completeness2->getRatio()->willReturn(100);

        $productCategories->toArray()->willReturn([$category1, $category2]);
        $category1->getRoot()->willReturn(1);
        $category2->getRoot()->willReturn(1);

        $this->apply($channel, [$product1, $product2])->shouldReturn([$product1, $product2]);
    }

    function it_returns_only_complete_products(
        ProductInterface $product1,
        ProductInterface $product2,
        Channel $channel,
        ArrayCollection $productCategories,
        ArrayCollection $completenesses1,
        ArrayCollection $completenesses2,
        Category $rootCategory,
        Category $category1,
        Category $category2,
        AbstractCompleteness $completeness1,
        AbstractCompleteness $completeness2
    ) {
        $channel->getCategory()->willReturn($rootCategory);
        $channel->getId()->willReturn(2);

        $rootCategory->getId()->willReturn(1);

        $product1->getCategories()->willReturn($productCategories);
        $product2->getCategories()->willReturn($productCategories);
        $product1->getCompletenesses()->willReturn($completenesses1);
        $product2->getCompletenesses()->willReturn($completenesses2);

        $completenesses1->toArray()->willReturn([$completeness1]);
        $completenesses2->toArray()->willReturn([$completeness2]);
        $completeness1->getChannel()->willReturn($channel);
        $completeness2->getChannel()->willReturn($channel);
        $completeness1->getRatio()->willReturn(100);
        $completeness2->getRatio()->willReturn(90);

        $productCategories->toArray()->willReturn([$category1, $category2]);
        $category1->getRoot()->willReturn(1);
        $category2->getRoot()->willReturn(1);

        $this->apply($channel, [$product1, $product2])->shouldReturn([$product1]);
    }

    function it_returns_only_products_in_the_given_channel(
        ProductInterface $product1,
        ProductInterface $product2,
        Channel $channel,
        ArrayCollection $productCategories1,
        ArrayCollection $productCategories2,
        ArrayCollection $completenesses,
        Category $rootCategory,
        Category $category1,
        Category $category2,
        AbstractCompleteness $completeness1,
        AbstractCompleteness $completeness2
    ) {
        $channel->getCategory()->willReturn($rootCategory);
        $channel->getId()->willReturn(2);

        $rootCategory->getId()->willReturn(1);

        $product1->getCategories()->willReturn($productCategories1);
        $product2->getCategories()->willReturn($productCategories2);
        $product1->getCompletenesses()->willReturn($completenesses);
        $product2->getCompletenesses()->willReturn($completenesses);

        $completenesses->toArray()->willReturn([$completeness1, $completeness2]);
        $completeness1->getChannel()->willReturn($channel);
        $completeness2->getChannel()->willReturn($channel);
        $completeness1->getRatio()->willReturn(100);
        $completeness2->getRatio()->willReturn(100);

        $productCategories1->toArray()->willReturn([$category1]);
        $productCategories2->toArray()->willReturn([$category2]);
        $category1->getRoot()->willReturn(1);
        $category2->getRoot()->willReturn(5);

        $this->apply($channel, [$product1, $product2])->shouldReturn([$product1]);
    }
}
