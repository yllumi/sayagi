<?php

namespace Yllumi\Sayagi\app\command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use support\Db;

#[AsCommand('sayagi:install-admin', 'Install yllumi/sayagi database: run install migration and seeder.')]
class InstallAdmin extends Command
{
    /**
     * Key DB_* yang wajib ada di file .env.
     */
    private const DB_ENV_KEYS = ['DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'];

    /**
     * Nilai placeholder bawaan webman yang dianggap belum dikonfigurasi.
     */
    private const DB_ENV_PLACEHOLDERS = ['your_database', 'your_username', 'your_password'];

    protected function configure(): void {}

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>[sayagi]</info> Starting database installation...');

        if (!$this->ensureEnvFile($input, $output)) {
            return Command::FAILURE;
        }

        if (!$this->ensureDatabaseConfig($output)) {
            return Command::FAILURE;
        }

        if (!$this->checkDatabase($output)) {
            return Command::FAILURE;
        }

        if (!$this->runInstallMigration($output)) {
            return Command::FAILURE;
        }

        if (!$this->runInstallSeeder($output)) {
            return Command::FAILURE;
        }

        if (!$this->createFirstUser($input, $output)) {
            return Command::FAILURE;
        }

        $output->writeln('<info>[sayagi]</info> Database installation complete.');
        return Command::SUCCESS;
    }

    /**
     * Pastikan file .env ada dan berisi key DB_* yang valid.
     * - Bila .env belum ada: dibuat (dari .env.example bila tersedia, selain itu kosong).
     * - Bila ada key DB_* yang hilang atau bernilai placeholder (your_database,
     *   your_username, your_password): prompt nilai lalu tulis ke .env.
     *   Key lain di .env dipertahankan.
     */
    protected function ensureEnvFile(InputInterface $input, OutputInterface $output): bool
    {
        $envFile = base_path() . '/.env';

        if (!is_file($envFile)) {
            $example = base_path() . '/.env.example';
            if (is_file($example)) {
                copy($example, $envFile);
            } else {
                file_put_contents($envFile, '');
            }
            $output->writeln('<info>[sayagi]</info> Created: .env');
        }

        $content  = (string) file_get_contents($envFile);
        $existing = self::parseEnvValues($content);

        $missing = [];
        foreach (self::DB_ENV_KEYS as $key) {
            $raw = $existing[$key] ?? '';
            if ($raw === '' || in_array($raw, self::DB_ENV_PLACEHOLDERS, true)) {
                $missing[] = $key;
            }
        }

        if (!$missing) {
            $output->writeln('<info>[sayagi]</info> .env database keys are complete.');
            return true;
        }

        $output->writeln('<comment>[sayagi]</comment> Database keys belum lengkap di .env: ' . implode(', ', $missing));
        $output->writeln('<comment>[sayagi]</comment> Setup konfigurasi database di .env:');
        $io = new SymfonyStyle($input, $output);

        $values = [];
        foreach ($missing as $key) {
            $default = $existing[$key] ?? null;
            if ($key === 'DB_PORT' && ($default === null || $default === '')) {
                $default = '3306';
            }
            $values[$key] = trim((string) $io->ask(self::promptLabel($key), $default));
        }

        self::writeEnvValues($envFile, $values);
        $output->writeln('<info>[sayagi]</info> .env updated with database credentials.');

        return true;
    }

    /**
     * Parse key=value dari isi .env menjadi array map.
     *
     * @return array<string,string>
     */
    protected static function parseEnvValues(string $content): array
    {
        $values = [];
        foreach (explode("\n", $content) as $line) {
            if (preg_match('/^\s*([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.*?)\s*$/', $line, $m)) {
                $values[$m[1]] = trim($m[2]);
            }
        }

        return $values;
    }

    /**
     * Label prompt untuk tiap key database.
     */
    protected static function promptLabel(string $key): string
    {
        return match ($key) {
            'DB_HOST'     => 'DB_HOST (host database, mis. 127.0.0.1)',
            'DB_PORT'     => 'DB_PORT (port database, mis. 3306)',
            'DB_DATABASE' => 'DB_DATABASE (nama database)',
            'DB_USERNAME' => 'DB_USERNAME (username database)',
            'DB_PASSWORD' => 'DB_PASSWORD (password database)',
            default       => $key,
        };
    }

    /**
     * Quote nilai .env bila mengandung spasi / karakter khusus (#, ", ', \, dll).
     */
    protected static function envValue(string $value): string
    {
        return preg_match('/[\s#"\'\\\\]/', $value)
            ? '"' . addcslashes($value, '"\\') . '"'
            : $value;
    }

    /**
     * Tulis nilai key ke .env: key yang sudah ada di-update, yang belum ada
     * ditambahkan; isi lain file dipertahankan. Nilai juga di-putenv agar
     * langsung berlaku di proses berjalan (getenv).
     *
     * @param string               $envFile Absolute path file .env.
     * @param array<string,string> $values  Map key => value.
     */
    protected static function writeEnvValues(string $envFile, array $values): void
    {
        $content = is_file($envFile) ? (string) file_get_contents($envFile) : '';

        foreach ($values as $key => $value) {
            $quoted  = self::envValue($value);
            $pattern = '/^(\s*' . preg_quote($key, '/') . '\s*=\s*).*$/m';

            if (preg_match($pattern, $content)) {
                $content = preg_replace_callback($pattern, static fn (array $m): string => $m[1] . $quoted, $content);
            } else {
                $content .= ($content !== '' && substr($content, -1) !== "\n" ? "\n" : '')
                    . $key . '=' . $quoted . "\n";
            }

            putenv($key . '=' . $value);
            $_ENV[$key]  = $value;
            $_SERVER[$key] = $value;
        }

        file_put_contents($envFile, $content);
    }

    /**
     * Pastikan paket webman/database terpasang dan config/database.php tersedia.
     * - Belum terpasang: coba composer require otomatis; bila gagal, tampilkan
     *   instruksi manual lalu hentikan (return false).
     * - Setelah terpasang: config/database.php diganti dengan
     *   vendor/webman/database/src/config/database.php (default package).
     * - Bila config/database.php sudah ada: cukup tampilkan instruksi 5 baris.
     */
    protected function ensureDatabaseConfig(OutputInterface $output): bool
    {
        $projectRoot = base_path();
        $vendorDir   = $projectRoot . '/vendor/webman/database';
        $configFile  = $projectRoot . '/config/database.php';
        $packageCfg  = $vendorDir . '/src/config/database.php';

        $installed = is_dir($vendorDir);

        if (!$installed) {
            $output->writeln('<info>[sayagi]</info> webman/database not found. Installing via composer...');

            exec('composer require webman/database --no-interaction 2>&1', $lines, $returnVar);
            foreach ($lines as $line) {
                $output->writeln($line);
            }

            if ($returnVar !== 0) {
                $output->writeln('<error>[sayagi]</error> Gagal menginstal webman/database secara otomatis.');
                $output->writeln('<comment>[sayagi]</comment> Jalankan manual di root project:');
                $output->writeln('    composer require webman/database');
                $output->writeln('<comment>[sayagi]</comment> Lalu jalankan ulang: php webman sayagi:install-admin');
                return false;
            }

            if (!is_file($packageCfg)) {
                $output->writeln('<error>[sayagi]</error> vendor/webman/database/src/config/database.php not found after install.');
                return false;
            }

            $output->writeln('<info>[sayagi]</info> webman/database installed.');

            // Baru terpasang: ganti config/database.php dengan default package.
            $output->writeln('<info>[sayagi]</info> Replacing config/database.php with webman/database default...');
            copy($packageCfg, $configFile);
            $output->writeln('<info>[sayagi]</info> Replaced: config/database.php');
        } elseif (!is_file($configFile)) {
            // Terpasang tapi config belum ada: salin dari package.
            $output->writeln('<info>[sayagi]</info> Creating config/database.php from webman/database...');
            copy($packageCfg, $configFile);
            $output->writeln('<info>[sayagi]</info> Published: config/database.php');
        } else {
            $output->writeln('<comment>[sayagi]</comment> config/database.php already exists.');
        }

        $this->showDatabaseConfigInstructions($output);

        return true;
    }

    /**
     * Tampilkan 5 baris konfigurasi database (getenv) untuk disalin ke
     * config/database.php sekitar baris 7-11 agar koneksi memakai nilai .env.
     */
    protected function showDatabaseConfigInstructions(OutputInterface $output): void
    {
        $output->writeln('');
        $output->writeln('<comment>[sayagi]</comment> Salin 5 baris konfigurasi database berikut ke config/database.php (sekitar baris 7-11):');
        $output->writeln('');
        $output->writeln("    'host'        => getenv('DB_HOST', '127.0.0.1'),");
        $output->writeln("    'port'        => getenv('DB_PORT', '3306'),");
        $output->writeln("    'database'    => getenv('DB_DATABASE', 'your_database'),");
        $output->writeln("    'username'    => getenv('DB_USERNAME', 'your_username'),");
        $output->writeln("    'password'    => getenv('DB_PASSWORD', 'your_password'),");
        $output->writeln('');
    }

    /**
     * Validasi konfigurasi database dari env sebelum migrasi/seeder dijalankan.
     * Memastikan kredensial terisi (bukan placeholder) dan koneksi berhasil.
     * Menggunakan env DB_* yang sama dengan config migration (Yllumi\Sayagi\libraries\Migration).
     */
    protected function checkDatabase(OutputInterface $output): bool
    {
        $host    = getenv('DB_HOST') ?: '127.0.0.1';
        $port    = getenv('DB_PORT') ?: '3306';
        $db      = getenv('DB_DATABASE') ?: '';
        $user    = getenv('DB_USERNAME') ?: '';
        $pass    = getenv('DB_PASSWORD') ?: '';
        $adapter = getenv('DB_ADAPTER') ?: 'mysql';

        $output->writeln('<info>[sayagi]</info> Checking database configuration...');

        // Kredensial wajib: database & username harus terisi dan bukan placeholder.
        $placeholders = ['your_database', 'your_username', 'your_password'];
        foreach (['database' => $db, 'username' => $user] as $label => $value) {
            if ($value === '' || in_array($value, $placeholders, true)) {
                $output->writeln('<error>[sayagi]</error> DB_' . strtoupper($label) . ' belum dikonfigurasi dengan benar di file .env.');
                return false;
            }
        }

        // Password boleh kosong, tapi bukan placeholder default.
        if (in_array($pass, $placeholders, true)) {
            $output->writeln('<error>[sayagi]</error> DB_PASSWORD masih placeholder default di file .env.');
            return false;
        }

        // Uji koneksi ke database (kredensial sama dengan yang dipakai migrasi).
        $dsn = $adapter === 'pgsql'
            ? "pgsql:host={$host};port={$port};dbname={$db}"
            : "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";

        try {
            new \PDO($dsn, $user, $pass, [
                \PDO::ATTR_TIMEOUT => 3,
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);
            $output->writeln('<info>[sayagi]</info> Database connection OK (' . $adapter . '://' . $host . ':' . $port . '/' . $db . ').');
        } catch (\PDOException $e) {
            $output->writeln('<error>[sayagi]</error> Database connection failed: ' . $e->getMessage());
            $output->writeln('<comment>[sayagi]</comment> Periksa DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD di file .env.');
            return false;
        }

        return true;
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

    /**
     * Tanya data user admin pertama lalu simpan ke mein_users.
     * Status langsung 'active', role_id = 1 (Super). Idempotent: dilewati
     * bila sudah ada user dengan role_id = 1.
     */
    protected function createFirstUser(InputInterface $input, OutputInterface $output): bool
    {
        if (Db::table('mein_users')->where('role_id', 1)->exists()) {
            $output->writeln('<comment>[sayagi]</comment> Admin user already exists, skipping first user creation.');
            return true;
        }

        $io = new SymfonyStyle($input, $output);

        $name     = trim((string) $io->ask('Nama lengkap'));
        $username = strtolower(trim((string) $io->ask('Username')));
        $email    = strtolower(trim((string) $io->ask('Email')));
        $password = (string) $io->askHidden('Password (minimal 8 karakter)');

        if ($name === '' || $username === '' || $email === '' || $password === '') {
            $output->writeln('<error>[sayagi]</error> Nama, username, email, dan password wajib diisi.');
            return false;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $output->writeln('<error>[sayagi]</error> Format email tidak valid.');
            return false;
        }

        if (strlen($password) < 8) {
            $output->writeln('<error>[sayagi]</error> Password minimal 8 karakter.');
            return false;
        }

        if (Db::table('mein_users')->where('email', $email)->exists()) {
            $output->writeln('<error>[sayagi]</error> Email sudah digunakan.');
            return false;
        }

        if (Db::table('mein_users')->where('username', $username)->exists()) {
            $output->writeln('<error>[sayagi]</error> Username sudah digunakan.');
            return false;
        }

        $phpass = new \Yllumi\Sayagi\libraries\Phpass();
        $now    = date('Y-m-d H:i:s');

        Db::table('mein_users')->insert([
            'name'       => $name,
            'username'   => $username,
            'email'      => $email,
            'password'   => $phpass->HashPassword($password),
            'status'     => 'active',
            'role_id'    => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $output->writeln('<info>[sayagi]</info> User admin pertama berhasil dibuat: ' . $username . ' (' . $email . ')');
        return true;
    }
}
