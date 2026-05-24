<?php
// database/migrations/xxxx_create_audit_logs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // audit_logs est partitionnée par RANGE sur created_at
        // Laravel Schema Builder ne supporte pas le partitionnement natif
        // On utilise donc du SQL brut
        DB::statement("
            CREATE TABLE IF NOT EXISTS `audit_logs` (
                `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `user_id`       BIGINT UNSIGNED NULL,
                `user_role`     VARCHAR(20)  NULL,
                `action`        VARCHAR(100) NOT NULL,
                `entity_type`   VARCHAR(100) NULL,
                `entity_id`     BIGINT UNSIGNED NULL,
                `old_values`    JSON NULL,
                `new_values`    JSON NULL,
                `ip_address`    VARCHAR(45) NULL,
                `user_agent`    TEXT NULL,
                `status`        ENUM('success','failure') DEFAULT 'success',
                `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`, `created_at`),
                KEY `idx_audit_user`    (`user_id`, `created_at`),
                KEY `idx_audit_action`  (`action`, `created_at`),
                KEY `idx_audit_entity`  (`entity_type`, `entity_id`)
            ) ENGINE=InnoDB
              DEFAULT CHARSET=utf8mb4
              COLLATE=utf8mb4_unicode_ci
            PARTITION BY RANGE COLUMNS(`created_at`) (
                PARTITION p2024_01 VALUES LESS THAN ('2024-02-01 00:00:00'),
                PARTITION p2024_02 VALUES LESS THAN ('2024-03-01 00:00:00'),
                PARTITION p2024_03 VALUES LESS THAN ('2024-04-01 00:00:00'),
                PARTITION p2024_04 VALUES LESS THAN ('2024-05-01 00:00:00'),
                PARTITION p2024_05 VALUES LESS THAN ('2024-06-01 00:00:00'),
                PARTITION p2024_06 VALUES LESS THAN ('2024-07-01 00:00:00'),
                PARTITION p2024_07 VALUES LESS THAN ('2024-08-01 00:00:00'),
                PARTITION p2024_08 VALUES LESS THAN ('2024-09-01 00:00:00'),
                PARTITION p2024_09 VALUES LESS THAN ('2024-10-01 00:00:00'),
                PARTITION p2024_10 VALUES LESS THAN ('2024-11-01 00:00:00'),
                PARTITION p2024_11 VALUES LESS THAN ('2024-12-01 00:00:00'),
                PARTITION p2024_12 VALUES LESS THAN ('2025-01-01 00:00:00'),
                PARTITION p2025_01 VALUES LESS THAN ('2025-02-01 00:00:00'),
                PARTITION p2025_02 VALUES LESS THAN ('2025-03-01 00:00:00'),
                PARTITION p2025_03 VALUES LESS THAN ('2025-04-01 00:00:00'),
                PARTITION p2025_04 VALUES LESS THAN ('2025-05-01 00:00:00'),
                PARTITION p2025_05 VALUES LESS THAN ('2025-06-01 00:00:00'),
                PARTITION p2025_06 VALUES LESS THAN ('2025-07-01 00:00:00'),
                PARTITION p2025_07 VALUES LESS THAN ('2025-08-01 00:00:00'),
                PARTITION p2025_08 VALUES LESS THAN ('2025-09-01 00:00:00'),
                PARTITION p2025_09 VALUES LESS THAN ('2025-10-01 00:00:00'),
                PARTITION p2025_10 VALUES LESS THAN ('2025-11-01 00:00:00'),
                PARTITION p2025_11 VALUES LESS THAN ('2025-12-01 00:00:00'),
                PARTITION p2025_12 VALUES LESS THAN ('2026-01-01 00:00:00'),
                PARTITION p2026_01 VALUES LESS THAN ('2026-02-01 00:00:00'),
                PARTITION p2026_02 VALUES LESS THAN ('2026-03-01 00:00:00'),
                PARTITION p2026_03 VALUES LESS THAN ('2026-04-01 00:00:00'),
                PARTITION p2026_04 VALUES LESS THAN ('2026-05-01 00:00:00'),
                PARTITION p2026_05 VALUES LESS THAN ('2026-06-01 00:00:00'),
                PARTITION p2026_06 VALUES LESS THAN ('2026-07-01 00:00:00'),
                PARTITION p2026_07 VALUES LESS THAN ('2026-08-01 00:00:00'),
                PARTITION p2026_08 VALUES LESS THAN ('2026-09-01 00:00:00'),
                PARTITION p2026_09 VALUES LESS THAN ('2026-10-01 00:00:00'),
                PARTITION p2026_10 VALUES LESS THAN ('2026-11-01 00:00:00'),
                PARTITION p2026_11 VALUES LESS THAN ('2026-12-01 00:00:00'),
                PARTITION p2026_12 VALUES LESS THAN ('2027-01-01 00:00:00'),
                PARTITION p_future  VALUES LESS THAN (MAXVALUE)
            )
        ");
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS `audit_logs`');
    }
};
