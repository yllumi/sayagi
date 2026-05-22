CREATE TABLE
    `mein_options` (
        `id` int NOT NULL AUTO_INCREMENT,
        `option_group` varchar(30) DEFAULT 'site',
        `option_name` varchar(30) DEFAULT NULL,
        `option_value` text,
        PRIMARY KEY (`id`)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

CREATE TABLE
    `mein_role_privileges` (
        `id` int NOT NULL AUTO_INCREMENT,
        `role_id` int DEFAULT NULL,
        `feature` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
        `privilege` varchar(100) DEFAULT NULL,
        `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

CREATE TABLE
    `mein_roles` (
        `id` int NOT NULL AUTO_INCREMENT,
        `role_name` varchar(200) DEFAULT NULL,
        `role_slug` varchar(50) DEFAULT NULL,
        `status` varchar(20) DEFAULT 'active',
        `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` datetime DEFAULT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

CREATE TABLE
    `mein_user_profile` (
        `id` int NOT NULL AUTO_INCREMENT,
        `user_id` int NOT NULL,
        `phone` varchar(200) DEFAULT NULL,
        `address` text,
        `birthday` date DEFAULT NULL,
        `interest` text,
        `experience` tinyint DEFAULT NULL,
        `jobs` varchar(255) DEFAULT NULL,
        `profession` varchar(20) DEFAULT NULL,
        `city` varchar(100) DEFAULT NULL,
        `portfolio_link` text,
        `description` text,
        `newsletter` tinyint (1) DEFAULT '1',
        `ready_to_work` tinyint (1) DEFAULT '0',
        `latest_ip` varchar(20) DEFAULT NULL,
        `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` datetime DEFAULT NULL,
        `gender` varchar(255) DEFAULT 'l',
        `status_marital` varchar(255) DEFAULT 'single',
        `record_log` varchar(255) DEFAULT '0',
        `akun_ig` varchar(255) DEFAULT NULL,
        `akun_tiktok` varchar(255) DEFAULT NULL,
        `hobi` varchar(255) DEFAULT NULL,
        `nomor_rekening` varchar(255) DEFAULT NULL,
        `bank` varchar(255) DEFAULT NULL,
        `pemilik_rekening` varchar(255) DEFAULT NULL,
        `deleted_at` timestamp NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

CREATE TABLE
    `mein_users` (
        `id` int NOT NULL AUTO_INCREMENT,
        `session_id` varchar(255) DEFAULT NULL,
        `source_id` varchar(64) DEFAULT NULL,
        `name` varchar(100) NOT NULL,
        `email` varchar(200) DEFAULT NULL,
        `phone` varchar(15) DEFAULT NULL,
        `username` varchar(200) DEFAULT NULL,
        `short_description` varchar(255) DEFAULT NULL,
        `avatar` varchar(255) DEFAULT NULL,
        `url` varchar(255) DEFAULT NULL,
        `referrer_code` varchar(50) DEFAULT NULL,
        `password` tinytext,
        `status` varchar(20) DEFAULT 'inactive',
        `role_id` int DEFAULT '3',
        `token` varchar(150) DEFAULT NULL,
        `otp` varchar(6) DEFAULT NULL,
        `cdn_token` text,
        `mail_unsubscribe` tinyint (1) DEFAULT NULL,
        `mail_invalid` tinyint (1) DEFAULT NULL,
        `mail_bounce` tinyint (1) DEFAULT NULL,
        `last_login` datetime DEFAULT NULL,
        `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` datetime DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `source_id` (`source_id`)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;