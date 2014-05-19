<?php

namespace Pompdelux\Bundle\KrakenBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class KrakenExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $config = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($config, $configs);

        foreach ($config['services'] as $name => $settings) {
            // until we can use file attachments in Guzzle in php 5.5 we block use of the upload type
            if ('upload' === $settings['type']) {
                throw new \ConfigurationException('The "upload" type is currently disabled due to PHP 5.5 cURL issues.');
            }

            $def = new Definition($container->getParameter('pompdelux.kraken.service.class'));
            $def->setPublic(true);
            $def->setScope(ContainerInterface::SCOPE_CONTAINER);
            $def->addArgument(new Reference('hanzo.kraken.guzzle.'.$settings['type'].'.service'));
            $def->addArgument(new Reference('logger'));
            $def->addArgument(new Reference('router'));
            $def->addArgument($settings['api_key']);
            $def->addArgument($settings['api_secret']);
            $def->addArgument($settings['type']);
            $def->addArgument($settings['use_lossy']);

            $callback = $settings['callback'] ? $settings['callback_route'] : null;
            $def->addArgument($callback);

            $container->setDefinition(sprintf('pompdelux.kraken.%s', $name), $def);
        }
    }
}
