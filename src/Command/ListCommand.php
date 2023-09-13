<?php
declare(strict_types=1);

namespace PBergman\Bundle\AzureFileBundle\Command;

use PBergman\Bundle\AzureFileBundle\Model\Directory;
use PBergman\Bundle\AzureFileBundle\Model\FileInfo;
use PBergman\Bundle\AzureFileBundle\Model\ListResult;
use PBergman\Bundle\AzureFileBundle\RestApi\FileApi;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends Command
{
    protected static $defaultName = 'azure:directory:list';

    private array $registry;

    public function register($name, FileApi $api)
    {
        $this->registry[$name] = $api;
    }

    protected function configure()
    {
        $this
            ->addArgument('directory', InputArgument::REQUIRED, 'Directory name used in config')
            ->addOption('recursive', 'r', InputOption::VALUE_NONE, 'Print recursive files')
            ->addOption('prefix', 'p', InputOption::VALUE_REQUIRED, 'Search for given prefix');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (null === $api = ($this->registry[$input->getArgument("directory")] ?? null)) {
            throw new \RuntimeException('No directory defined for "' . $input->getArgument("directory") . '"');
        }

        $result = $this->list($api, null, $input->getOption('prefix'));
        $total  = count($result->getEntries());
        $table  = new Table($output);
        $table->setHeaders([
            'name', 'size', 'etag', 'directory'
        ]);

        foreach ($result->getEntries() as $entry) {

            $this->addRow($table, $entry, $input->getOption('recursive') ? $this->createPath($result, null) : null);

            if ($input->getOption('recursive') && $entry instanceof Directory) {
                $this->recursive($table, $this->createPath($result, $entry), $input->getOption('prefix'), $api, $total);
            }
        }

        $table->setHeaderTitle(sprintf('[share: %s | path: %s | total files: %d]', $result->getShareName(), $result->getDirectoryPath(), $total));
        $table->render();

        return 0;
    }

    private function addRow(Table $table, FileInfo $entry, ?string $path = null): void
    {
        $table->addRow([
            (($path === null) ? $entry->getName() : $path . '/' . $entry->getName()),
            $entry->getProperties()->getContentLength(),
            $entry->getProperties()->getEtag(),
            (($entry instanceof Directory) ? '✓' : '✗')
        ]);
    }

    private function createPath(ListResult $result, ?FileInfo $fileInfo): string
    {
        return sprintf('/%s/%s%s', $result->getShareName(), $result->getDirectoryPath(), (null !== $fileInfo) ? $fileInfo->getName() : null);
    }

    private function list(FileApi $api, ?string $path = null, ?string $prefix = null): ListResult
    {
        return $api->list($path, $prefix, null, false, FileApi::LIST_INCLUDE_ETAGS);
    }

    private function recursive(Table $table, string $path, ?string $prefix, FileApi $api, int &$total): void
    {
        $result = $this->list($api, $path, $prefix);
        $total += count($result->getEntries());

        foreach ($result->getEntries() as $entry) {

            $this->addRow($table, $entry, $path);

            if ($entry instanceof Directory) {
                $this->recursive($table, $path . '/' . $entry->getName(), $prefix, $api, $total);
            }
        }
    }
}