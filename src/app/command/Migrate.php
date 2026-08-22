<?php

namespace Yllumi\Sayagi\app\command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('migrate', 'Migrate the database')]
class Migrate extends Command
{
    /**
     * @return void
     */
    protected function configure()
    {
        $this->addArgument('plugin', InputArgument::OPTIONAL, 'Plugin path', '');
        $this->addOption('all', 'a', InputOption::VALUE_NONE, 'Run migrations for all plugins and sayagi core');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('all')) {
            return $this->migrateAll($output);
        }

        $plugin = $input->getArgument('plugin');

        // Pilah dulu path plugin
        if($plugin == 'sayagi') {
            $pluginPath = 'vendor/yllumi/sayagi/src';
        } else {
            $pluginPath = $plugin ? 'plugin/' . trim($plugin, '/') . '/' : '';
        }

        return $this->runMigration($pluginPath, $output);
    }

    /**
     * Run migrations for sayagi core and every plugin that ships database/migrations.
     */
    protected function migrateAll(OutputInterface $output): int
    {
        $output->writeln('<info>[migrate]</info> Running migrations for all plugins and sayagi core...');

        $targets = ['vendor/yllumi/sayagi/src'];

        foreach (glob(base_path('plugin/*')) ?: [] as $pluginDir) {
            if (!is_dir($pluginDir . '/database/migrations')) {
                continue;
            }
            $targets[] = 'plugin/' . basename($pluginDir) . '/';
        }

        foreach ($targets as $target) {
            $output->writeln('');
            $output->writeln('<info>[migrate]</info> Target: ' . $target);
            if ($this->runMigration($target, $output) !== self::SUCCESS) {
                return self::FAILURE;
            }
        }

        $output->writeln('');
        $output->writeln('<info>[migrate]</info> All migrations executed.');
        return self::SUCCESS;
    }

    /**
     * Run phinx migrate for a single plugin path.
     */
    protected function runMigration(string $pluginPath, OutputInterface $output): int
    {
        $command = 'PLUGIN_PATH=' . escapeshellarg($pluginPath)
            . ' ./vendor/bin/phinx migrate --configuration=vendor/yllumi/sayagi/src/config/migration.php';

        exec($command, $outputLines, $returnVar);
        foreach ($outputLines as $line) {
            $output->writeln($line);
        }
        return $returnVar === 0 ? self::SUCCESS : self::FAILURE;
    }

}
