<?php

namespace Yllumi\Sayagi\app\command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

#[AsCommand('sayagi:publish-template', 'Publish a starter page template to app/pages/.')]
class PublishTemplate extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument(
                'type',
                InputArgument::OPTIONAL,
                'Template type to publish: <comment>basic</comment> or <comment>mobile</comment>'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Overwrite existing files'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $type = $input->getArgument('type');

        if (!$type) {
            $helper   = $this->getHelper('question');
            $question = new ChoiceQuestion(
                '<question> Which template type would you like to publish? </question>',
                ['basic', 'mobile'],
                0
            );
            $question->setErrorMessage('Type "%s" is not valid.');
            $type = $helper->ask($input, $output, $question);
        }

        $type = strtolower(trim($type));

        if (!in_array($type, ['basic', 'mobile'], true)) {
            $output->writeln("<error>[sayagi]</error> Unknown template type \"$type\". Choose <comment>basic</comment> or <comment>mobile</comment>.");
            return Command::FAILURE;
        }

        $packageRoot = dirname(__DIR__, 3);
        $srcDir      = $packageRoot . '/templates/' . $type . '_page';
        $destDir     = base_path() . '/app/pages';
        $force       = $input->getOption('force');

        if (!is_dir($srcDir)) {
            $output->writeln("<error>[sayagi]</error> Template source not found: $srcDir");
            return Command::FAILURE;
        }

        $output->writeln("<info>[sayagi]</info> Publishing <comment>$type</comment> template to <comment>app/pages/</comment>...");

        $published = 0;
        $skipped   = 0;

        $this->copyDirectory($srcDir, $destDir, $force, $output, $published, $skipped);

        $output->writeln('');
        $output->writeln("<info>[sayagi]</info> Done. Published: <info>$published</info>, Skipped: <comment>$skipped</comment>.");

        if ($skipped > 0 && !$force) {
            $output->writeln("<comment>[sayagi]</comment> Run with <comment>--force</comment> to overwrite existing files.");
        }

        return Command::SUCCESS;
    }

    private function copyDirectory(
        string $src,
        string $dest,
        bool $force,
        OutputInterface $output,
        int &$published,
        int &$skipped
    ): void {
        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }

        foreach (scandir($src) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $srcPath  = $src  . '/' . $item;
            $destPath = $dest . '/' . $item;

            if (is_dir($srcPath)) {
                $this->copyDirectory($srcPath, $destPath, $force, $output, $published, $skipped);
                continue;
            }

            if (is_file($destPath) && !$force) {
                $output->writeln("  <comment>skip</comment>  " . $this->relativePath($destPath));
                $skipped++;
                continue;
            }

            copy($srcPath, $destPath);
            $output->writeln("  <info>copy</info>  " . $this->relativePath($destPath));
            $published++;
        }
    }

    private function relativePath(string $absPath): string
    {
        $base = base_path();
        return ltrim(str_replace($base, '', $absPath), '/');
    }
}
