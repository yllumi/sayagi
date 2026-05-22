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

        $usersSql = <<<'SQL'
INSERT INTO `mein_users` (`id`, `session_id`, `source_id`, `name`, `email`, `phone`, `username`, `short_description`, `avatar`, `url`, `referrer_code`, `password`, `status`, `role_id`, `token`, `otp`, `cdn_token`, `mail_unsubscribe`, `mail_invalid`, `mail_bounce`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJsb2dnZWRfaW4iOnRydWUsInVzZXJfaWQiOiIxIiwiZnVsbF9uYW1lIjoiTWltaW4iLCJlbWFpbCI6ImFkbWluQGFkbWluLmNvbSIsInRpbWVzdGFtcCI6MTczNzAzODYxNn0.2QBIj2mFYOKkDMlOOj96fkW-Q3c4XOy2gTG55Nadv4E', NULL, 'Mimin', 'admin@admin.com', '08987654321', 'admin', NULL, NULL, NULL, NULL, '$P$BKWx8Bq/tZX78kHf5PlbIEpsEC3L9O0', 'active', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2025-01-16 21:43:36', '2025-01-16 01:57:29', NULL)
ON DUPLICATE KEY UPDATE
`session_id` = VALUES(`session_id`),
`source_id` = VALUES(`source_id`),
`name` = VALUES(`name`),
`email` = VALUES(`email`),
`phone` = VALUES(`phone`),
`username` = VALUES(`username`),
`short_description` = VALUES(`short_description`),
`avatar` = VALUES(`avatar`),
`url` = VALUES(`url`),
`referrer_code` = VALUES(`referrer_code`),
`password` = VALUES(`password`),
`status` = VALUES(`status`),
`role_id` = VALUES(`role_id`),
`token` = VALUES(`token`),
`otp` = VALUES(`otp`),
`cdn_token` = VALUES(`cdn_token`),
`mail_unsubscribe` = VALUES(`mail_unsubscribe`),
`mail_invalid` = VALUES(`mail_invalid`),
`mail_bounce` = VALUES(`mail_bounce`),
`last_login` = VALUES(`last_login`),
`created_at` = VALUES(`created_at`),
`updated_at` = VALUES(`updated_at`)
SQL;

        $this->execute($usersSql);
    }
}
