<?php
declare(strict_types=1);

namespace PBergman\Bundle\AzureFileBundle\Command;

use PBergman\Bundle\AzureFileBundle\RestApi\FileApi;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GetCommand extends Command
{
    protected static $defaultName = 'azure:directory:get-file';

    private array $registry;

    public function register($name, FileApi $api)
    {
        $this->registry[$name] = $api;
    }

    protected function configure()
    {
        $this
            ->addArgument('directory', InputArgument::REQUIRED, 'Directory name used in config')
            ->addArgument('name', InputArgument::REQUIRED, 'The File to download');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var FileApi $api  */
        if (null === $api = ($this->registry[$input->getArgument("directory")] ?? null)) {
            throw new \RuntimeException('No directory defined for "' . $input->getArgument("directory") . '"');
        }

        file_put_contents($input->getArgument('name'), (string)$api->getFile($input->getArgument('name')));

        return 0;
    }
}