<?php

return [
    'pp_per_baht_official' => 1200,
    'donation_pp_per_baht' => 1080,
    'claim_cost' => 270,
    'claim_reward_claimer' => 220,
    'claim_reward_suggester' => 30,
    'claim_reward_platform' => 20,
    'claim_cap_per_donation_per_day' => 5,
    'claim_cap_total_per_day' => 20,
    'platform_personal_code' => 99999999,
    'academy_claim_cost' => 270,
    'academy_claim_max_cost' => 270,
    'academy_claim_ratio_claimer' => 0.7778,
    'academy_claim_ratio_suggester' => 0.1111,
    'academy_claim_ratio_school' => 0.0741,
    'academy_claim_ratio_platform' => 0.0370,
    'academy_claim_cap_per_donation_per_day' => 5,
    'academy_claim_cap_total_per_day' => 20,
    'course_claim_cost' => 270,
    'course_claim_max_cost' => 270,
    'course_claim_ratio_claimer' => 0.7778,
    'course_claim_ratio_suggester' => 0.1111,
    'course_claim_ratio_course' => 0.0741,
    'course_claim_ratio_platform' => 0.0370,
    'course_claim_cap_per_donation_per_day' => 5,
    'course_claim_cap_total_per_day' => 20,

    // ปิดการโอนแต้ม/เงินระหว่างสมาชิก (กันทุจริต/ฟอกเงิน) — แต้มและเงินใช้ได้แค่สะสม ใช้จ่าย และถอนโดยเจ้าของบัญชี
    // เปิดคืนได้ทาง env เท่านั้น ห้ามเปิดโดยไม่ผ่านการตัดสินใจของเจ้าของระบบ
    'p2p_transfers_enabled' => (bool) env('ECONOMY_P2P_TRANSFERS_ENABLED', false),
];
