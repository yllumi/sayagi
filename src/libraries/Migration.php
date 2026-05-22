<?php

namespace Yllumi\Sayagi\libraries;

// Bootstrap webman config loader
require_once __DIR__ . '/../../../../autoload.php';
require_once __DIR__ . '/../../../../../support/bootstrap.php';

class Migration
{
    private $config = [];

    protected $pluginPath         = null;
    protected $migrationPath      = "database/migrations";
    protected $seedPath           = "database/seeds";
    protected $migrationTable     = 'ph_migrations';
    protected $defaultEnvironment = 'default';

    public function __construct($migrationPath = null)
    {
        $this->pluginPath = $migrationPath ? trim($migrationPath, '/') . '/' : '';
        $this->initConfig();
    }

    protected function initConfig()
    {
        $this->config = [
            'paths' => [
                'migrations' => './' . $this->pluginPath . $this->migrationPath,
                'seeds'      => './' . $this->pluginPath . $this->seedPath,
            ],
            'environments' => [
                'default_migration_table' => $this->migrationTable,
                'default_environment'     => $this->defaultEnvironment,
                'default' => [
                    'adapter' => getenv('DB_ADAPTER')  ? getenv('DB_ADAPTER') : 'mysql',
                    'host'    => getenv('DB_HOST')     ? getenv('DB_HOST') : 'localhost',
                    'name'    => getenv('DB_DATABASE') ? getenv('DB_DATABASE') : '',
                    'user'    => getenv('DB_USERNAME') ? getenv('DB_USERNAME') : '',
                    'pass'    => getenv('DB_PASSWORD') ? getenv('DB_PASSWORD') : '',
                    'port'    => getenv('DB_PORT')     ? getenv('DB_PORT') : '3306',
                    'charset' => 'utf8',
                ],
            ],
            'version_order' => 'creation',
        ];
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function getMigrationPath()
    {
        return './' . $this->pluginPath . $this->migrationPath;
    }
}
