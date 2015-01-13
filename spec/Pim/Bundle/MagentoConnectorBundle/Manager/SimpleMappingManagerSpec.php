<?php

namespace spec\Pim\Bundle\MagentoConnectorBundle\Manager;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;
use Pim\Bundle\MagentoConnectorBundle\Entity\SimpleMapping;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class SimpleMappingManagerSpec extends ObjectBehavior
{
    function let(ObjectManager $objectManager, EntityRepository $er)
    {
        $this->beConstructedWith($objectManager, 'className');
        $objectManager->getRepository('className')->willReturn($er);
    }

    function it_gets_mapping_from_database($er)
    {
        $er->findBy(array('identifier' => 'foo'))->willReturn(array('bar'));

        $this->getMapping('foo')->shouldReturn(array('bar'));
    }

    function it_store_new_mapping_in_database($er, $objectManager)
    {
        $er->findBy(array('identifier' => 'identifier'))->willReturn(array());
        $er->findOneBy(array('identifier' => 'identifier', 'source' => 'foo'))->willReturn(null);
        $objectManager->persist(Argument::cetera())->shouldBeCalled();
        $objectManager->flush()->shouldBeCalled();

        $this->setMapping(array(array('source' => 'foo', 'target' => 'bar')), 'identifier');
    }

    function it_store_updated_mapping_in_database($er, $objectManager, SimpleMapping $simpleMapping)
    {
        $objectManager->flush()->shouldBeCalled();
        $er->findBy(array('identifier' => 'identifier'))->willReturn(array());
        $er->findOneBy(array('identifier' => 'identifier', 'source' => 'foo'))->willReturn($simpleMapping);

        $simpleMapping->setTarget('bar')->shouldBeCalled();

        $objectManager->persist(Argument::cetera())->shouldBeCalled();

        $this->setMapping(array(array('source' => 'foo', 'target' => 'bar')), 'identifier');
    }

    function it_cleans_old_mapping_values($er, $objectManager)
    {
        $er->findBy(array('identifier' => 'identifier'))->willReturn(array('old_mapping'));

        $objectManager->remove('old_mapping')->shouldBeCalled();
        $objectManager->flush()->shouldBeCalled();

        $this->setMapping(array(), 'identifier');
    }
}
