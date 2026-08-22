<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

final class SayagiInitSeeder extends AbstractSeed
{
    public function run(): void
    {
        $rolesSql = <<<'SQL'
INSERT INTO `mein_roles` (`id`, `role_name`, `role_slug`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Super', 'super', 'active', '2013-05-13 03:32:53', NULL),
(2, 'Member', 'member', 'active', '2013-05-13 03:32:53', NULL)
ON DUPLICATE KEY UPDATE
`role_name` = VALUES(`role_name`),
`role_slug` = VALUES(`role_slug`),
`status` = VALUES(`status`),
`created_at` = VALUES(`created_at`),
`updated_at` = VALUES(`updated_at`)
SQL;

        $this->execute($rolesSql);
    }
}
