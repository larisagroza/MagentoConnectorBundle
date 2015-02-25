<?php

namespace Pim\Bundle\MagentoConnectorBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;

/**
 * Magento connector bundle extension
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class PimMagentoConnectorExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('cleaners.yml');
        $loader->load('entities.yml');
        $loader->load('guessers.yml');
        $loader->load('mappers.yml');
        $loader->load('mergers.yml');
        $loader->load('managers.yml');
        $loader->load('normalizers.yml');
        $loader->load('processors.yml');
        $loader->load('purgers.yml');
        $loader->load('readers.yml');
        $loader->load('repositories.yml');
        $loader->load('services.yml');
        $loader->load('validators.yml');
        $loader->load('webservices.yml');
        $loader->load('writers.yml');

        $storageDriver = $container->getParameter('pim_catalog_storage_driver');
        $storageConfig = sprintf('storage_driver/%s.yml', $storageDriver);
        if (file_exists(__DIR__ . '/../Resources/config/' . $storageConfig)) {
            $loader->load($storageConfig);
        }
    }
}
