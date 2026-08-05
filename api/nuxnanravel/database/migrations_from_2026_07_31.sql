-- =====================================================================
-- nuxnan — migrations ตั้งแต่ 2026_07_31 เป็นต้นไป แปลงเป็น SQL ล้วน
-- ครอบคลุม 15 migrations (2026_07_31_000001 … 2026_08_02_000004)
-- Target: MySQL 8 / InnoDB / utf8mb4_unicode_ci
--
-- เงื่อนไขก่อนรัน: DB ปลายทางต้องมีตาราง users, academies, academic_years,
-- academy_members, academy_groups, students อยู่แล้ว
-- (คือรัน migrations ถึง 2026_07_29_000007 ครบแล้ว)
--
-- รัน:  mysql -u <user> -p <database> < database/migrations_from_2026_07_31.sql
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 1;

START TRANSACTION;
-- หมายเหตุ: MySQL ทำ implicit commit ทุกครั้งที่เจอ DDL — transaction นี้
-- คุมได้แค่ INSERT ท้ายไฟล์ ถ้าพังกลางทางต้อง rollback ด้วยมือ (ดูส่วนท้ายไฟล์)


-- ---------------------------------------------------------------------
-- 1) 2026_07_31_000001_create_elections_table
-- ---------------------------------------------------------------------
CREATE TABLE `elections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_id` bigint unsigned NOT NULL,
  `academic_year_id` bigint unsigned DEFAULT NULL,
  `title` varchar(150) NOT NULL,
  `description` text,
  `status` enum('draft','nomination','campaign','voting','closed','published','cancelled') NOT NULL DEFAULT 'draft',
  `nomination_opens_at` datetime DEFAULT NULL,
  `nomination_closes_at` datetime DEFAULT NULL,
  `voting_opens_at` datetime DEFAULT NULL,
  `voting_closes_at` datetime DEFAULT NULL,
  `allow_abstain` tinyint(1) NOT NULL DEFAULT '1',
  `ballot_ttl_seconds` int unsigned NOT NULL DEFAULT '180',
  `voter_roll_locked_at` datetime DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `created_by` bigint unsigned NOT NULL,
  `settings` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `elections_academic_year_id_foreign` (`academic_year_id`),
  KEY `elections_created_by_foreign` (`created_by`),
  KEY `elections_academy_id_status_index` (`academy_id`,`status`),
  CONSTRAINT `elections_academy_id_foreign` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `elections_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE SET NULL,
  CONSTRAINT `elections_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- 2) 2026_07_31_000002_create_election_stations_table
