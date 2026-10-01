-- =====================================================================
-- Orphan-check สำหรับ academy_donate_claims (read-only) — ก่อน deploy prod
-- คู่กับ migration: 2026_09_27_000003_repair_academy_donate_claims_foreign_keys.php
--
-- migration 000003 เติม FK 7 คอลัมน์ และจะ **ล้มเหลว (errno 1452)** ถ้ามี orphan row
-- (ค่าไม่ null แต่ไม่มี parent row ตรง id). รัน query ชุดนี้บน prod ก่อน migrate เพื่อยืนยัน
-- ว่ามี 0 orphan. ถ้าเจอ orphan → เคลียร์/ซ่อมก่อน ค่อยรัน migration.
--
-- ทั้งหมดเป็น SELECT (ไม่แก้ข้อมูล) รันซ้ำได้เสมอ. ใช้ผ่าน phpMyAdmin หรือ mysql CLI
-- ได้เลย. ถ้ารัน artisan ได้ ใช้ `php artisan academy:donate-claims-orphan-check` แทนได้
-- (ให้ exit code + JSON สำหรับ gate ใน deploy script).
--
-- หมายเหตุ: academy_donate_id ตั้งใจไม่มี FK (legacy academy_donates ไม่ FK-compatible)
--           → ไม่อยู่ในการเช็คนี้.
-- เช็คการมีอยู่ของ parent ระดับกายภาพ (NOT EXISTS) — FK สนใจแถวจริง ไม่ใช่ soft-delete scope.
-- =====================================================================

-- (1) สรุปรวม: orphan รวมทุกคอลัมน์ (แถวเดียว, ควรได้ 0 ทุกช่อง)
SELECT
    (SELECT COUNT(*) FROM academy_donate_claims c
       WHERE c.academy_id IS NOT NULL
         AND NOT EXISTS (SELECT 1 FROM academies p WHERE p.id = c.academy_id))                       AS academy_id,
    (SELECT COUNT(*) FROM academy_donate_claims c
       WHERE c.claimer_id IS NOT NULL
         AND NOT EXISTS (SELECT 1 FROM users p WHERE p.id = c.claimer_id))                            AS claimer_id,
    (SELECT COUNT(*) FROM academy_donate_claims c
       WHERE c.suggester_id IS NOT NULL
         AND NOT EXISTS (SELECT 1 FROM users p WHERE p.id = c.suggester_id))                          AS suggester_id,
    (SELECT COUNT(*) FROM academy_donate_claims c
       WHERE c.claimer_transaction_id IS NOT NULL
         AND NOT EXISTS (SELECT 1 FROM points_transactions p WHERE p.id = c.claimer_transaction_id))  AS claimer_transaction_id,
    (SELECT COUNT(*) FROM academy_donate_claims c
       WHERE c.suggester_transaction_id IS NOT NULL
         AND NOT EXISTS (SELECT 1 FROM points_transactions p WHERE p.id = c.suggester_transaction_id)) AS suggester_transaction_id,
    (SELECT COUNT(*) FROM academy_donate_claims c
       WHERE c.school_transaction_id IS NOT NULL
         AND NOT EXISTS (SELECT 1 FROM academy_point_transactions p WHERE p.id = c.school_transaction_id)) AS school_transaction_id,
    (SELECT COUNT(*) FROM academy_donate_claims c
       WHERE c.platform_transaction_id IS NOT NULL
         AND NOT EXISTS (SELECT 1 FROM points_transactions p WHERE p.id = c.platform_transaction_id)) AS platform_transaction_id;

-- =====================================================================
-- (2) รายละเอียด: id ของ orphan row แต่ละคอลัมน์ (ไว้ตามไปเคลียร์ ถ้าเจอ)
--     ถ้า (1) ได้ 0 ทุกช่อง ไม่ต้องรันส่วนนี้.
-- =====================================================================

SELECT c.id, 'academy_id' AS bad_column, c.academy_id AS bad_value
  FROM academy_donate_claims c
 WHERE c.academy_id IS NOT NULL
   AND NOT EXISTS (SELECT 1 FROM academies p WHERE p.id = c.academy_id)
UNION ALL
SELECT c.id, 'claimer_id', c.claimer_id
  FROM academy_donate_claims c
 WHERE c.claimer_id IS NOT NULL
   AND NOT EXISTS (SELECT 1 FROM users p WHERE p.id = c.claimer_id)
UNION ALL
SELECT c.id, 'suggester_id', c.suggester_id
  FROM academy_donate_claims c
 WHERE c.suggester_id IS NOT NULL
   AND NOT EXISTS (SELECT 1 FROM users p WHERE p.id = c.suggester_id)
UNION ALL
SELECT c.id, 'claimer_transaction_id', c.claimer_transaction_id
  FROM academy_donate_claims c
 WHERE c.claimer_transaction_id IS NOT NULL
   AND NOT EXISTS (SELECT 1 FROM points_transactions p WHERE p.id = c.claimer_transaction_id)
UNION ALL
SELECT c.id, 'suggester_transaction_id', c.suggester_transaction_id
  FROM academy_donate_claims c
 WHERE c.suggester_transaction_id IS NOT NULL
   AND NOT EXISTS (SELECT 1 FROM points_transactions p WHERE p.id = c.suggester_transaction_id)
UNION ALL
SELECT c.id, 'school_transaction_id', c.school_transaction_id
  FROM academy_donate_claims c
 WHERE c.school_transaction_id IS NOT NULL
   AND NOT EXISTS (SELECT 1 FROM academy_point_transactions p WHERE p.id = c.school_transaction_id)
UNION ALL
SELECT c.id, 'platform_transaction_id', c.platform_transaction_id
  FROM academy_donate_claims c
 WHERE c.platform_transaction_id IS NOT NULL
   AND NOT EXISTS (SELECT 1 FROM points_transactions p WHERE p.id = c.platform_transaction_id)
 ORDER BY id;
