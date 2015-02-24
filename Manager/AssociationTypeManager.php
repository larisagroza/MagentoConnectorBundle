<?php

namespace Pim\Bundle\MagentoConnectorBundle\Manager;

use Doctrine\Common\Persistence\ObjectManager;

/**
 * Association type manager
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class AssociationTypeManager
{
    /**
     * @var \Doctrine\Common\Persistence\ObjectManager
     */
    protected $objectManager;

    /** @var string */
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
     * Get association types with criterias
     *
     * @param string[] $criterias
     *
     * @return array
     */
    public function getAssociationTypes($criterias = [])
    {
        return $this->getEntityRepository()->findBy($criterias);
    }

    /**
     * Get association types with criterias
     *
     * @param string[] $criterias
     *
     * @return AssociationType|null
     */
    public function getAssociationType($criterias = [])
    {
        return $this->getEntityRepository()->findOneBy($criterias);
    }

    /**
     * Get association types by code
     *
     * @param string $code
     *
     * @return array
     */
    public function getAssociationTypeByCode($code)
    {
        return $this->getAssociationType(['code' => $code]);
    }

    /**
     * Get assiociation type choices with criterias
     * Allow to list association types in an array like array[<code>] = <label>
     *
     * @param array $criterias
     *
     * @return string[]
     */
    public function getAssociationTypeChoices($criterias = [])
    {
        $associationTypes = $this->getAssociationTypes($criterias);

        $choices = [];

        foreach ($associationTypes as $assiociationType) {
            $choices[$assiociationType->getCode()] = $assiociationType->getLabel();
        }

        return $choices;
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
