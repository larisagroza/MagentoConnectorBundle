<?php

namespace Pim\Bundle\MagentoConnectorBundle\Manager;

use Doctrine\Common\Persistence\ObjectManager;
use Pim\Bundle\MagentoConnectorBundle\Entity\SimpleMapping;

/**
 * Mapping manager.
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class SimpleMappingManager
{
    /**
     * @var \Doctrine\Common\Persistence\ObjectManager
     */
    protected $objectManager;

    /** @var string */
    protected $className;

    /** @var array */
    protected $savedMapping = [];

    /**
     * Constructor.
     *
     * @param ObjectManager $objectManager
     * @param string        $className
     */
    public function __construct(ObjectManager $objectManager, $className)
    {
        $this->objectManager = $objectManager;
        $this->className     = $className;
    }

    /**
     * Get mapping for given identifier.
     *
     * @param string $identifier
     *
     * @return array
     */
    public function getMapping($identifier)
    {
        return $this->getEntityRepository()->findBy(array('identifier' => $identifier));
    }

    /**
     * Set mapping to database for given identifier.
     *
     * @param array  $mapping
     * @param string $identifier
     */
    public function setMapping(array $mapping, $identifier)
    {
        if (!$this->isMappingSaved($mapping, $identifier)) {
            $this->pruneOldMapping($identifier);

            foreach ($mapping as $mappingItem) {
                if ($mappingItem['source'] != '') {
                    $simpleMappingItem = new SimpleMapping();
                    $simpleMappingItem->setIdentifier($identifier);
                    $simpleMappingItem->setSource($mappingItem['source']);
                    $simpleMappingItem->setTarget($mappingItem['target']);
                    $this->objectManager->persist($simpleMappingItem);
                }
            }

            $this->objectManager->flush();
            $this->updateSavedMapping($mapping, $identifier);
        }
    }

    /**
     * Update saved mapping
     *
     * @param array  $mapping
     * @param string $identifier
     */
    protected function updateSavedMapping(array $mapping, $identifier)
    {
        $this->savedMapping[] = $this->getKey($mapping, $identifier);
    }

    /**
     * Checks if given mapping is saved
     *
     * @param array  $mapping
     * @param string $identifier
     *
     * @return bool
     */
    protected function isMappingSaved(array $mapping, $identifier)
    {
        return in_array($this->getKey($mapping, $identifier), $this->savedMapping);
    }

    /**
     * Returns key from mapping and identifier
     *
     * @param array  $mapping
     * @param string $identifier
     *
     * @return string
     */
    protected function getKey(array $mapping, $identifier)
    {
        return md5(sprintf('%s%s', json_encode($mapping), $identifier));
    }

    /**
     * Prune old instance of simple mapping for the given identifier.
     *
     * @param string $identifier
     */
    protected function pruneOldMapping($identifier)
    {
        $oldMappingItems = $this->getEntityRepository()->findBy(array('identifier' => $identifier));

        foreach ($oldMappingItems as $oldMappingItem) {
            $this->objectManager->remove($oldMappingItem);
        }

        $this->objectManager->flush();
    }

    /**
     * Get the entity manager.
     *
     * @return EntityRepository
     */
    protected function getEntityRepository()
    {
        return $this->objectManager->getRepository($this->className);
    }
}
