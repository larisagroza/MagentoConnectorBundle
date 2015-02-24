<?php

namespace spec\Pim\Bundle\MagentoConnectorBundle\Manager;

use Doctrine\Common\Persistence\ObjectManager;
use Pim\Bundle\CatalogBundle\Entity\Attribute;
use Doctrine\ORM\EntityRepository;
use Pim\Bundle\MagentoConnectorBundle\Entity\MagentoAttributeMapping;
use PhpSpec\ObjectBehavior;

class AttributeMappingManagerSpec extends ObjectBehavior
{
    function let(
        ObjectManager $objectManager,
        EntityRepository $entityRepository,
        Attribute $attribute
    ) {
        $this->beConstructedWith($objectManager, 'Pim\Bundle\MagentoConnectorBundle\Entity\MagentoAttributeMapping');
        $objectManager->getRepository('Pim\Bundle\MagentoConnectorBundle\Entity\MagentoAttributeMapping')
            ->willReturn($entityRepository);

        $attribute->getCode()->willReturn(12);
    }

    function it_returns_attribute_from_id(
        $entityRepository,
        MagentoAttributeMapping $magentoAttributeMapping,
        $attribute
    ) {
        $entityRepository->findOneBy(['magentoAttributeId' => 12, 'magentoUrl' => 'url'])
            ->willReturn($magentoAttributeMapping);

        $magentoAttributeMapping->getAttribute()->willReturn($attribute);

        $this->getAttributeFromId(12, 'url')->shouldReturn($attribute);
    }

    function it_returns_null_if_no_attribute_are_found_from_id($entityRepository)
    {
        $entityRepository->findOneBy(['magentoAttributeId' => 12, 'magentoUrl' => 'url'])
            ->willReturn(null);

        $this->getAttributeFromId(12, 'url')->shouldReturn(null);
    }

    function it_returns_id_from_attribute(
        $entityRepository,
        MagentoAttributeMapping $magentoAttributeMapping,
        $attribute
    ) {
        $entityRepository->findOneBy(['attribute' => $attribute, 'magentoUrl' => 'url'])
            ->willReturn($magentoAttributeMapping);

        $magentoAttributeMapping->getMagentoAttributeId()->willReturn(3);

        $this->getIdFromAttribute($attribute, 'url')->shouldReturn(3);
    }

    function it_returns_null_if_no_id_are_found_from_attribute(
        $entityRepository,
        $attribute
    ) {
        $entityRepository->findOneBy(['attribute' => $attribute, 'magentoUrl' => 'url'])
            ->willReturn(null);

        $this->getIdFromAttribute($attribute, 'url')->shouldReturn(null);
    }

    function it_returns_all_magento_attribute(
        $entityRepository,
        MagentoAttributeMapping $magentoAttributeMapping
    ) {
        $entityRepository->findAll(['magentoUrl' => 'url'])
            ->shouldBeCalled()
            ->willReturn([$magentoAttributeMapping]);

        $this->getAllMagentoAttribute('url')->shouldReturn([$magentoAttributeMapping]);
    }

    function it_registers_mapping(
        $entityRepository,
        MagentoAttributeMapping $magentoAttributeMapping,
        $attribute,
        $objectManager
) {
        $entityRepository->findOneBy(['attribute' => $attribute])->willReturn($magentoAttributeMapping);
        $magentoAttributeMapping->setAttribute($attribute);
        $magentoAttributeMapping->setMagentoAttributeId(12);
        $magentoAttributeMapping->setMagentoUrl('url');

        $objectManager->persist($magentoAttributeMapping)->shouldBeCalled();
        $objectManager->flush()->shouldBeCalled();

        $this->registerAttributeMapping($attribute, 12, 'url');
    }

    function it_returns_true_when_magento_attribute_exists(
        $entityRepository,
        MagentoAttributeMapping $magentoAttributeMapping,
        $attribute
    ) {
        $entityRepository->findOneBy(['magentoAttributeId' => 12, 'magentoUrl' => 'url'])
            ->willReturn($magentoAttributeMapping);

        $magentoAttributeMapping->getAttribute()->willReturn($attribute);

        $this->magentoAttributeExists(12, 'url')->shouldReturn(true);
    }

    function it_returns_false_when_magento_attribute_does_not_exist($entityRepository)
    {
        $entityRepository->findOneBy(['magentoAttributeId' => 12, 'magentoUrl' => 'url'])
            ->willReturn(null);

        $this->magentoAttributeExists(12, 'url')->shouldReturn(false);
    }
}
