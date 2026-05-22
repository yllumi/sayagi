<?php

namespace Yllumi\Sayagi\app\command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('sayagi:update', 'Update yllumi/sayagi: publish missing config files and run package migrations.')]
class Update extends Install
{
    protected function configure(): void {}

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>[sayagi]</info> Starting update...');

        $this->publishFiles($output);

        if (!$this->runPackageMigrations($output)) {
            return Command::FAILURE;
        }

        $output->writeln('<info>[sayagi]</info> Update complete.');
        return Command::SUCCESS;
    }

    protected function runPackageMigrations(OutputInterface $output): bool
    {
        $projectRoot = base_path();
        $migrationDir = $projectRoot . '/vendor/yllumi/sayagi/src/database/migrations';

        $migrationFiles = glob($migrationDir . '/*.php') ?: [];
        if (!$migrationFiles) {
            $output->writeln('<comment>[sayagi]</comment> No package migration files found.');
            return true;
        }

        $command = 'PLUGIN_PATH=' . escapeshellarg('vendor/yllumi/sayagi/src/')
            . ' ./vendor/bin/phinx migrate --configuration=vendor/yllumi/sayagi/src/config/migration.php';

        exec($command, $outputLines, $returnVar);
        foreach ($outputLines as $line) {
            $output->writeln($line);
        }

        if ($returnVar !== 0) {
            $output->writeln('<error>[sayagi]</error> Failed running package migrations.');
            return false;
        }

        $output->writeln('<info>[sayagi]</info> Package migrations executed.');
        return true;
    }
}
