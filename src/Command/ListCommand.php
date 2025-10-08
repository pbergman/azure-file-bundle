<?php
declare(strict_types=1);

namespace PBergman\Bundle\AzureFileBundle\Command;

use PBergman\Bundle\AzureFileBundle\Model\Directory;
use PBergman\Bundle\AzureFileBundle\Model\FileInfo;
use PBergman\Bundle\AzureFileBundle\Model\ListResult;
use PBergman\Bundle\AzureFileBundle\RestApi\FileApi;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'azure:directory:list',
    description: 'Get files by path fom remote azure environment',
)]
class ListCommand
{
    use ApiRegistryTrait;

    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: 'Directory name used in config.', suggestedValues: [self::class, 'getDirectories'])] string $directory,
        #[Option(description: 'Print recursive files.', shortcut: 'r')] bool $recursive = false,
        #[Option(description: 'Search for given prefix.', shortcut: 'p')] ?string $prefix = null,
    ): int
    {

        try {

            $api       = $this->getApiForDirectory($directory);
            $result    = $this->list($api, null, $prefix);
            $total     = \count($result->getEntries());
            $isVerbose = $io->isVerbose();

            $table  = $io->createTable();
            $table->setStyle($isVerbose ? 'compact' : 'default');
            $table->setHeaders($isVerbose ? ['name', 'size', 'etag', 'created', 'updated', 'directory'] : ['name', 'size', 'etag', 'directory']);

            foreach ($result->getEntries() as $entry) {

                $this->addRow($table, $entry, $isVerbose, $recursive ? $this->createPath($result, null) : null);

                if ($recursive && $entry instanceof Directory) {
                    $this->recursive($table, $isVerbose, $this->createPath($result, $entry), $prefix, $api, $total);
                }
            }

            $table->setHeaderTitle(sprintf('[share: %s | path: %s | total files: %d]', $result->getShareName(), $result->getDirectoryPath(), $total));
            $table->render();

            return Command::SUCCESS;
        } catch (\Throwable $exception) {
            $io->error($exception->getMessage());
            return Command::FAILURE;
        }
    }

    private function addRow(Table $table, FileInfo $entry, bool $verbose, ?string $path = null): void
    {
        if ($verbose) {
            $table->addRow([
                (($path === null) ? $entry->getName() : $path . '/' . $entry->getName()),
                $entry->getProperties()->getContentLength(),
                $entry->getProperties()->getEtag(),
                $this->fmtDateTime($entry->getProperties()->getCreationTime()),
                $this->fmtDateTime($entry->getProperties()->getChangeTime()),
                (($entry instanceof Directory) ? '✓' : '✗'),
            ]);

        }  else {
            $table->addRow([
                (($path === null) ? $entry->getName() : $path . '/' . $entry->getName()),
                $entry->getProperties()->getContentLength(),
                $entry->getProperties()->getEtag(),
                (($entry instanceof Directory) ? '✓' : '✗'),
            ]);
        }

    }

    private function fmtDateTime(?\DateTimeInterface $date): string
    {
        if (null == $date) {
            return '';
        }

        return $date->format(\DateTimeInterface::ATOM);
    }

    private function createPath(ListResult $result, ?FileInfo $fileInfo): string
    {
        return sprintf('/%s/%s%s', $result->getShareName(), $result->getDirectoryPath(), (null !== $fileInfo) ? $fileInfo->getName() : null);
    }

    private function list(FileApi $api, ?string $path = null, ?string $prefix = null): ListResult
    {
        return $api->list($path, $prefix, null, false, FileApi::LIST_INCLUDE_TIMESTAMPS|FileApi::LIST_INCLUDE_ETAGS);
    }

    private function recursive(Table $table, bool $verbose, string $path, ?string $prefix, FileApi $api, int &$total): void
    {
        $result = $this->list($api, $path, $prefix);
        $total += count($result->getEntries());

        foreach ($result->getEntries() as $entry) {

            $this->addRow($table, $entry, $verbose, $path);

            if ($entry instanceof Directory) {
                $this->recursive($table, $verbose, $path . '/' . $entry->getName(), $prefix, $api, $total);
            }
        }
    }
}
