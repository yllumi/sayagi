<?php

namespace Yllumi\Sayagi\app\command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('sayagi:install-admin', 'Install yllumi/sayagi database: run install migration and seeder.')]
class InstallAdmin extends Command
{
    protected function configure(): void {}

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>[sayagi]</info> Starting database installation...');

        if (!$this->runInstallMigration($output)) {
            return Command::FAILURE;
        }

        if (!$this->runInstallSeeder($output)) {
            return Command::FAILURE;
        }

        $output->writeln('<info>[sayagi]</info> Database installation complete.');
        return Command::SUCCESS;
    }

    protected function runInstallMigration(OutputInterface $output): bool
    {
        $projectRoot = base_path();
        $migrationDir = $projectRoot . '/vendor/yllumi/sayagi/src/database/migrations';

        $migrationFiles = glob($migrationDir . '/*_install_plugin.php') ?: [];
        if (!$migrationFiles) {
            $output->writeln('<error>[sayagi]</error> install_plugin migration file not found.');
            return false;
        }

        usort($migrationFiles, static function (string $left, string $right): int {
            return strcmp($left, $right);
        });

        $migrationFile = end($migrationFiles);
        $migrationBaseName = basename($migrationFile ?: '');
        preg_match('/^(\d+)_install_plugin\.php$/', $migrationBaseName, $matches);
        $targetVersion = $matches[1] ?? null;

        if (!$targetVersion) {
            $output->writeln('<error>[sayagi]</error> Unable to resolve install_plugin migration version.');
            return false;
        }

        $command = 'PLUGIN_PATH=' . escapeshellarg('vendor/yllumi/sayagi/src/')
            . ' ./vendor/bin/phinx migrate --configuration=vendor/yllumi/sayagi/src/config/migration.php'
            . ' --target=' . escapeshellarg($targetVersion);

        exec($command, $outputLines, $returnVar);
        foreach ($outputLines as $line) {
            $output->writeln($line);
        }

        if ($returnVar !== 0) {
            $output->writeln('<error>[sayagi]</error> Failed running install_plugin migration.');
            return false;
        }

        $output->writeln('<info>[sayagi]</info> install_plugin migration executed.');
        return true;
    }

    protected function runInstallSeeder(OutputInterface $output): bool
    {
        $projectRoot = base_path();
        $seedDir = $projectRoot . '/vendor/yllumi/sayagi/src/database/seeds';
        $seedClass = 'SayagiInitSeeder';

        if (!is_file($seedDir . '/' . $seedClass . '.php')) {
            $output->writeln('<comment>[sayagi]</comment> Seeder not found, skipping.');
            return true;
        }

        $command = 'PLUGIN_PATH=' . escapeshellarg('vendor/yllumi/sayagi/src/')
            . ' ./vendor/bin/phinx seed:run --configuration=vendor/yllumi/sayagi/src/config/migration.php'
            . ' --seed=' . escapeshellarg($seedClass);

        exec($command, $outputLines, $returnVar);
        foreach ($outputLines as $line) {
            $output->writeln($line);
        }

        if ($returnVar !== 0) {
            $output->writeln('<error>[sayagi]</error> Failed running install seeder.');
            return false;
        }

        $output->writeln('<info>[sayagi]</info> install seeder executed.');
        return true;
    }
}