-- ---------------------------------------------------------------------
CREATE TABLE `election_stations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `election_id` bigint unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `location` varchar(150) DEFAULT NULL,
  `is_open` tinyint(1) NOT NULL DEFAULT '0',
  `opened_by` bigint unsigned DEFAULT NULL,
  `opened_at` datetime DEFAULT NULL,
  `closed_by` bigint unsigned DEFAULT NULL,
  `closed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `election_stations_election_id_name_unique` (`election_id`,`name`),
  KEY `election_stations_opened_by_foreign` (`opened_by`),
  KEY `election_stations_closed_by_foreign` (`closed_by`),
  CONSTRAINT `election_stations_election_id_foreign` FOREIGN KEY (`election_id`) REFERENCES `elections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `election_stations_opened_by_foreign` FOREIGN KEY (`opened_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `election_stations_closed_by_foreign` FOREIGN KEY (`closed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- 3) 2026_07_31_000003_create_election_parties_table
-- ---------------------------------------------------------------------
CREATE TABLE `election_parties` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `election_id` bigint unsigned NOT NULL,
  `number` smallint unsigned DEFAULT NULL,
  `name` varchar(120) NOT NULL,
  `slogan` varchar(200) DEFAULT NULL,
  `logo_path` varchar(191) DEFAULT NULL,
  `policy` text,
  `status` enum('pending','approved','rejected','withdrawn') NOT NULL DEFAULT 'pending',
  `applied_by` bigint unsigned NOT NULL,
  `reviewed_by` bigint unsigned DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `review_note` text,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `election_parties_election_id_number_unique` (`election_id`,`number`),
  UNIQUE KEY `election_parties_election_id_name_unique` (`election_id`,`name`),
  KEY `election_parties_applied_by_foreign` (`applied_by`),
  KEY `election_parties_reviewed_by_foreign` (`reviewed_by`),
  CONSTRAINT `election_parties_election_id_foreign` FOREIGN KEY (`election_id`) REFERENCES `elections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `election_parties_applied_by_foreign` FOREIGN KEY (`applied_by`) REFERENCES `users` (`id`),
  CONSTRAINT `election_parties_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- 4) 2026_07_31_000004_create_election_party_members_table
-- ---------------------------------------------------------------------
CREATE TABLE `election_party_members` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `party_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `role` enum('leader','deputy','secretary','treasurer','member') NOT NULL,
  `position_label` varchar(80) DEFAULT NULL,
  `sort_order` smallint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `election_party_members_party_id_user_id_unique` (`party_id`,`user_id`),
  KEY `election_party_members_user_id_foreign` (`user_id`),
  CONSTRAINT `election_party_members_party_id_foreign` FOREIGN KEY (`party_id`) REFERENCES `election_parties` (`id`) ON DELETE CASCADE,
  CONSTRAINT `election_party_members_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- 5) 2026_07_31_000005_create_election_voters_table
-- ---------------------------------------------------------------------
CREATE TABLE `election_voters` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `election_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `academy_member_id` bigint unsigned DEFAULT NULL,
  `member_code` varchar(20) DEFAULT NULL,
  `display_name` varchar(150) NOT NULL,
  `voter_type` enum('student','staff') NOT NULL,
  `grade_level` varchar(10) DEFAULT NULL,
  `classroom_name` varchar(50) DEFAULT NULL,
  `student_number` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `election_voters_election_id_user_id_unique` (`election_id`,`user_id`),
  KEY `election_voters_user_id_foreign` (`user_id`),
  KEY `election_voters_academy_member_id_foreign` (`academy_member_id`),
  KEY `election_voters_election_id_grade_level_index` (`election_id`,`grade_level`),
  KEY `election_voters_election_id_member_code_index` (`election_id`,`member_code`),
  KEY `election_voters_election_id_student_number_index` (`election_id`,`student_number`),
  CONSTRAINT `election_voters_election_id_foreign` FOREIGN KEY (`election_id`) REFERENCES `elections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `election_voters_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `election_voters_academy_member_id_foreign` FOREIGN KEY (`academy_member_id`) REFERENCES `academy_members` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- 6) 2026_07_31_000006_create_election_voter_receipts_table
-- ---------------------------------------------------------------------
CREATE TABLE `election_voter_receipts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `election_id` bigint unsigned NOT NULL,
  `election_voter_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `station_id` bigint unsigned NOT NULL,
  `issued_by` bigint unsigned NOT NULL,
  `status` enum('issued','cast','void','expired') NOT NULL DEFAULT 'issued',
  `token_hash` char(64) DEFAULT NULL,
  `token_expires_at` datetime DEFAULT NULL,
  `issued_at` datetime NOT NULL,
  `cast_at` datetime DEFAULT NULL,
  `void_reason` varchar(200) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `election_voter_receipts_election_id_user_id_unique` (`election_id`,`user_id`),
  KEY `election_voter_receipts_election_voter_id_foreign` (`election_voter_id`),
  KEY `election_voter_receipts_user_id_foreign` (`user_id`),
  KEY `election_voter_receipts_issued_by_foreign` (`issued_by`),
  KEY `election_voter_receipts_election_id_status_index` (`election_id`,`status`),
  KEY `election_voter_receipts_station_id_index` (`station_id`),
  CONSTRAINT `election_voter_receipts_election_id_foreign` FOREIGN KEY (`election_id`) REFERENCES `elections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `election_voter_receipts_election_voter_id_foreign` FOREIGN KEY (`election_voter_id`) REFERENCES `election_voters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `election_voter_receipts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `election_voter_receipts_station_id_foreign` FOREIGN KEY (`station_id`) REFERENCES `election_stations` (`id`),
  CONSTRAINT `election_voter_receipts_issued_by_foreign` FOREIGN KEY (`issued_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- 7) 2026_07_31_000007_create_election_ballots_table
