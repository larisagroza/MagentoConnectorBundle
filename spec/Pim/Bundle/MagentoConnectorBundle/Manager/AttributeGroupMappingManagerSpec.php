<?php

namespace spec\Pim\Bundle\MagentoConnectorBundle\Manager;

use Doctrine\Common\Persistence\ObjectManager;
use Pim\Bundle\CatalogBundle\Entity\AttributeGroup;
use Doctrine\ORM\EntityRepository;
use Pim\Bundle\MagentoConnectorBundle\Entity\MagentoGroupMapping;
use Pim\Bundle\CatalogBundle\Entity\Family;
use PhpSpec\ObjectBehavior;

class AttributeGroupMappingManagerSpec extends ObjectBehavior
{
    function let(
        ObjectManager $objectManager,
        EntityRepository $entityRepository,
        Family $family,
        AttributeGroup $group
    ) {
        $this->beConstructedWith($objectManager, 'Pim\Bundle\MagentoConnectorBundle\Entity\MagentoAttributeMapping');
        $objectManager->getRepository('Pim\Bundle\MagentoConnectorBundle\Entity\MagentoAttributeMapping')
            ->willReturn($entityRepository);

        $group->getCode()->willReturn(12);
        $family->getCode()->willReturn(5);
    }

    function it_returns_id_from_group(
        $entityRepository,
        MagentoGroupMapping $magentoGroupMapping,
        $family,
        $group
    ) {
        $entityRepository->findOneBy(['pimGroupCode' => 12, 'pimFamilyCode' => 5, 'magentoUrl' => 'magento_url'])
            ->willReturn($magentoGroupMapping);

        $magentoGroupMapping->getMagentoGroupId()->willReturn(4);

        $this->getIdFromGroup($group, $family, 'magento_url')->shouldReturn(4);
    }

    function it_gets_null_if_attribute_group_mapping_is_not_found(
        $entityRepository,
        MagentoGroupMapping $magentoGroupMapping,
        $family,
        $group
    ) {
        $entityRepository->findOneBy(['pimGroupCode' => 12, 'pimFamilyCode' => 5, 'magentoUrl' => 'magento_url'])
            ->willReturn(null);

        $magentoGroupMapping->getMagentoGroupId()->shouldNotBeCalled();

        $this->getIdFromGroup($group, $family, 'magento_url')->shouldReturn(null);
    }

    function it_returns_all_mappings(
        $entityRepository,
        MagentoGroupMapping $magentoGroupMapping
    ) {
        $entityRepository->findAll()->willReturn([$magentoGroupMapping]);
        $this->getAllMappings()->shouldReturn([$magentoGroupMapping]);
    }

    function it_registers_mapping(
        $entityRepository,
        MagentoGroupMapping $magentoGroupMapping,
        $group,
        $family,
        $objectManager
    ) {
        $entityRepository->findOneBy(['pimGroupCode' => 12, 'pimFamilyCode' => 5])->willReturn($magentoGroupMapping);
        $magentoGroupMapping->setPimGroupCode(12)->shouldBeCalled();
        $magentoGroupMapping->setPimFamilyCode(5)->shouldBeCalled();
        $magentoGroupMapping->setMagentoGroupId(3)->shouldBeCalled();
        $magentoGroupMapping->setMagentoUrl('url')->shouldBeCalled();

        $objectManager->persist($magentoGroupMapping)->shouldBeCalled();
        $objectManager->flush()->shouldBeCalled();

        $this->registerGroupMapping($group, $family, 3, 'url');
    }

    function it_returns_null_if_no_mappings_found($entityRepository)
    {
        $entityRepository->findAll()->willReturn([]);
        $this->getAllMappings()->shouldReturn(null);
    }
}
