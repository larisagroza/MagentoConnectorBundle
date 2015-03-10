<?php

namespace Pim\Bundle\MagentoConnectorBundle;

use Akeneo\Bundle\BatchBundle\Connector\Connector;
use Pim\Bundle\MagentoConnectorBundle\DependencyInjection\Compiler\NormalizerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Pim Magento connector to import/export data from magento platform.
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class PimMagentoConnectorBundle extends Connector
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new NormalizerPass());
    }
}
