<?php

namespace Yllumi\Sayagi\app\command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('db:seed', 'Run a specific seeder from yllumi/sayagi package.')]
class Seed extends Command
{
    protected function configure(): void
    {
        $this->addArgument('seeder', InputArgument::OPTIONAL, 'Seeder class name', '');
        $this->addArgument('plugin', InputArgument::OPTIONAL, 'Plugin path', '');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $seedClass = (string)$input->getArgument('seeder');
        $plugin = $input->getArgument('plugin');

        // Pilah dulu path plugin
        if($plugin == 'sayagi') {
            $pluginPath = 'vendor/yllumi/sayagi/src';
        } else {
            $pluginPath = $plugin ? 'plugin/' . trim($plugin, '/') . '/' : '';
        }

        $output->writeln('<info>[sayagi]</info> Running seeder: ' . $seedClass);

        $command = 'PLUGIN_PATH=' . escapeshellarg($pluginPath)
            . ' ./vendor/bin/phinx seed:run --configuration=vendor/yllumi/sayagi/src/config/migration.php'
            . ' --seed=' . escapeshellarg($seedClass);

        exec($command, $outputLines, $returnVar);
        foreach ($outputLines as $line) {
            $output->writeln($line);
        }

        return $returnVar === 0 ? self::SUCCESS : self::FAILURE;
    }
}
