<?php

namespace spec\Pim\Bundle\MagentoConnectorBundle\Reader\ORM;

use PhpSpec\ObjectBehavior;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\AbstractQuery;
use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Pim\Bundle\CatalogBundle\Entity\Category;
use Pim\Bundle\CatalogBundle\Entity\Channel;
use Pim\Bundle\CatalogBundle\Manager\ChannelManager;
use Pim\Bundle\MagentoConnectorBundle\Entity\Repository\CategoryRepository;

class CategoryReaderSpec extends ObjectBehavior
{
    const CATEGORY_CLASS = 'Pim\Bundle\CatalogBundle\Entity\Category';

    function let(
        EntityManager $em,
        CategoryRepository $repository,
        ChannelManager $channelManager,
        StepExecution $stepExecution
    ) {
        $this->beConstructedWith($em, self::CATEGORY_CLASS, $repository, $channelManager);
        $this->setStepExecution($stepExecution);
    }

    function it_reads_categories_with_custom_order_query(
        $repository,
        $channelManager,
        Channel $channel,
        Category $rootCategory,
        QueryBuilder $qb,
        AbstractQuery $query
    ) {
        $this->setChannel('channel');

        $channelManager->getChannelByCode('channel')->willReturn($channel);
        $channel->getCategory()->willReturn($rootCategory);
        $repository->findOrderedCategories($rootCategory)->willReturn($qb);

        $qb->getQuery()->willReturn($query);
        $query->execute()->willReturn(['foo', 'bar']);

        $this->read()->shouldReturn('foo');
        $this->read()->shouldReturn('bar');
        $this->read()->shouldReturn(null);
    }
}
