<?php
declare(strict_types=1);

namespace PBergman\Bundle\AzureFileBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('pbergman_azure_file');
        $rootNode    = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                ->scalarNode('mime_types_file')
                    ->info('For none linux systems a url can be used like: http://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types')
                    ->defaultValue('/etc/mime.types')
                ->end()
                ->scalarNode('http_client')
                    ->defaultValue('http_client')
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