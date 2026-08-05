-- =====================================================================
-- ซ่อม schema drift ที่ติดมากับ DB dump ที่ import เมื่อ 2026-08-01
-- คู่กับ migration: 2026_08_05_000001_repair_schema_drift_from_imported_dump.php
--
-- ใช้ไฟล์นี้เฉพาะกรณีที่รัน `php artisan migrate` ไม่ได้ (เช่น shared hosting
-- ที่เข้าได้แต่ phpMyAdmin) ถ้ารัน artisan ได้ ให้ใช้ migration แทน
-- เพราะ migration จะบันทึกลงตาราง `migrations` ให้ด้วย
--
-- MySQL 8.4 ไม่รองรับ `ADD COLUMN IF NOT EXISTS` จึงมีให้ 2 แบบ:
--   ส่วนที่ 1 = ALTER ตรงๆ  → ใช้เมื่อรู้แน่ว่าคอลัมน์ยังไม่มี (ตรวจแล้วบน nuxnan_nuxnan_db)
--   ส่วนที่ 2 = แบบมี guard → ใช้เมื่อไม่แน่ใจสถานะ DB (เช่น production) รันซ้ำได้
-- เลือกรันส่วนใดส่วนหนึ่ง ไม่ต้องรันทั้งสอง
-- =====================================================================


-- =====================================================================
-- ส่วนที่ 1 — ALTER ตรงๆ (รันซ้ำจะได้ error 1060 Duplicate column name)
-- =====================================================================

START TRANSACTION;

-- 2026_07_23_180000_add_donor_view_to_course_claims
ALTER TABLE `course_donates`
    ADD COLUMN `remaining_points` BIGINT UNSIGNED NULL DEFAULT 0 AFTER `points_amount`;

UPDATE `course_donates`
   SET `remaining_points` = `points_amount`
 WHERE `donation_type` = 'point'
   AND `status` IN ('approved', 'completed');

-- 2026_01_13_150811_add_points_columns_to_polls_table
ALTER TABLE `polls`
    ADD COLUMN `points_pool`        INT NOT NULL DEFAULT 0 AFTER `image_url`,
    ADD COLUMN `points_per_vote`    INT NOT NULL DEFAULT 0 AFTER `points_pool`,
    ADD COLUMN `points_distributed` INT NOT NULL DEFAULT 0 AFTER `points_per_vote`;

-- 2025_11_14_100000_add_logo_and_headers_to_courses_table
ALTER TABLE `courses`
    ADD COLUMN `logo`            VARCHAR(255) NULL AFTER `cover`,
    ADD COLUMN `cover_header`    VARCHAR(255) NULL AFTER `logo`,
    ADD COLUMN `cover_subheader` TEXT         NULL AFTER `cover_header`;

-- 2026_02_01_010000_add_columns_to_roles_table
ALTER TABLE `roles`
    ADD COLUMN `display_name` VARCHAR(255) NULL           AFTER `name`,
    ADD COLUMN `status`       TINYINT(1) NOT NULL DEFAULT 1 AFTER `description`;

UPDATE `roles` SET `display_name` = `name` WHERE `display_name` IS NULL;

-- 2026_02_05_011941_create_gamification_tables
-- `points` คือคอลัมน์ที่มีจริง ส่วน `xp_reward` เป็นชื่อที่ Badge::$fillable ใช้
ALTER TABLE `badges`
    ADD COLUMN `xp_reward` INT NOT NULL DEFAULT 0 AFTER `icon`;

UPDATE `badges` SET `xp_reward` = `points` WHERE `points` > 0;

-- 2025_11_27_203000_create_user_profiles_table (ชื่อเดิมฝั่ง nuxni)
ALTER TABLE `user_profiles`
    ADD COLUMN `cover_image_url` VARCHAR(255) NULL AFTER `cover_image`;

COMMIT;


-- =====================================================================
-- ส่วนที่ 2 — แบบมี guard (รันซ้ำได้ ไม่ error ถ้าคอลัมน์มีอยู่แล้ว)
-- ใช้ information_schema + PREPARE จึงไม่ต้องเปลี่ยน DELIMITER
-- =====================================================================

