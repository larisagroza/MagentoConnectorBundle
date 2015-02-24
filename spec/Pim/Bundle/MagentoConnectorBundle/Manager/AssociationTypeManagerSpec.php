<?php

namespace spec\Pim\Bundle\MagentoConnectorBundle\Manager;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;
use Pim\Bundle\CatalogBundle\Entity\AssociationType;
use PhpSpec\ObjectBehavior;

class AssociationTypeManagerSpec extends ObjectBehavior
{
    function let(ObjectManager $objectManager, EntityRepository $entityRepository)
    {
        $this->beConstructedWith($objectManager, 'class_name');
        $objectManager->getRepository('class_name')->willReturn($entityRepository);
    }

    function it_gets_association_type_from_repository($entityRepository, $arrayCollection)
    {
        $entityRepository->findBy([])->willReturn($arrayCollection);

        $this->getAssociationTypes()->shouldReturn($arrayCollection);
    }

    function it_gets_association_type_from_repository_by_code($entityRepository, AssociationType $associationType)
    {
        $entityRepository->findOneBy(['code' => 'foo'])->willReturn($associationType);

        $this->getAssociationTypeByCode('foo')->shouldReturn($associationType);
    }

    function it_gets_association_type_choices_from_repository($entityRepository, AssociationType $associationType)
    {
        $entityRepository->findBy([])->willReturn([$associationType]);
        $associationType->getCode()->willReturn('foo');
        $associationType->getLabel()->willReturn('Foo');

        $this->getAssociationTypeChoices()->shouldReturn(['foo' => 'Foo']);
    }
}
