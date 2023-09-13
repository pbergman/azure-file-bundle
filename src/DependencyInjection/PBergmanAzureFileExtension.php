<?php
declare(strict_types=1);

namespace PBergman\Bundle\AzureFileBundle\DependencyInjection;

use PBergman\Bundle\AzureFileBundle\Authorize\SharedKeyLiteAuthorizer;
use PBergman\Bundle\AzureFileBundle\Command\GetCommand;
use PBergman\Bundle\AzureFileBundle\Command\ListCommand;
use PBergman\Bundle\AzureFileBundle\RestApi\Client;
use PBergman\Bundle\AzureFileBundle\RestApi\FileApi;
use PBergman\Bundle\AzureFileBundle\Util\MimeTypeGuesser;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpClient\ScopingHttpClient;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\String\UnicodeString;

class PBergmanAzureFileExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(dirname(__FILE__, 2) . '/Resources/config'));
        $loader->load('services.xml');
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container
            ->getDefinition(MimeTypeGuesser::class)
            ->setArgument(1, $config['mime_types_file']);

        $sharesReferences = [];
        $clientReferences = [];

        foreach ($config['shares'] as $id => $share) {
            $sharesReferences[$id] = new Reference($this->createFileApiAuthorizerService($container, $id, $share));
            $clientReferences[$id] = new Reference($this->createFileApiClient($container, $sharesReferences[$id], $id));
        }

        foreach ($config['directories'] as $id => $info) {

            if (false === array_key_exists($info['share'], $sharesReferences)) {
                throw new \RuntimeException(sprintf('No share defined by name "%s" (available: "%s"', $info['share'], implode('", "', array_keys($sharesReferences))));
            }

            $this->createFileApiService(
                $container,
                $sharesReferences[$info['share']],
                $clientReferences[$info['share']],
                $config['shares'][$info['share']]['account'],
                $info['share'],
                $id,
                $info['path']
            );
        }
    }

    private function createFileApiService(ContainerBuilder $container, Reference $auth, Reference $client, string $account, string $share, string $name, ?string $root = null)
    {
        $name = new UnicodeString($name);
        $def  = (string)$name->snake()->prepend('pbergman.azure_file.file_api.');

        if (null !== $root) {
            // check for slash in start and end of string and if not append, see
            // https://symfony.com/doc/current/reference/configuration/framework.html#reference-http-client-base-uri
            if ('/' !== $root[0]) {
                $root = '/' . $root;
            }

            if ('/' !== $root[-1]) {
                $root .= '/';
            }
        }

        $baseUri    = sprintf('https://%s.file.core.windows.net/%s%s', $account, $share, $root);
        $clientName = (string)$name->snake()->ensureEnd('.client');

        $container
            ->register($clientName, ScopingHttpClient::class)
            ->setFactory([ScopingHttpClient::class, 'forBaseUri'])
            ->setArguments([$client, $baseUri, ['extra' => ['trace_content' => true]]])
            ->addTag('http_client.client');

        $container
            ->register($def, FileApi::class)
            ->setArguments([
                new Reference(SerializerInterface::class),
                new Reference($clientName->toString()),
                $auth,
                $share,
            ]);

        $container
            ->getDefinition(ListCommand::class)
            ->addMethodCall('register', [$name->toString(), new Reference($def)]);

        $container
            ->getDefinition(GetCommand::class)
            ->addMethodCall('register', [$name->toString(), new Reference($def)]);

        $container->registerAliasForArgument($def, FileApi::class, (string)$name->camel()->ensureEnd('AzureFileApi'));
    }

    private function createFileApiAuthorizerService(ContainerBuilder $container, string $name, array $config): string
    {
        $name = (string)(new UnicodeString($name))->snake()->prepend('pbergman.azure_file.authorizer.');

        if (false === $container->hasDefinition($name)) {
            $definition = new Definition(SharedKeyLiteAuthorizer::class, [$config['account'], $config['key']]);
            $definition->setPublic(false);
            $container->setDefinition($name, $definition);
        }

        return $name;
    }

    private function createFileApiClient(ContainerBuilder $container, Reference $auth, string $name): string
    {
        $name = (string)(new UnicodeString($name))->snake()->prepend('pbergman.azure_file.http_client_');

        $container
            ->register($name, Client::class)
            ->setArguments([new Reference('http_client'), $auth]);

        return $name;
    }
}