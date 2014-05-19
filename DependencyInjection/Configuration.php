<?php

namespace Pompdelux\KrakenBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('pompdelux_kraken');

        $rootNode
            ->children()
                ->arrayNode('services')
                    ->isRequired()
                    ->requiresAtLeastOneElement()
                    ->useAttributeAsKey('alias', false)
                    ->prototype('array')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('api_key')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('api_secret')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->enumNode('type')
                                ->values(['url', 'upload'])
                                ->defaultValue('url')
                            ->end()
                            ->booleanNode('use_lossy')->defaultTrue()->end()
                            ->booleanNode('callback')->defaultFalse()->end()
                            ->scalarNode('callback_route')->defaultValue('hanzo_kraken_callback')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
