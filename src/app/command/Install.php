<?php

namespace Yllumi\Sayagi\app\command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('sayagi:install', 'Install yllumi/sayagi: run plugin migration and publish config files.')]
class Install extends Command
{
    protected function configure(): void {}

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>[sayagi]</info> Starting installation...');

        if (!$this->runInstallMigration($output)) {
            return Command::FAILURE;
        }

        if (!$this->runInstallSeeder($output)) {
            return Command::FAILURE;
        }

        $this->publishFiles($output);
        $this->publishRouteCode($output);
        $this->renameIndexController($output);

        $output->writeln('<info>[sayagi]</info> Installation complete.');
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

    protected function publishRouteCode(OutputInterface $output): void
    {
        $routeFile = base_path() . '/config/route.php';

        if (!is_file($routeFile)) {
            $output->writeln('<comment>[sayagi]</comment> config/route.php not found, skipping route injection.');
            return;
        }

        $contents = file_get_contents($routeFile);
        $snippet  = '\\Yllumi\\Sayagi\\PageRouter::init();';

        if (str_contains($contents, $snippet)) {
            $output->writeln('<comment>[sayagi]</comment> Skipped (exists): PageRouter::init() already in config/route.php');
            return;
        }

        $addition = "\n// Page based route\n\\Yllumi\\Sayagi\\PageRouter::init();\n";
        file_put_contents($routeFile, rtrim($contents) . $addition);
        $output->writeln('<info>[sayagi]</info> Published: PageRouter::init() added to config/route.php');
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