--    (ตารางบัตรลงคะแนน — PK เป็น uuid ไม่มี timestamps เพื่อไม่ให้โยงกลับหาผู้ลงคะแนน)
-- ---------------------------------------------------------------------
CREATE TABLE `election_ballots` (
  `uuid` char(36) NOT NULL,
  `election_id` bigint unsigned NOT NULL,
  `party_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`uuid`),
  KEY `election_ballots_party_id_foreign` (`party_id`),
  KEY `election_ballots_election_id_index` (`election_id`),
  CONSTRAINT `election_ballots_election_id_foreign` FOREIGN KEY (`election_id`) REFERENCES `elections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `election_ballots_party_id_foreign` FOREIGN KEY (`party_id`) REFERENCES `election_parties` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- 8) 2026_07_31_000008_create_election_results_table
--    (published_at / published_by ยัง NOT NULL ตาม migration เดิม)
-- ---------------------------------------------------------------------
CREATE TABLE `election_results` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `election_id` bigint unsigned NOT NULL,
  `party_id` bigint unsigned DEFAULT NULL,
  `votes` int unsigned NOT NULL,
  `rank` smallint unsigned DEFAULT NULL,
  `is_winner` tinyint(1) NOT NULL DEFAULT '0',
  `published_at` datetime NOT NULL,
  `published_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `election_results_election_id_party_id_unique` (`election_id`,`party_id`),
  KEY `election_results_party_id_foreign` (`party_id`),
  KEY `election_results_published_by_foreign` (`published_by`),
  CONSTRAINT `election_results_election_id_foreign` FOREIGN KEY (`election_id`) REFERENCES `elections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `election_results_party_id_foreign` FOREIGN KEY (`party_id`) REFERENCES `election_parties` (`id`) ON DELETE SET NULL,
  CONSTRAINT `election_results_published_by_foreign` FOREIGN KEY (`published_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- 9) 2026_07_31_000009_make_election_results_publication_nullable
-- ---------------------------------------------------------------------
ALTER TABLE `election_results`
  MODIFY `published_at` datetime NULL,
  MODIFY `published_by` bigint unsigned NULL;


-- ---------------------------------------------------------------------
-- 10) 2026_08_01_000010_add_education_level_to_elections_and_members
--     (แม้ชื่อจะพูดถึง members แต่ up() แตะเฉพาะ elections)
-- ---------------------------------------------------------------------
ALTER TABLE `elections`
  ADD COLUMN `education_level` tinyint unsigned NULL AFTER `status`;


-- ---------------------------------------------------------------------
-- 11) 2026_08_01_000011_add_education_level_to_academy_members
-- ---------------------------------------------------------------------
ALTER TABLE `academy_members`
  ADD COLUMN `education_level` tinyint unsigned NULL AFTER `student_id`;


-- ---------------------------------------------------------------------
-- 12) 2026_08_02_000001_create_house_assignment_batches_table
-- ---------------------------------------------------------------------
CREATE TABLE `house_assignment_batches` (
  `id` char(36) NOT NULL,
  `academy_id` bigint unsigned NOT NULL,
  `academic_year_id` bigint unsigned NOT NULL,
  `mode` enum('random','import') NOT NULL,
  `status` enum('draft','committed','undone') NOT NULL DEFAULT 'draft',
  `options` json NOT NULL,
  `summary` json DEFAULT NULL,
  `source_filename` varchar(191) DEFAULT NULL,
  `created_by_user_id` bigint unsigned NOT NULL,
  `committed_at` timestamp NULL DEFAULT NULL,
  `committed_by_user_id` bigint unsigned DEFAULT NULL,
  `undone_at` timestamp NULL DEFAULT NULL,
  `undone_by_user_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `house_assignment_batches_academy_id_foreign` (`academy_id`),
  KEY `house_assignment_batches_academic_year_id_foreign` (`academic_year_id`),
  KEY `house_assignment_batches_created_by_user_id_foreign` (`created_by_user_id`),
  KEY `house_assignment_batches_committed_by_user_id_foreign` (`committed_by_user_id`),
  KEY `house_assignment_batches_undone_by_user_id_foreign` (`undone_by_user_id`),
  KEY `hab_academy_year_status_idx` (`academy_id`,`academic_year_id`,`status`),
  CONSTRAINT `house_assignment_batches_academy_id_foreign` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `house_assignment_batches_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE,
  CONSTRAINT `house_assignment_batches_created_by_user_id_foreign` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `house_assignment_batches_committed_by_user_id_foreign` FOREIGN KEY (`committed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `house_assignment_batches_undone_by_user_id_foreign` FOREIGN KEY (`undone_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- 13) 2026_08_02_000002_create_house_assignment_rows_table
--     (ยังไม่มี previous_house_group_id — เพิ่มในข้อ 15)
-- ---------------------------------------------------------------------
CREATE TABLE `house_assignment_rows` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `batch_id` char(36) NOT NULL,
  `row_number` int unsigned NOT NULL,
  `raw` json DEFAULT NULL,
  `student_id` bigint unsigned DEFAULT NULL,
  `house_group_id` bigint unsigned DEFAULT NULL,
  `status` enum('ok','unmatched','ambiguous','unknown_house','already_assigned','skipped') NOT NULL,
  `message` text,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `house_assignment_rows_student_id_foreign` (`student_id`),
  KEY `house_assignment_rows_house_group_id_foreign` (`house_group_id`),
  KEY `house_assignment_rows_batch_id_status_index` (`batch_id`,`status`),
  CONSTRAINT `house_assignment_rows_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE SET NULL,
  CONSTRAINT `house_assignment_rows_house_group_id_foreign` FOREIGN KEY (`house_group_id`) REFERENCES `academy_groups` (`id`) ON DELETE SET NULL,
  CONSTRAINT `house_assignment_rows_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `house_assignment_batches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- 14) 2026_08_02_000003_create_house_memberships_table
-- ---------------------------------------------------------------------
CREATE TABLE `house_memberships` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_id` bigint unsigned NOT NULL,
  `academic_year_id` bigint unsigned NOT NULL,
  `house_group_id` bigint unsigned NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `source` enum('random','import','manual') NOT NULL,
  `batch_id` char(36) DEFAULT NULL,
  `assigned_by_user_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `house_memberships_academic_year_id_student_id_unique` (`academic_year_id`,`student_id`),
  KEY `house_memberships_academy_id_foreign` (`academy_id`),
  KEY `house_memberships_house_group_id_foreign` (`house_group_id`),
  KEY `house_memberships_student_id_foreign` (`student_id`),
  KEY `house_memberships_user_id_foreign` (`user_id`),
  KEY `house_memberships_assigned_by_user_id_foreign` (`assigned_by_user_id`),
  KEY `hm_academy_year_house_idx` (`academy_id`,`academic_year_id`,`house_group_id`),
  CONSTRAINT `house_memberships_academy_id_foreign` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `house_memberships_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE,
  CONSTRAINT `house_memberships_house_group_id_foreign` FOREIGN KEY (`house_group_id`) REFERENCES `academy_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `house_memberships_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `house_memberships_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `house_memberships_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `house_assignment_batches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- 15) 2026_08_02_000004_add_previous_house_group_to_assignment_rows
