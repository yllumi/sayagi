<?php

namespace Yllumi\Sayagi\app\command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('make:migration', 'Make migration class')]
class MakeMigration extends Command
{
    /**
     * @return void
     */
    protected function configure()
    {
        $this->addArgument('name', InputArgument::OPTIONAL, 'Migration class name');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');
        
        // run ./vendor/bin/phinx create $name
        $command = sprintf('./vendor/bin/phinx create %s --configuration=config/migration.php', $name);
        exec($command, $outputLines, $returnVar);
        foreach ($outputLines as $line) {
            $output->writeln($line);
        }
        return $returnVar === 0 ? self::SUCCESS : self::FAILURE;
    }

}
