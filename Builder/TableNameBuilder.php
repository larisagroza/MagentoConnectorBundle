<?php

namespace Pim\Bundle\MagentoConnectorBundle\Builder;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * Get the table name from the entity parameter name
 * Ease overriding entities managing with DBAL support avoiding hard-coded table names
 *
 * @author    Willy Mesnage <willy.mesnage@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class TableNameBuilder
{
    /** @var ContainerInterface */
    protected $container;

    /** @var ManagerRegistry */
    protected $managerRegistry;

    /**
     * Construct
     *
     * @param ContainerInterface $container
     * @param ManagerRegistry    $managerRegistry
     */
    public function __construct(ContainerInterface $container, ManagerRegistry $managerRegistry)
    {
        $this->container = $container;
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * Get table name from container parameter defined
     *
     * @param string $entityParameter
     * @param mixed  $targetEntity
     * @param bool   $isJoin
     *
     * @return string
     */
    public function getTableName($entityParameter, $targetEntity = null, $isJoin = false)
    {
        $entityClassName = $this->getEntityClassName($entityParameter);
        $classMetadata   = $this->getClassMetadata($entityClassName);

        if (null !== $targetEntity) {
            $assocMapping  = $classMetadata->getAssociationMapping($targetEntity);

            if (true === $isJoin) {
                $tableName = $assocMapping['joinTable']['name'];
            } else {
                $targetEntityClassName = $assocMapping['targetEntity'];
                $classMetadata = $this->getClassMetadata($targetEntityClassName);
                $tableName     = $classMetadata->getTableName();
            }
        } else {
            $tableName = $classMetadata->getTableName();
        }

        return $tableName;
    }

    /**
     * Returns class metadata for a defined entity parameter
     *
     * @param string $entityClassName
     *
     * @return \Doctrine\ORM\Mapping\ClassMetadata
     */
    protected function getClassMetadata($entityClassName)
    {
        $manager = $this->managerRegistry->getManagerForClass($entityClassName);

        return $manager->getClassMetadata($entityClassName);
    }

    /**
     * Get the entity class name from its parameter
     *
     * @param string $entityParameter
     *
     * @return mixed
     */
    protected function getEntityClassName($entityParameter)
    {
        return $this->container->getParameter($entityParameter);
    }
}
