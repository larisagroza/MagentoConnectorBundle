<?php

namespace Pim\Bundle\MagentoConnectorBundle\Mapper;

/**
 * Mapper
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Mapper implements MapperInterface
{
    const IDENTIFIER_FORMAT = '%s-%s';

    /**
     * Get mapping
     * @return array
     */
    public function getMapping()
    {
        return new MappingCollection();
    }

    /**
     * Set mapping
     * @param array $mapping
     */
    public function setMapping(array $mapping)
    {
    }

    /**
     * Get all targets
     * @return array
     */
    public function getAllTargets()
    {
        return array();
    }

    /**
     * Get all sources
     * @return array
     */
    public function getAllSources()
    {
        return array();
    }

    /**
     * Get mapper priority
     * @return integer
     */
    public function getPriority()
    {
        return 0;
    }

    /**
     * Get mapper identifier
     * @param string $rootIdentifier
     *
     * @return string
     */
    public function getIdentifier($rootIdentifier = 'generic')
    {
        if ($this->isValid()) {
            return sha1(sprintf(self::IDENTIFIER_FORMAT, $rootIdentifier, ''));
        } else {
            return '';
        }
    }

    /**
     * Is the mapper valid ?
     * @return boolean
     */
    public function isValid()
    {
        return true;
    }
}
