-- ============================================================
-- Restore missing tables (generated 2026-07-21 06:16 from nuxnan_nuxnan_db)
-- สร้างตารางที่หายไป 19 ตาราง + บันทึกการรันลงตาราง migrations (11 รายการ)
-- ปลอดภัยต่อการรันซ้ำ: ใช้ IF NOT EXISTS และเช็คก่อน insert migrations
-- Engine: InnoDB พร้อม FOREIGN KEY ตามที่ประกาศใน migration
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- Migration: 2025_06_22_create_member_activity_logs_table
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `member_activity_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL COMMENT 'User who performed the action',
  `target_user_id` bigint unsigned DEFAULT NULL COMMENT 'User who was affected',
  `academy_member_id` bigint unsigned DEFAULT NULL COMMENT 'Related member record',
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Action type: join, leave, approve, reject, suspend, role_change, etc.',
  `action_category` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'member' COMMENT 'Category: member, role, course, attendance, etc.',
  `old_values` json DEFAULT NULL COMMENT 'Previous values before change',
  `new_values` json DEFAULT NULL COMMENT 'New values after change',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT 'Human-readable description',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `member_activity_logs_user_id_foreign` (`user_id`),
  KEY `member_activity_logs_target_user_id_foreign` (`target_user_id`),
  KEY `member_activity_logs_academy_member_id_foreign` (`academy_member_id`),
  KEY `member_activity_logs_academy_id_created_at_index` (`academy_id`,`created_at`),
  KEY `member_activity_logs_academy_id_action_index` (`academy_id`,`action`),
  KEY `member_activity_logs_academy_id_user_id_index` (`academy_id`,`user_id`),
  KEY `member_activity_logs_academy_id_target_user_id_index` (`academy_id`,`target_user_id`),
  CONSTRAINT `member_activity_logs_academy_id_foreign` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `member_activity_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `member_activity_logs_target_user_id_foreign` FOREIGN KEY (`target_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `member_activity_logs_academy_member_id_foreign` FOREIGN KEY (`academy_member_id`) REFERENCES `academy_members` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Migration: 2025_06_22_create_academy_invite_links_table
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `academy_invite_links` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_id` bigint unsigned NOT NULL,
  `created_by` bigint unsigned DEFAULT NULL COMMENT 'User who created the link',
  `code` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Unique invite code',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Name/label for the invite link',
  `description` text COLLATE utf8mb4_unicode_ci,
  `academy_role_id` bigint unsigned DEFAULT NULL COMMENT 'Default role for new members',
  `max_uses` int DEFAULT NULL COMMENT 'Maximum number of uses, null = unlimited',
  `use_count` int NOT NULL DEFAULT '0' COMMENT 'Current usage count',
  `expires_at` timestamp NULL DEFAULT NULL COMMENT 'Expiration date, null = never expires',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `require_approval` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Whether new members need approval',
  `allowed_domains` json DEFAULT NULL COMMENT 'Restrict to specific email domains',
  `metadata` json DEFAULT NULL COMMENT 'Additional settings',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `academy_invite_links_code_unique` (`code`),
  KEY `academy_invite_links_created_by_foreign` (`created_by`),
  KEY `academy_invite_links_academy_role_id_foreign` (`academy_role_id`),
  KEY `academy_invite_links_academy_id_is_active_index` (`academy_id`,`is_active`),
  KEY `academy_invite_links_code_index` (`code`),
  CONSTRAINT `academy_invite_links_academy_id_foreign` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `academy_invite_links_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `academy_invite_links_academy_role_id_foreign` FOREIGN KEY (`academy_role_id`) REFERENCES `academy_roles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Migration: 2025_10_26_070433_create_jsm_student_info_table
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `jsm_student_info` (
  `id` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `citizen_id` varchar(18) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `student_id` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `classroom` varchar(9) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title_prefix` varchar(12) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `first_name` varchar(18) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `middle_name` varchar(8) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `en_title_prefix` varchar(12) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `en_first_name` varchar(14) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `en_last_name` varchar(17) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `en_middle_name` varchar(18) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_of_birth` varchar(11) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gender` varchar(4) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nationality` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `religion` varchar(13) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `student_status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `record_date` varchar(13) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `disability_type` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `house_code` varchar(13) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `house_number` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `village_number` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alley` varchar(23) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `road` varchar(18) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subdistrict` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `district` varchar(18) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `province` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code` varchar(12) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_number` varchar(13) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `enrollment_date` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `father_citizen_id` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `father_title_prefix` varchar(19) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `father_first_name` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `father_last_name` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `father_status` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `father_nationality` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mother_citizen_id` varchar(26) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mother_title_prefix` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mother_first_name` varchar(19) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mother_last_name` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mother_status` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mother_nationality` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guardian_citizen_id` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guardian_title_prefix` varchar(12) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guardian_full_name` varchar(28) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guardian_occupation` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guardian_phone_number` varchar(14) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `relationship` varchar(12) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `height_cm` varchar(13) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `weight_kg` varchar(13) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `previous_school_name` varchar(54) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `previous_school_province` varchar(19) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `previous_grade_level` varchar(18) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Migration: 2025_10_26_070433_create_mental_math_scores_table
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `mental_math_scores` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `player_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `score` int NOT NULL,
  `difficulty` enum('easy','medium','hard') COLLATE utf8mb4_unicode_ci NOT NULL,
  `combo` int NOT NULL DEFAULT '0',
  `questions_answered` int NOT NULL DEFAULT '0',
  `accuracy` double NOT NULL DEFAULT '0',
  `time_spent` int NOT NULL DEFAULT '0',
  `is_practice_mode` tinyint(1) NOT NULL DEFAULT '0',
  `is_daily_challenge` tinyint(1) NOT NULL DEFAULT '0',
  `date_played` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mental_math_scores_user_id_foreign` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Migration: 2025_10_26_071536_create_teams_table
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `teams` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `personal_team` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `teams_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Migration: 2025_10_26_071537_create_team_user_table
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `team_user` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `team_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `role` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `team_user_team_id_user_id_unique` (`team_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Migration: 2025_10_26_071538_create_team_invitations_table
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `team_invitations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `team_id` bigint unsigned NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `team_invitations_team_id_email_unique` (`team_id`,`email`),
  CONSTRAINT `team_invitations_team_id_foreign` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Migration: 2026_01_31_100000_create_permissions_table
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `group` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `role_permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `role_id` bigint unsigned NOT NULL,
  `permission_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_permissions_role_id_permission_id_unique` (`role_id`,`permission_id`),
  KEY `role_permissions_permission_id_foreign` (`permission_id`),
  CONSTRAINT `role_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `permission_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_permissions_user_id_permission_id_unique` (`user_id`,`permission_id`),
  KEY `user_permissions_permission_id_foreign` (`permission_id`),
  CONSTRAINT `user_permissions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Migration: 2026_02_01_183529_create_student_health_records_table
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `student_health_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned NOT NULL,
  `weight` decimal(5,2) DEFAULT NULL COMMENT 'น้ำหนัก (kg)',
  `height` decimal(5,2) DEFAULT NULL COMMENT 'ส่วนสูง (cm)',
  `allergies` text COLLATE utf8mb4_unicode_ci COMMENT 'ประวัติการแพ้',
  `underlying_disease` text COLLATE utf8mb4_unicode_ci COMMENT 'โรคประจำตัว',
  `recorded_at` date DEFAULT NULL COMMENT 'วันที่บันทึกข้อมูล',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_health_records_student_id_index` (`student_id`),
  CONSTRAINT `student_health_records_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Migration: 2026_02_12_100000_create_school_store_tables
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `academy_stores` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_id` bigint unsigned NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `logo` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `banner_image` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `allow_points_payment` tinyint(1) NOT NULL DEFAULT '1',
  `allow_wallet_payment` tinyint(1) NOT NULL DEFAULT '1',
  `min_order_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `settings` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `academy_stores_academy_id_unique` (`academy_id`),
  CONSTRAINT `academy_stores_academy_id_foreign` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `store_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_store_id` bigint unsigned NOT NULL,
  `parent_id` bigint unsigned DEFAULT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `icon` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `store_categories_academy_store_id_slug_unique` (`academy_store_id`,`slug`),
  KEY `store_categories_parent_id_foreign` (`parent_id`),
  CONSTRAINT `store_categories_academy_store_id_foreign` FOREIGN KEY (`academy_store_id`) REFERENCES `academy_stores` (`id`) ON DELETE CASCADE,
  CONSTRAINT `store_categories_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `store_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `store_products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_store_id` bigint unsigned NOT NULL,
  `category_id` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `compare_price` decimal(10,2) DEFAULT NULL,
  `cost_price` decimal(10,2) DEFAULT NULL,
  `sku` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stock_quantity` int NOT NULL DEFAULT '0',
  `low_stock_threshold` int NOT NULL DEFAULT '5',
  `images` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_featured` tinyint(1) NOT NULL DEFAULT '0',
  `payment_type` enum('points','wallet','both') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'both',
  `points_price` int DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `total_sold` int NOT NULL DEFAULT '0',
  `average_rating` decimal(3,2) NOT NULL DEFAULT '0.00',
  `review_count` int NOT NULL DEFAULT '0',
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `store_products_academy_store_id_slug_unique` (`academy_store_id`,`slug`),
  KEY `store_products_created_by_foreign` (`created_by`),
  KEY `store_products_academy_store_id_is_active_is_featured_index` (`academy_store_id`,`is_active`,`is_featured`),
  KEY `store_products_category_id_is_active_index` (`category_id`,`is_active`),
  CONSTRAINT `store_products_academy_store_id_foreign` FOREIGN KEY (`academy_store_id`) REFERENCES `academy_stores` (`id`) ON DELETE CASCADE,
  CONSTRAINT `store_products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `store_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `store_products_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `store_orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_store_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `order_number` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','confirmed','processing','ready','completed','cancelled','refunded') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `payment_method` enum('points','wallet') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'wallet',
  `points_spent` int DEFAULT NULL,
  `payment_status` enum('pending','paid','refunded') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `cancel_reason` text COLLATE utf8mb4_unicode_ci,
  `processed_by` bigint unsigned DEFAULT NULL,
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `store_orders_order_number_unique` (`order_number`),
  KEY `store_orders_processed_by_foreign` (`processed_by`),
  KEY `store_orders_academy_store_id_status_index` (`academy_store_id`,`status`),
  KEY `store_orders_user_id_status_index` (`user_id`,`status`),
  KEY `store_orders_order_number_index` (`order_number`),
  CONSTRAINT `store_orders_academy_store_id_foreign` FOREIGN KEY (`academy_store_id`) REFERENCES `academy_stores` (`id`) ON DELETE CASCADE,
  CONSTRAINT `store_orders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `store_orders_processed_by_foreign` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `store_order_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `store_order_id` bigint unsigned NOT NULL,
  `store_product_id` bigint unsigned DEFAULT NULL,
  `product_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `product_sku` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `unit_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `unit_points_price` int DEFAULT NULL,
  `total_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `store_order_items_store_order_id_foreign` (`store_order_id`),
  KEY `store_order_items_store_product_id_foreign` (`store_product_id`),
  CONSTRAINT `store_order_items_store_order_id_foreign` FOREIGN KEY (`store_order_id`) REFERENCES `store_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `store_order_items_store_product_id_foreign` FOREIGN KEY (`store_product_id`) REFERENCES `store_products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `store_product_reviews` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `store_product_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `store_order_id` bigint unsigned DEFAULT NULL,
  `rating` tinyint unsigned NOT NULL DEFAULT '5',
  `comment` text COLLATE utf8mb4_unicode_ci,
  `is_approved` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `spr_product_user_order_unique` (`store_product_id`,`user_id`,`store_order_id`),
  KEY `store_product_reviews_user_id_foreign` (`user_id`),
  KEY `store_product_reviews_store_order_id_foreign` (`store_order_id`),
  KEY `spr_product_approved_idx` (`store_product_id`,`is_approved`),
  CONSTRAINT `store_product_reviews_store_product_id_foreign` FOREIGN KEY (`store_product_id`) REFERENCES `store_products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `store_product_reviews_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `store_product_reviews_store_order_id_foreign` FOREIGN KEY (`store_order_id`) REFERENCES `store_orders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Migration: 2026_04_15_100001_create_academy_post_comment_likes_dislikes_tables
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `academy_post_comment_likes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_post_comment_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `comment_like_unique` (`academy_post_comment_id`,`user_id`),
  KEY `academy_post_comment_likes_user_id_foreign` (`user_id`),
  CONSTRAINT `academy_post_comment_likes_academy_post_comment_id_foreign` FOREIGN KEY (`academy_post_comment_id`) REFERENCES `academy_post_comments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `academy_post_comment_likes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `academy_post_comment_dislikes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_post_comment_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `comment_dislike_unique` (`academy_post_comment_id`,`user_id`),
  KEY `academy_post_comment_dislikes_user_id_foreign` (`user_id`),
  CONSTRAINT `academy_post_comment_dislikes_academy_post_comment_id_foreign` FOREIGN KEY (`academy_post_comment_id`) REFERENCES `academy_post_comments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `academy_post_comment_dislikes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- บันทึกการรันลงตาราง migrations (ข้ามรายการที่มีอยู่แล้ว)
-- ------------------------------------------------------------
SET @next_batch = (SELECT COALESCE(MAX(batch), 0) + 1 FROM (SELECT batch FROM migrations) AS m);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2025_06_22_create_member_activity_logs_table', @next_batch
WHERE NOT EXISTS (SELECT 1 FROM (SELECT migration FROM migrations) AS m WHERE m.migration = '2025_06_22_create_member_activity_logs_table');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2025_06_22_create_academy_invite_links_table', @next_batch
WHERE NOT EXISTS (SELECT 1 FROM (SELECT migration FROM migrations) AS m WHERE m.migration = '2025_06_22_create_academy_invite_links_table');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2025_10_26_070433_create_jsm_student_info_table', @next_batch
WHERE NOT EXISTS (SELECT 1 FROM (SELECT migration FROM migrations) AS m WHERE m.migration = '2025_10_26_070433_create_jsm_student_info_table');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2025_10_26_070433_create_mental_math_scores_table', @next_batch
WHERE NOT EXISTS (SELECT 1 FROM (SELECT migration FROM migrations) AS m WHERE m.migration = '2025_10_26_070433_create_mental_math_scores_table');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2025_10_26_071536_create_teams_table', @next_batch
WHERE NOT EXISTS (SELECT 1 FROM (SELECT migration FROM migrations) AS m WHERE m.migration = '2025_10_26_071536_create_teams_table');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2025_10_26_071537_create_team_user_table', @next_batch
WHERE NOT EXISTS (SELECT 1 FROM (SELECT migration FROM migrations) AS m WHERE m.migration = '2025_10_26_071537_create_team_user_table');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2025_10_26_071538_create_team_invitations_table', @next_batch
WHERE NOT EXISTS (SELECT 1 FROM (SELECT migration FROM migrations) AS m WHERE m.migration = '2025_10_26_071538_create_team_invitations_table');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_01_31_100000_create_permissions_table', @next_batch
WHERE NOT EXISTS (SELECT 1 FROM (SELECT migration FROM migrations) AS m WHERE m.migration = '2026_01_31_100000_create_permissions_table');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_02_01_183529_create_student_health_records_table', @next_batch
WHERE NOT EXISTS (SELECT 1 FROM (SELECT migration FROM migrations) AS m WHERE m.migration = '2026_02_01_183529_create_student_health_records_table');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_02_12_100000_create_school_store_tables', @next_batch
WHERE NOT EXISTS (SELECT 1 FROM (SELECT migration FROM migrations) AS m WHERE m.migration = '2026_02_12_100000_create_school_store_tables');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_04_15_100001_create_academy_post_comment_likes_dislikes_tables', @next_batch
WHERE NOT EXISTS (SELECT 1 FROM (SELECT migration FROM migrations) AS m WHERE m.migration = '2026_04_15_100001_create_academy_post_comment_likes_dislikes_tables');
