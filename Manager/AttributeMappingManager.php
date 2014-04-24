<?php

namespace Pim\Bundle\MagentoConnectorBundle\Manager;

use Pim\Bundle\CatalogBundle\Entity\Attribute;
use Pim\Bundle\CatalogBundle\Entity\Repository\AttributeRepository;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Attribute mapping manager
 *
 * @author    Olivier Soulet <olivier.soulet@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class AttributeMappingManager
{
    /**
     * @var \Doctrine\Common\Persistence\ObjectManager
     */
    protected $objectManager;

    /**
     * @var string
     */
    protected $className;

    /**
     * Constructor
     * @param ObjectManager $objectManager
     * @param string        $className
     */
    public function __construct(ObjectManager $objectManager, $className)
    {
        $this->objectManager = $objectManager;
        $this->className     = $className;
    }

    /**
     * Get attribute from id and Magento url
     * @param int    $id
     * @param string $magentoUrl
     *
     * @return Attribute
     */
    public function getAttributeFromId($id, $magentoUrl)
    {
        $magentoAttributeMapping = $this->getEntityRepository()->findOneBy(
            array(
                'magentoAttributeId' => $id,
                'magentoUrl'         => $magentoUrl
            )
        );

        return $magentoAttributeMapping ? $magentoAttributeMapping->getAttribute() : null;
    }

    /**
     * Get id from attribute and Magento url
     * @param Attribute $attribute
     * @param string    $magentoUrl
     *
     * @return int
     */
    public function getIdFromAttribute(Attribute $attribute, $magentoUrl)
    {

        $attributeMapping = $this->getEntityRepository()->findOneBy(
            array(
                'attribute'   => $attribute,
                'magentoUrl'  => $magentoUrl
            )
        );
            return $attributeMapping ? $attributeMapping->getMagentoAttributeId() : null;
    }

    /**
     * Register a new attribute mapping
     * @param Attribute   $pimAttribute
     * @param int         $magentoAttributeId
     * @param string      $magentoUrl
     */
    public function registerAttributeMapping(
        Attribute $pimAttribute,
        $magentoAttributeId,
        $magentoUrl
    ) {
        $magentoAttributeMapping = new $this->className();
        $magentoAttributeMapping->setAttribute($pimAttribute);
        $magentoAttributeMapping->setMagentoAttributeId($magentoAttributeId);
        $magentoAttributeMapping->setMagentoUrl($magentoUrl);

        $this->objectManager->persist($magentoAttributeMapping);
        $this->objectManager->flush();
    }

    /**
     * Does the given magento attribute exist in pim ?
     * @param string $attributeId
     * @param string $magentoUrl
     *
     * @return boolean
     */
    public function magentoAttributeExists($attributeId, $magentoUrl)
    {
        return $this->getAttributeFromId($attributeId, $magentoUrl) !== null;
    }

    /**
     * Get the entity manager
     * @return EntityRepository
     */
    protected function getEntityRepository()
    {
        return $this->objectManager->getRepository($this->className);
    }
}