-- ---------------------------------------------------------------------
ALTER TABLE `house_assignment_rows`
  ADD COLUMN `previous_house_group_id` bigint unsigned NULL AFTER `house_group_id`,
  ADD KEY `house_assignment_rows_previous_house_group_id_foreign` (`previous_house_group_id`),
  ADD CONSTRAINT `house_assignment_rows_previous_house_group_id_foreign`
    FOREIGN KEY (`previous_house_group_id`) REFERENCES `academy_groups` (`id`) ON DELETE SET NULL;


-- =====================================================================
-- บันทึกลงตาราง migrations
-- ใช้ batch ใหม่ก้อนเดียว (= MAX(batch) + 1) เพื่อให้ rollback ทีเดียวจบ
-- ถ้าอยากคง batch เดิมของเครื่อง dev ให้ใช้บล็อกทางเลือกด้านล่างแทน
-- =====================================================================
SET @batch := (SELECT IFNULL(MAX(`batch`), 0) + 1 FROM `migrations`);

INSERT INTO `migrations` (`migration`, `batch`) VALUES
  ('2026_07_31_000001_create_elections_table', @batch),
  ('2026_07_31_000002_create_election_stations_table', @batch),
  ('2026_07_31_000003_create_election_parties_table', @batch),
  ('2026_07_31_000004_create_election_party_members_table', @batch),
  ('2026_07_31_000005_create_election_voters_table', @batch),
  ('2026_07_31_000006_create_election_voter_receipts_table', @batch),
  ('2026_07_31_000007_create_election_ballots_table', @batch),
  ('2026_07_31_000008_create_election_results_table', @batch),
  ('2026_07_31_000009_make_election_results_publication_nullable', @batch),
  ('2026_08_01_000010_add_education_level_to_elections_and_members', @batch),
  ('2026_08_01_000011_add_education_level_to_academy_members', @batch),
  ('2026_08_02_000001_create_house_assignment_batches_table', @batch),
  ('2026_08_02_000002_create_house_assignment_rows_table', @batch),
  ('2026_08_02_000003_create_house_memberships_table', @batch),
  ('2026_08_02_000004_add_previous_house_group_to_assignment_rows', @batch);