-- course_donates.remaining_points
SET @sql := (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `course_donates` ADD COLUMN `remaining_points` BIGINT UNSIGNED NULL DEFAULT 0 AFTER `points_amount`',
    'DO 0') FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'course_donates' AND COLUMN_NAME = 'remaining_points');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

UPDATE `course_donates` SET `remaining_points` = `points_amount`
 WHERE `donation_type` = 'point' AND `status` IN ('approved', 'completed')
   AND (`remaining_points` IS NULL OR `remaining_points` = 0);

-- polls.points_pool
SET @sql := (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `polls` ADD COLUMN `points_pool` INT NOT NULL DEFAULT 0 AFTER `image_url`',
    'DO 0') FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'polls' AND COLUMN_NAME = 'points_pool');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

-- polls.points_per_vote
SET @sql := (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `polls` ADD COLUMN `points_per_vote` INT NOT NULL DEFAULT 0 AFTER `points_pool`',
    'DO 0') FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'polls' AND COLUMN_NAME = 'points_per_vote');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

-- polls.points_distributed
SET @sql := (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `polls` ADD COLUMN `points_distributed` INT NOT NULL DEFAULT 0 AFTER `points_per_vote`',
    'DO 0') FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'polls' AND COLUMN_NAME = 'points_distributed');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

-- courses.logo
SET @sql := (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `courses` ADD COLUMN `logo` VARCHAR(255) NULL AFTER `cover`',
    'DO 0') FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'courses' AND COLUMN_NAME = 'logo');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

-- courses.cover_header
SET @sql := (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `courses` ADD COLUMN `cover_header` VARCHAR(255) NULL AFTER `logo`',
    'DO 0') FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'courses' AND COLUMN_NAME = 'cover_header');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

-- courses.cover_subheader
SET @sql := (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `courses` ADD COLUMN `cover_subheader` TEXT NULL AFTER `cover_header`',
    'DO 0') FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'courses' AND COLUMN_NAME = 'cover_subheader');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

-- roles.display_name
SET @sql := (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `roles` ADD COLUMN `display_name` VARCHAR(255) NULL AFTER `name`',
    'DO 0') FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'roles' AND COLUMN_NAME = 'display_name');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

UPDATE `roles` SET `display_name` = `name` WHERE `display_name` IS NULL;

-- roles.status
SET @sql := (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `roles` ADD COLUMN `status` TINYINT(1) NOT NULL DEFAULT 1 AFTER `description`',
    'DO 0') FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'roles' AND COLUMN_NAME = 'status');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

-- badges.xp_reward
SET @sql := (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `badges` ADD COLUMN `xp_reward` INT NOT NULL DEFAULT 0 AFTER `icon`',
    'DO 0') FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'badges' AND COLUMN_NAME = 'xp_reward');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

UPDATE `badges` SET `xp_reward` = `points` WHERE `points` > 0 AND `xp_reward` = 0;

-- user_profiles.cover_image_url
SET @sql := (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `user_profiles` ADD COLUMN `cover_image_url` VARCHAR(255) NULL AFTER `cover_image`',
    'DO 0') FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_profiles' AND COLUMN_NAME = 'cover_image_url');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;


-- =====================================================================
-- ตรวจผลหลังรัน — ต้องได้ 11 แถวครบ
-- =====================================================================
SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
  FROM information_schema.COLUMNS
 WHERE TABLE_SCHEMA = DATABASE()
   AND (   (TABLE_NAME = 'course_donates' AND COLUMN_NAME = 'remaining_points')
        OR (TABLE_NAME = 'polls'          AND COLUMN_NAME IN ('points_pool', 'points_per_vote', 'points_distributed'))
        OR (TABLE_NAME = 'courses'        AND COLUMN_NAME IN ('logo', 'cover_header', 'cover_subheader'))
        OR (TABLE_NAME = 'roles'          AND COLUMN_NAME IN ('display_name', 'status'))
        OR (TABLE_NAME = 'badges'         AND COLUMN_NAME = 'xp_reward')
        OR (TABLE_NAME = 'user_profiles'  AND COLUMN_NAME = 'cover_image_url'))
 ORDER BY TABLE_NAME, COLUMN_NAME;
