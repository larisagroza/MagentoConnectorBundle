<?php

namespace spec\Pim\Bundle\MagentoConnectorBundle\Manager;

use Doctrine\Common\Persistence\ObjectManager;
use Pim\Bundle\CatalogBundle\Entity\Family;
use Doctrine\ORM\EntityRepository;
use Pim\Bundle\MagentoConnectorBundle\Entity\MagentoFamilyMapping;
use Pim\Bundle\MagentoConnectorBundle\Mapper\MappingCollection;
use PhpSpec\ObjectBehavior;

class FamilyMappingManagerSpec extends ObjectBehavior
{
    function let(
        ObjectManager $objectManager,
        EntityRepository $entityRepository,
        MappingCollection $mappingCollection
    ) {
        $this->beConstructedWith($objectManager, 'Pim\Bundle\MagentoConnectorBundle\Entity\MagentoFamilyMapping');
        $objectManager->getRepository('Pim\Bundle\MagentoConnectorBundle\Entity\MagentoFamilyMapping')
            ->willReturn($entityRepository);

        $mappingCollection->getTarget('default')->willReturn(12);
    }

    function it_gets_family_from_id(
        $entityRepository,
        MagentoFamilyMapping $familyMapping,
        Family $family
    ) {
        $entityRepository->findOneBy(['magentoFamilyId' => 12, 'magentoUrl' => 'magento_url'])
            ->willReturn($familyMapping);

        $familyMapping->getFamily()->willReturn($family);

        $this->getFamilyFromId(12, 'magento_url')->shouldReturn($family);
    }

    function it_returns_null_if_family_mapping_is_not_found($entityRepository)
    {
        $entityRepository->findOneBy(['magentoFamilyId' => 12, 'magentoUrl' => 'magento_url'])
            ->willReturn(null);

        $this->getFamilyFromId(12, 'magento_url')->shouldReturn(null);
    }

    function it_gets_id_from_family_mapping_stored_in_database(
        Family $family,
        $entityRepository,
        MagentoFamilyMapping $familyMapping,
        $mappingCollection
    ) {
        $entityRepository->findOneBy(
            [
                'family'   => $family,
                'magentoUrl' => '',
            ]
        )->willReturn($familyMapping);

        $familyMapping->getMagentoFamilyId()->willReturn(13);

        $mappingCollection->getTarget('colors')->willReturn('colors');

        $family->getCode()->willReturn('colors');

        $this->getIdFromFamily($family, '', $mappingCollection)->shouldReturn(13);
    }

    function it_returns_null_if_family_is_not_found(
        Family $family,
        $entityRepository,
        $mappingCollection
    ) {
        $entityRepository->findOneBy(
            [
                'family'   => $family,
                'magentoUrl' => '',
            ]
        )->willReturn(null);

        $mappingCollection->getTarget('colors')->willReturn('colors');

        $family->getCode()->willReturn('colors');

        $this->getIdFromFamily($family, '', $mappingCollection)->shouldReturn(null);
    }

    function it_tests_if_a_family_exist_from_family_id(
        $entityRepository,
        MagentoFamilyMapping $familyMapping,
        Family $family
    ) {
        $entityRepository->findOneBy(['magentoFamilyId' => 12, 'magentoUrl' => 'magento_url'])
            ->willReturn($familyMapping);

        $familyMapping->getFamily()->willReturn($family);

        $this->magentoFamilyExists(12, 'magento_url')->shouldReturn(true);
    }

    function it_registers_mapping(
        EntityRepository $entityRepository,
        MagentoFamilyMapping $magentoFamilyMapping,
        Family $family,
        $objectManager
    ) {
        $entityRepository->findOneBy(['family' => $family])->willReturn($magentoFamilyMapping);
        $magentoFamilyMapping->setFamily($family)->shouldBeCalled();
        $magentoFamilyMapping->setMagentoFamilyId(35050)->shouldBeCalled();
        $magentoFamilyMapping->setMagentoUrl('url')->shouldBeCalled();

        $objectManager->persist($magentoFamilyMapping)->shouldBeCalled();
        $objectManager->flush()->shouldBeCalled();

        $this->registerFamilyMapping($family, 35050, 'url');
    }
}
