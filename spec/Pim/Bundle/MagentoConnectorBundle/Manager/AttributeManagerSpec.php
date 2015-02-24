<?php

namespace spec\Pim\Bundle\MagentoConnectorBundle\Manager;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\ClassMetadata;
use PhpSpec\ObjectBehavior;

class AttributeManagerSpec extends ObjectBehavior
{
    function let(
        ObjectManager $objectManager,
        ClassMetadataFactory $classMetadataFactory,
        ClassMetadata $classMetadata
    ) {
        $this->beConstructedWith($objectManager, 'class_name');
        $objectManager->getMetadataFactory()->willReturn($classMetadataFactory);
        $classMetadataFactory->getMetadataFor('class_name')->willReturn($classMetadata);
    }
}
