<?php
declare(strict_types=1);

namespace PBergman\Bundle\AzureFileBundle\Command;

use PBergman\Bundle\AzureFileBundle\RestApi\FileApi;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'azure:directory:get-file',
    description: 'Download file from remote azure environment.'
)]
class GetCommand
{
    use ApiRegistryTrait;

    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: 'Directory name used in config.', suggestedValues: [self::class, 'getDirectories'])] string $directory,
        #[Argument('The File to download.')] string $name, OutputInterface $output
    ): int
    {
        try {

            $api  = $this->getApiForDirectory($directory);
            $file = $api->getFile($name);

            if (false === $file->exists()) {
                throw new \RuntimeException(sprintf('<error>File "%s" not found (status: %d)</error>', $name, $file->getStatus()));
            }

            if (false === \file_put_contents($name, (string)$file)) {
                throw new \RuntimeException(sprintf('<error>Could not save file "%s"</error>', $name));
            }

            return Command::SUCCESS;

        } catch (\Throwable $exception) {
            $io->error($exception->getMessage());
            return Command::FAILURE;
        }

    }
}