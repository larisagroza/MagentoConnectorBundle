<?php

namespace Pim\Bundle\MagentoConnectorBundle\Cleaner;

use Akeneo\Bundle\BatchBundle\Step\StepExecutionAwareInterface;
use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Pim\Bundle\MagentoConnectorBundle\Item\MagentoItemStep;
use Pim\Bundle\MagentoConnectorBundle\Validator\Constraints\HasValidCredentials;

/**
 * Magento item cleaner
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 * @HasValidCredentials()
 */
abstract class Cleaner extends MagentoItemStep implements StepExecutionAwareInterface
{
    /** @const string */
    const DO_NOTHING = 'do_nothing';

    /** @const string */
    const DISABLE    = 'disable';

    /** @const string */
    const DELETE     = 'delete';

    /** @var StepExecution */
    protected $stepExecution;

    /** @var string */
    protected $notInPimAnymoreAction;

    /**
     * @return string notInPimAnymoreAction
     */
    public function getNotInPimAnymoreAction()
    {
        return $this->notInPimAnymoreAction;
    }

    /**
     * @param string $notInPimAnymoreAction notInPimAnymoreAction
     *
     * @return Cleaner
     */
    public function setNotInPimAnymoreAction($notInPimAnymoreAction)
    {
        $this->notInPimAnymoreAction = $notInPimAnymoreAction;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    abstract public function execute();

    /**
     * {@inheritdoc}
     */
    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->stepExecution = $stepExecution;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigurationFields()
    {
        return array_merge(
            parent::getConfigurationFields(),
            [
                'notInPimAnymoreAction' => [
                    'type'    => 'choice',
                    'options' => [
                        'choices'  => [
                            Cleaner::DO_NOTHING => 'pim_magento_connector.export.do_nothing.label',
                            Cleaner::DISABLE    => 'pim_magento_connector.export.disable.label',
                            Cleaner::DELETE     => 'pim_magento_connector.export.delete.label',
                        ],
                        'required' => true,
                        'help'     => 'pim_magento_connector.export.notInPimAnymoreAction.help',
                        'label'    => 'pim_magento_connector.export.notInPimAnymoreAction.label',
                        'attr'     => ['class' => 'select2'],
                    ],
                ]
            ]
        );
    }
}
