<?php

namespace Yllumi\Sayagi\app\command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use support\Db;

#[AsCommand('sayagi:user:create', 'Create a new user in mein_users table.')]
class CreateUser extends Command
{
    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::OPTIONAL, 'Full name');
        $this->addArgument('username', InputArgument::OPTIONAL, 'Username');
        $this->addArgument('email', InputArgument::OPTIONAL, 'Email address');
        $this->addArgument('password', InputArgument::OPTIONAL, 'Plain password (min 8 chars)');

        $this->addOption('phone', null, InputOption::VALUE_OPTIONAL, 'Phone number');
        $this->addOption('role', null, InputOption::VALUE_OPTIONAL, 'Role ID');
        $this->addOption('status', null, InputOption::VALUE_OPTIONAL, 'User status');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $name = trim((string) $input->getArgument('name'));
        $username = strtolower(trim((string) $input->getArgument('username')));
        $email = strtolower(trim((string) $input->getArgument('email')));
        $password = (string) $input->getArgument('password');
        $phoneOption = $input->getOption('phone');
        $roleOption = $input->getOption('role');
        $statusOption = $input->getOption('status');

        $phone = trim((string) ($phoneOption ?? ''));
        $status = trim((string) ($statusOption ?? ''));
        $roleId = (int) ($roleOption ?? 0);

        if ($input->isInteractive()) {
            if ($name === '') {
                $name = trim((string) $io->ask('Nama lengkap'));
            }

            if ($username === '') {
                $username = strtolower(trim((string) $io->ask('Username')));
            }

            if ($email === '') {
                $email = strtolower(trim((string) $io->ask('Email')));
            }

            if ($password === '') {
                $password = (string) $io->askHidden('Password (minimal 8 karakter)');
            }

            if ($phoneOption === null) {
                $phone = trim((string) $io->ask('Phone (opsional)', ''));
            }

            if ($roleOption === null) {
                $roleId = (int) $io->ask('Role ID', '2');
            }

            if ($statusOption === null) {
                $status = (string) $io->choice('Status user', ['active', 'inactive', 'deleted'], 'active');
            }
        }

        $status = $status !== '' ? $status : 'active';

        if ($name === '' || $username === '' || $email === '' || $password === '') {
            $output->writeln('<error>[sayagi]</error> name, username, email, dan password wajib diisi.');
            return Command::FAILURE;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $output->writeln('<error>[sayagi]</error> Format email tidak valid.');
            return Command::FAILURE;
        }

        if (strlen($password) < 8) {
            $output->writeln('<error>[sayagi]</error> Password minimal 8 karakter.');
            return Command::FAILURE;
        }

        if (Db::table('mein_users')->where('email', $email)->exists()) {
            $output->writeln('<error>[sayagi]</error> Email sudah digunakan.');
            return Command::FAILURE;
        }

        if (Db::table('mein_users')->where('username', $username)->exists()) {
            $output->writeln('<error>[sayagi]</error> Username sudah digunakan.');
            return Command::FAILURE;
        }

        if ($roleId > 0 && !Db::table('mein_roles')->where('id', $roleId)->exists()) {
            $output->writeln('<error>[sayagi]</error> Role ID tidak ditemukan: ' . $roleId);
            return Command::FAILURE;
        }

        $phpass = new \Yllumi\Sayagi\libraries\Phpass();
        $now = date('Y-m-d H:i:s');

        $payload = [
            'name' => $name,
            'username' => $username,
            'email' => $email,
            'phone' => $phone,
            'password' => $phpass->HashPassword($password),
            'status' => $status,
            'role_id' => $roleId > 0 ? $roleId : null,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        Db::table('mein_users')->insert($payload);

        $output->writeln('<info>[sayagi]</info> User berhasil dibuat: ' . $username . ' (' . $email . ')');
        return Command::SUCCESS;
    }
}
