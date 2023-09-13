<?php
declare(strict_types=1);

namespace PBergman\AzureFileBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('PBergman_azure_file');
        $rootNode    = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                ->scalarNode('mime_types_file')
                    ->defaultValue('/etc/mime.types')
                ->end()
                ->arrayNode('shares')
                    ->useAttributeAsKey('share')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('share')->end()
                            ->scalarNode('account')->isRequired()->end()
                            ->scalarNode('key')->isRequired()->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('directories')
                    ->useAttributeAsKey('id')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('share')->isRequired()->end()
                            ->scalarNode('path')->isRequired()->end()
                        ->end()
                    ->end()
                ->end()

            ->end();

        return $treeBuilder;
    }
}