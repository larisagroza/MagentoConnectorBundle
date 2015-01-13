<?php

namespace Pim\Bundle\MagentoConnectorBundle\Reader\ORM;

use Doctrine\ORM\EntityManager;
use Pim\Bundle\BaseConnectorBundle\Reader\ORM\EntityReader;
use Pim\Bundle\CatalogBundle\Manager\ChannelManager;
use Pim\Bundle\MagentoConnectorBundle\Entity\Repository\CategoryRepository;

/**
 * ORM reader for categories
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class CategoryReader extends EntityReader
{
    /** @var CategoryRepository */
    protected $repository;

    /** @var ChannelManager */
    protected $channelManager;

    /**
     * @param EntityManager      $em
     * @param string             $className
     * @param CategoryRepository $repository
     * @param ChannelManager     $channelManager
     */
    public function __construct(
        EntityManager $em,
        $className,
        CategoryRepository $repository,
        ChannelManager $channelManager
    ) {
        parent::__construct($em, $className);

        $this->repository     = $repository;
        $this->channelManager = $channelManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getQuery()
    {
        if (!$this->query) {
            $channel = $this->channelManager->getChannelByCode($this->channel);

            $this->query = $this->repository
                ->findOrderedCategories($channel->getCategory())
                ->getQuery();
        }

        return $this->query;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigurationFields()
    {
        return array_merge(
            parent::getConfigurationFields(),
            array(
                'channel' => array(
                    'type'    => 'choice',
                    'options' => array(
                        'choices'  => $this->channelManager->getChannelChoices(),
                        'required' => true,
                        'select2'  => true,
                        'label'    => 'pim_base_connector.export.channel.label',
                        'help'     => 'pim_base_connector.export.channel.help'
                    )
                )
            )
        );
    }

    /**
     * Set channel code
     *
     * @param string $channel
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;
    }

    /**
     * Get channel code
     *
     * @return string
     */
    public function getChannel()
    {
        return $this->channel;
    }
}
