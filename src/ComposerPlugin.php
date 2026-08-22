<?php

namespace Yllumi\Sayagi;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;

class ComposerPlugin implements PluginInterface, EventSubscriberInterface
{
    protected Composer $composer;
    protected IOInterface $io;

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    public function deactivate(Composer $composer, IOInterface $io): void {}

    public function uninstall(Composer $composer, IOInterface $io): void {}

    public static function getSubscribedEvents(): array
    {
        return [
            PackageEvents::POST_PACKAGE_INSTALL => 'onPostPackageInstall',
            PackageEvents::POST_PACKAGE_UPDATE  => 'onPostPackageUpdate',
        ];
    }

    public function onPostPackageInstall(PackageEvent $event): void
    {
        $package = $event->getOperation()->getPackage();
        if ($package->getName() === 'yllumi/sayagi') {
            $this->publishFiles();
        }
    }

    public function onPostPackageUpdate(PackageEvent $event): void
    {
        $package = $event->getOperation()->getTargetPackage();
        if ($package->getName() === 'yllumi/sayagi') {
            $this->publishFiles();
        }
    }

    protected function publishFiles(): void
    {
        // Root project directory (where composer.json of the project lives)
        $projectRoot = $this->composer->getConfig()->get('vendor-dir') . '/..';
        $projectRoot = realpath($projectRoot) ?: rtrim($projectRoot, '/');

        // Semua logika salin-menyalin dipusatkan di command sayagi:install.
        \Yllumi\Sayagi\app\command\Install::publishTo(
            $projectRoot,
            fn(string $message) => $this->io->write($message)
        );
    }
}
