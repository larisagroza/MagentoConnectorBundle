<?php

namespace spec\Pim\Bundle\MagentoConnectorBundle\EventSubscriber;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use PhpSpec\ObjectBehavior;
use Pim\Bundle\CatalogBundle\Event\ProductEvents;
use Pim\Bundle\CatalogBundle\Model\ProductInterface;
use Pim\Bundle\MagentoConnectorBundle\Builder\TableNameBuilder;
use Symfony\Component\EventDispatcher\GenericEvent;

class CascadeDeleteDeltaSubscriberSpec extends ObjectBehavior
{
    function let(EntityManager $em, TableNameBuilder $tableNameBuilder, Connection $connection)
    {
        $em->getConnection()->willReturn($connection);
        $tableNameBuilder
            ->getTableName('pim_magento_connector.entity.delta_product_export.class')
            ->willReturn('delta_product');
        $tableNameBuilder
            ->getTableName('pim_magento_connector.entity.delta_product_association_export.class')
            ->willReturn('delta_association');
        $tableNameBuilder
            ->getTableName('pim_magento_connector.entity.delta_configurable_export.class')
            ->willReturn('delta_configurable');

        $this->beConstructedWith($em, $tableNameBuilder);
    }

    function it_is_an_event_subscriber()
    {
        $this->shouldImplement('Symfony\Component\EventDispatcher\EventSubscriberInterface');
    }

    function it_subscribes_to_product_remove_events()
    {
        $this->getSubscribedEvents()->shouldReturn(
            [
                ProductEvents::POST_MASS_REMOVE => 'postMassRemove',
                ProductEvents::POST_REMOVE      => 'postRemove'
            ]
        );
    }

    function it_deletes_removed_product_id(GenericEvent $event, ProductInterface $product, $connection)
    {
        $event->getSubject()->willReturn($product);
        $product->getId()->willReturn(5);

        $connection->executeQuery('DELETE FROM delta_product WHERE product_id IN (5)')->shouldBeCalled();
        $connection->executeQuery('DELETE FROM delta_association WHERE product_id IN (5)')->shouldBeCalled();
        $connection->executeQuery('DELETE FROM delta_configurable WHERE product_id IN (5)')->shouldBeCalled();

        $this->postRemove($event);
    }

    function it_deletes_removed_product_ids(GenericEvent $event, $connection)
    {
        $event->getSubject()->willReturn([1, 2, 3]);

        $connection->executeQuery('DELETE FROM delta_product WHERE product_id IN (1,2,3)')->shouldBeCalled();
        $connection->executeQuery('DELETE FROM delta_association WHERE product_id IN (1,2,3)')->shouldBeCalled();
        $connection->executeQuery('DELETE FROM delta_configurable WHERE product_id IN (1,2,3)')->shouldBeCalled();

        $this->postMassRemove($event);
    }
}