COMMIT;

-- ---------------------------------------------------------------------
-- ทางเลือก: คง batch เดิมตามเครื่อง dev (111–115)
-- ---------------------------------------------------------------------
-- INSERT INTO `migrations` (`migration`, `batch`) VALUES
--   ('2026_07_31_000001_create_elections_table', 111),
--   ('2026_07_31_000002_create_election_stations_table', 111),
--   ('2026_07_31_000003_create_election_parties_table', 111),
--   ('2026_07_31_000004_create_election_party_members_table', 111),
--   ('2026_07_31_000005_create_election_voters_table', 111),
--   ('2026_07_31_000006_create_election_voter_receipts_table', 111),
--   ('2026_07_31_000007_create_election_ballots_table', 111),
--   ('2026_07_31_000008_create_election_results_table', 111),
--   ('2026_07_31_000009_make_election_results_publication_nullable', 111),
--   ('2026_08_01_000010_add_education_level_to_elections_and_members', 112),
--   ('2026_08_01_000011_add_education_level_to_academy_members', 112),
--   ('2026_08_02_000001_create_house_assignment_batches_table', 113),
--   ('2026_08_02_000002_create_house_assignment_rows_table', 113),
--   ('2026_08_02_000003_create_house_memberships_table', 114),
--   ('2026_08_02_000004_add_previous_house_group_to_assignment_rows', 115);


-- =====================================================================
-- ROLLBACK (ถ้าต้องถอยกลับ) — รันตามลำดับนี้เท่านั้น เพราะติด FK
-- =====================================================================
-- ALTER TABLE `house_assignment_rows`
--   DROP FOREIGN KEY `house_assignment_rows_previous_house_group_id_foreign`,
--   DROP COLUMN `previous_house_group_id`;
-- DROP TABLE IF EXISTS `house_memberships`;
-- DROP TABLE IF EXISTS `house_assignment_rows`;
-- DROP TABLE IF EXISTS `house_assignment_batches`;
-- ALTER TABLE `academy_members` DROP COLUMN `education_level`;
-- ALTER TABLE `elections` DROP COLUMN `education_level`;
-- DROP TABLE IF EXISTS `election_results`;
-- DROP TABLE IF EXISTS `election_ballots`;
-- DROP TABLE IF EXISTS `election_voter_receipts`;
-- DROP TABLE IF EXISTS `election_voters`;
-- DROP TABLE IF EXISTS `election_party_members`;
-- DROP TABLE IF EXISTS `election_parties`;
-- DROP TABLE IF EXISTS `election_stations`;
-- DROP TABLE IF EXISTS `elections`;
-- DELETE FROM `migrations` WHERE `migration` >= '2026_07_31' AND `migration` < '2026_08_03';
