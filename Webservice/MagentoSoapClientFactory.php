<?php

namespace Pim\Bundle\MagentoConnectorBundle\Webservice;

use Pim\Bundle\MagentoConnectorBundle\Item\MagentoItemStep;

/**
 * A magento soap client factory
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MagentoSoapClientFactory
{
    /**
     * @var boolean
     */
    protected $activateLog;

    /**
     * @var string
     */
    protected $logDir;

    /**
     * Get a new magento soap client
     * @param MagentoSoapClientParameters $clientParameters
     *
     * @return MagentoSoapClient
     */
    public function getMagentoSoapClient(MagentoSoapClientParameters $clientParameters)
    {
        return new MagentoSoapClient($clientParameters, $this->logDir, $this->activateLog);
    }

    /**
     * @param boolean $activateLog
     */
    public function setActivateLog($activateLog)
    {
        $this->activateLog = $activateLog;
    }

    /**
     * Set log directory.
     *
     * @param string $logDir
     */
    public function setLogDir($logDir)
    {
        $this->logDir = $logDir;
    }
}
