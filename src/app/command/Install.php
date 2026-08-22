<?php

namespace Yllumi\Sayagi\app\command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

#[AsCommand('sayagi:install', 'Install yllumi/sayagi: publish config files and starter page template (non-database).')]
class Install extends Command
{
    protected function configure(): void {}

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>[sayagi]</info> Starting installation...');

        $this->publishFiles($output);
        $this->replaceConfigView($output);
        $this->publishTemplate($input, $output);
        $this->renameIndexController($output);

        $output->writeln('<info>[sayagi]</info> Installation complete.');
        return Command::SUCCESS;
    }

    protected function publishFiles(OutputInterface $output): void
    {
        $projectRoot  = base_path();
        $targetDir    = $projectRoot . '/config/plugin/panel';
        $packageSrc   = dirname(__DIR__, 2);
        $packageRoot  = dirname($packageSrc);

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
            $output->writeln('<info>[sayagi]</info> Created: config/plugin/panel/');
        }

        $this->copyFile($packageSrc . '/menu.yml', $targetDir . '/menu.yml', $output);
        $this->copyFile($packageSrc . '/privileges.yml', $targetDir . '/privileges.yml', $output);
        $this->copyDirectory($packageSrc . '/settings', $targetDir . '/settings', $output);

        // Publish admin panel theme assets → public/panel_theme/
        $this->copyDirectory($packageRoot . '/panel_theme', $projectRoot . '/public/panel_theme', $output);

        // Publish frontend SPA page theme → public/page_theme/
        $this->copyDirectory($packageRoot . '/page_theme', $projectRoot . '/public/page_theme', $output);

        // Copy all config files to config/plugin/yllumi/sayagi/
        $pluginConfigDir = $projectRoot . '/config/plugin/yllumi/sayagi';
        if (!is_dir($pluginConfigDir)) {
            mkdir($pluginConfigDir, 0755, true);
            $output->writeln('<info>[sayagi]</info> Created: config/plugin/yllumi/sayagi/');
        }
        // Publish webman config files (exclude migration.php — phinx-only, not for webman)
        foreach (glob($packageSrc . '/config/*.php') ?: [] as $configFile) {
            if (basename($configFile) === 'migration.php') {
                continue;
            }
            $this->copyFile($configFile, $pluginConfigDir . '/' . basename($configFile), $output);
        }
    }

    /**
     * Ganti config/view.php bawaan webman dengan versi package (handler Raw).
     * File lama di-rename ke .bak, lalu src/config/view.php disalin ke config/.
     * Idempotent: bila .bak sudah ada, langkah dilewati (tidak menimpa lagi).
     */
    protected function replaceConfigView(OutputInterface $output): void
    {
        $projectRoot = base_path();
        $srcFile     = dirname(__DIR__, 2) . '/config/view.php';
        $destFile    = $projectRoot . '/config/view.php';
        $backupFile  = $destFile . '.bak';

        if (is_file($backupFile)) {
            $output->writeln('<comment>[sayagi]</comment> config/view.php.bak exists, skipping replacement.');
            return;
        }

        if (is_file($destFile)) {
            rename($destFile, $backupFile);
            $output->writeln('<info>[sayagi]</info> Renamed: config/view.php → config/view.php.bak');
        }

        copy($srcFile, $destFile);
        $output->writeln('<info>[sayagi]</info> Published: ' . $destFile);
    }

    /**
     * Ask which starter page template (basic or mobile) should be copied
     * into app/pages/, mirroring the sayagi:publish-template command.
     */
    protected function publishTemplate(InputInterface $input, OutputInterface $output): void
    {
        $helper   = $this->getHelper('question');
        $question = new ChoiceQuestion(
            '<question>Publish starter page template to app/pages/?</question>',
            ['basic', 'mobile', 'skip'],
            0
        );
        $question->setErrorMessage('Template type "%s" is not valid.');
        $type = $helper->ask($input, $output, $question);

        if ($type === 'skip') {
            $output->writeln('<comment>[sayagi]</comment> Skipped publishing starter page template.');
            return;
        }

        $packageRoot = dirname(__DIR__, 3);
        $srcDir      = $packageRoot . '/templates/' . $type . '_page';
        $destDir     = base_path() . '/app/pages';

        if (!is_dir($srcDir)) {
            $output->writeln('<error>[sayagi]</error> Template source not found: ' . $srcDir);
            return;
        }

        $output->writeln('<info>[sayagi]</info> Publishing <comment>' . $type . '</comment> template to <comment>app/pages/</comment>...');
        $this->copyDirectory($srcDir, $destDir, $output);
    }

    protected function renameIndexController(OutputInterface $output): void
    {
        $file = base_path() . '/app/controller/IndexController.php';

        if (!is_file($file)) {
            $output->writeln('<comment>[sayagi]</comment> IndexController.php not found, skipping.');
            return;
        }

        rename($file, $file . '.bak');
        $output->writeln('<info>[sayagi]</info> Renamed: app/controller/IndexController.php → IndexController.php.bak');
    }

    protected function copyFile(string $src, string $dest, OutputInterface $output): void
    {
        if (!is_file($src)) {
            return;
        }

        if (is_file($dest)) {
            $output->writeln('<comment>[sayagi]</comment> Skipped (exists): ' . basename($dest));
            return;
        }

        copy($src, $dest);
        $output->writeln('<info>[sayagi]</info> Published: ' . $dest);
    }

    protected function copyDirectory(string $src, string $dest, OutputInterface $output): void
    {
        if (!is_dir($src)) {
            return;
        }

        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }

        foreach (scandir($src) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $srcPath  = $src  . '/' . $item;
            $destPath = $dest . '/' . $item;

            is_dir($srcPath)
                ? $this->copyDirectory($srcPath, $destPath, $output)
                : $this->copyFile($srcPath, $destPath, $output);
        }
    }
}
