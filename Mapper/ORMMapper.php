<?php

namespace Pim\Bundle\MagentoConnectorBundle\Mapper;

use Pim\Bundle\MagentoConnectorBundle\Manager\SimpleMappingManager;

/**
 * ORM mapper
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ORMMapper extends Mapper
{
    /**
     * @var SimpleMappingManager
     */
    protected $simpleMappingManager;

    /** @var string */
    protected $rootIdentifier;

    /**
     * @param SimpleMappingManager $simpleMappingManager
     * @param string               $rootIdentifier
     */
    public function __construct(
        SimpleMappingManager $simpleMappingManager,
        $rootIdentifier
    ) {
        $this->simpleMappingManager = $simpleMappingManager;
        $this->rootIdentifier       = $rootIdentifier;
    }

    /**
     * Get mapping
     * @return array
     */
    public function getMapping()
    {
        if (!$this->isValid()) {
            return new MappingCollection();
        }

        $simpleMappingItems = $this->simpleMappingManager->getMapping($this->getIdentifier($this->rootIdentifier));

        $mapping = new MappingCollection();
        foreach ($simpleMappingItems as $simpleMappingItem) {
            $mapping->add(
                array(
                    'source'    => $simpleMappingItem->getSource(),
                    'target'    => $simpleMappingItem->getTarget(),
                    'deletable' => true,
                )
            );
        }

        return $mapping;
    }

    /**
     * Set mapping
     * @param array $mapping
     */
    public function setMapping(array $mapping)
    {
        if (!$this->isValid()) {
            return;
        }

        $this->simpleMappingManager->setMapping($mapping, $this->getIdentifier($this->rootIdentifier));
    }

    /**
     * Get all targets
     * @return array
     */
    public function getAllTargets()
    {
        $targets = array();

        if ($this->isValid()) {
            $elements = $this->simpleMappingManager->getMapping($this->getIdentifier($this->rootIdentifier));

            foreach ($elements as $element) {
                $targets[] = array('id' => $element->getTarget(), 'text' => $element->getTarget());
            }
        }

        return $targets;
    }

    /**
     * Get all sources
     * @return array
     */
    public function getAllSources()
    {
        $sources = array();

        if ($this->isValid()) {
            $elements = $this->simpleMappingManager->getMapping($this->getIdentifier($this->rootIdentifier));

            foreach ($elements as $element) {
                $sources[] = array('id' => $element->getSource(), 'text' => $element->getSource());
            }
        }

        return $sources;
    }

    /**
     * Get mapper priority
     * @return integer
     */
    public function getPriority()
    {
        return 10;
    }
}
