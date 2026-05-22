<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use Phinx\Util\Literal;

final class InstallPlugin extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function up(): void
    {
        if (!$this->hasTable('mein_options')) {
            $this->table('mein_options', [
                'id' => false,
                'primary_key' => ['id'],
                'engine' => 'InnoDB',
                'charset' => 'utf8mb3',
            ])
                ->addColumn('id', 'integer', ['identity' => true, 'null' => false])
                ->addColumn('option_group', 'string', ['limit' => 30, 'default' => 'site', 'null' => true])
                ->addColumn('option_name', 'string', ['limit' => 30, 'null' => true])
                ->addColumn('option_value', 'text', ['null' => true])
                ->create();
        }

        if (!$this->hasTable('mein_role_privileges')) {
            $this->table('mein_role_privileges', [
                'id' => false,
                'primary_key' => ['id'],
                'engine' => 'InnoDB',
                'charset' => 'utf8mb3',
            ])
                ->addColumn('id', 'integer', ['identity' => true, 'null' => false])
                ->addColumn('role_id', 'integer', ['null' => true])
                ->addColumn('feature', 'string', ['limit' => 50, 'null' => true])
                ->addColumn('privilege', 'string', ['limit' => 100, 'null' => true])
                ->addColumn('created_at', 'timestamp', ['null' => true, 'default' => Literal::from('CURRENT_TIMESTAMP')])
                ->create();
        }

        if (!$this->hasTable('mein_roles')) {
            $this->table('mein_roles', [
                'id' => false,
                'primary_key' => ['id'],
                'engine' => 'InnoDB',
                'charset' => 'utf8mb3',
            ])
                ->addColumn('id', 'integer', ['identity' => true, 'null' => false])
                ->addColumn('role_name', 'string', ['limit' => 200, 'null' => true])
                ->addColumn('role_slug', 'string', ['limit' => 50, 'null' => true])
                ->addColumn('status', 'string', ['limit' => 20, 'default' => 'active', 'null' => true])
                ->addColumn('created_at', 'timestamp', ['null' => true, 'default' => Literal::from('CURRENT_TIMESTAMP')])
                ->addColumn('updated_at', 'datetime', ['null' => true])
                ->create();
        }

        if (!$this->hasTable('mein_user_profile')) {
            $this->table('mein_user_profile', [
                'id' => false,
                'primary_key' => ['id'],
                'engine' => 'InnoDB',
                'charset' => 'utf8mb3',
            ])
                ->addColumn('id', 'integer', ['identity' => true, 'null' => false])
                ->addColumn('user_id', 'integer', ['null' => false])
                ->addColumn('phone', 'string', ['limit' => 200, 'null' => true])
                ->addColumn('address', 'text', ['null' => true])
                ->addColumn('birthday', 'date', ['null' => true])
                ->addColumn('interest', 'text', ['null' => true])
                ->addColumn('experience', 'integer', ['limit' => 2, 'null' => true])
                ->addColumn('jobs', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('profession', 'string', ['limit' => 20, 'null' => true])
                ->addColumn('city', 'string', ['limit' => 100, 'null' => true])
                ->addColumn('portfolio_link', 'text', ['null' => true])
                ->addColumn('description', 'text', ['null' => true])
                ->addColumn('newsletter', 'boolean', ['default' => true, 'null' => true])
                ->addColumn('ready_to_work', 'boolean', ['default' => false, 'null' => true])
                ->addColumn('latest_ip', 'string', ['limit' => 20, 'null' => true])
                ->addColumn('created_at', 'timestamp', ['null' => true, 'default' => Literal::from('CURRENT_TIMESTAMP')])
                ->addColumn('updated_at', 'datetime', ['null' => true])
                ->addColumn('gender', 'string', ['limit' => 255, 'default' => 'l', 'null' => true])
                ->addColumn('status_marital', 'string', ['limit' => 255, 'default' => 'single', 'null' => true])
                ->addColumn('record_log', 'string', ['limit' => 255, 'default' => '0', 'null' => true])
                ->addColumn('akun_ig', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('akun_tiktok', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('hobi', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('nomor_rekening', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('bank', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('pemilik_rekening', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('deleted_at', 'timestamp', ['null' => true, 'default' => null])
                ->addIndex(['user_id'], ['name' => 'user_id'])
                ->create();
        }

        if (!$this->hasTable('mein_users')) {
            $this->table('mein_users', [
                'id' => false,
                'primary_key' => ['id'],
                'engine' => 'InnoDB',
                'charset' => 'utf8mb3',
            ])
                ->addColumn('id', 'integer', ['identity' => true, 'null' => false])
                ->addColumn('session_id', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('source_id', 'string', ['limit' => 64, 'null' => true])
                ->addColumn('name', 'string', ['limit' => 100, 'null' => false])
                ->addColumn('email', 'string', ['limit' => 200, 'null' => true])
                ->addColumn('phone', 'string', ['limit' => 15, 'null' => true])
                ->addColumn('username', 'string', ['limit' => 200, 'null' => true])
                ->addColumn('short_description', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('avatar', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('url', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('referrer_code', 'string', ['limit' => 50, 'null' => true])
                ->addColumn('password', 'text', ['null' => true])
                ->addColumn('status', 'string', ['limit' => 20, 'default' => 'inactive', 'null' => true])
                ->addColumn('role_id', 'integer', ['default' => 3, 'null' => true])
                ->addColumn('token', 'string', ['limit' => 150, 'null' => true])
                ->addColumn('otp', 'string', ['limit' => 6, 'null' => true])
                ->addColumn('cdn_token', 'text', ['null' => true])
                ->addColumn('mail_unsubscribe', 'boolean', ['null' => true])
                ->addColumn('mail_invalid', 'boolean', ['null' => true])
                ->addColumn('mail_bounce', 'boolean', ['null' => true])
                ->addColumn('last_login', 'datetime', ['null' => true])
                ->addColumn('created_at', 'timestamp', ['null' => true, 'default' => Literal::from('CURRENT_TIMESTAMP')])
                ->addColumn('updated_at', 'datetime', ['null' => true])
                ->addIndex(['source_id'], ['unique' => true, 'name' => 'source_id'])
                ->create();
        }
    }

    /**
     * Migrate Down.
     */
    public function down(): void
    {
        $this->table('mein_options')->drop()->save();
        $this->table('mein_role_privileges')->drop()->save();
        $this->table('mein_roles')->drop()->save();
        $this->table('mein_user_profile')->drop()->save();
        $this->table('mein_users')->drop()->save();
    }
}
