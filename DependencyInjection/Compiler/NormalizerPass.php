<?php

namespace Pim\Bundle\MagentoConnectorBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Add normalizers to a registry to
 *
 * @author Romain Monceau <romain@akeneo.com>
 */
class NormalizerPass implements CompilerPassInterface
{
    /** @const string */
    const SERVICE_ID = 'pim_magento_connector.normalizer.registry';

    /** @const string */
    const TAG_NAME   = 'magento_normalizer';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $registryDef = $container->findDefinition(self::SERVICE_ID);

        foreach ($container->findTaggedServiceIds(static::TAG_NAME) as $serviceId => $tags) {
            foreach ($tags as $tag) {
                if (isset($tag['normalizer_key'])) {
                    $registryDef->addMethodCall(
                        'addNormalizer',
                        [$tag['normalizer_key'], new Reference($serviceId)]
                    );
                }
            }
        }
    }
}
