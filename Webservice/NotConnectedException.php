<?php

namespace Pim\Bundle\MagentoConnectorBundle\Webservice;

/**
 * Exception thrown if the client is not connected
 *
 * @author    Julien Sanchez <gildas@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class NotConnectedException extends \Exception
{
    /**
     * Constructor
     *
     * @param string $message
     */
    public function __construct($message = 'The soap client is not connected')
    {
        parent::__construct($message);
    }
}