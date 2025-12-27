<?php

namespace App\Enums;

/**
 * ActivityType Enum
 * 
 * Defines all possible activity types in the system.
 * Uses snake_case convention for consistency with REST API standards.
 * 
 * @since PHP 8.1+
 */
enum ActivityType: string
{
    // Post Activities
    case CREATE_POST = 'create_post';
    case UPDATE_POST = 'update_post';
    case DELETE_POST = 'delete_post';
    case SHARE_POST = 'share_post';

    // Comment Activities
    case CREATE_COMMENT = 'create_comment';
    case UPDATE_COMMENT = 'update_comment';
    case DELETE_COMMENT = 'delete_comment';

    // Reaction Activities
    case LIKE = 'like';
    case DISLIKE = 'dislike';

    // Donation Activities
    case GIVE_DONATION = 'give_donation';
    case RECEIVE_DONATION = 'receive_donation';

    // Advertise/Support Activities
    case CREATE_ADVERTISE = 'create_advertise';
    case VIEW_ADVERTISE = 'view_advertise';
    case APPROVE_ADVERTISE = 'approve_advertise';
    case REJECT_ADVERTISE = 'reject_advertise';

    // Course Activities
    case ENROLL_COURSE = 'enroll_course';
    case COMPLETE_COURSE = 'complete_course';
    case JOIN_COURSE = 'join_course';

    // General Activities
    case CREATE = 'create';
    case UPDATE = 'update';
    case DELETE = 'delete';
    case JOIN = 'join';
    
    /**
     * Get the Thai display text for this activity type.
     */
    public function label(): string
    {
        return match($this) {
            self::CREATE_POST => 'สร้างโพสต์ใหม่',
            self::UPDATE_POST => 'อัปเดตโพสต์',
            self::DELETE_POST => 'ลบโพสต์',
            self::SHARE_POST => 'แชร์โพสต์',
            self::CREATE_COMMENT => 'แสดงความคิดเห็น',
            self::UPDATE_COMMENT => 'แก้ไขความคิดเห็น',
            self::DELETE_COMMENT => 'ลบความคิดเห็น',
            self::LIKE => 'ถูกใจ',
            self::DISLIKE => 'ไม่ถูกใจ',
            self::GIVE_DONATION => 'บริจาค',
            self::RECEIVE_DONATION => 'ได้รับบริจาค',
            self::CREATE_ADVERTISE => 'สร้างโฆษณา',
            self::VIEW_ADVERTISE => 'ดูโฆษณา',
            self::APPROVE_ADVERTISE => 'อนุมัติโฆษณา',
            self::REJECT_ADVERTISE => 'ปฏิเสธโฆษณา',
            self::ENROLL_COURSE => 'ลงทะเบียนเรียน',
            self::COMPLETE_COURSE => 'เรียนจบ',
            self::JOIN_COURSE => 'เข้าร่วมรายวิชา',
            self::CREATE => 'สร้าง',
            self::UPDATE => 'อัปเดต',
            self::DELETE => 'ลบ',
            self::JOIN => 'เข้าร่วม',
        };
    }

    /**
     * Get the short Thai display text for inline display.
     */
    public function shortLabel(): string
    {
        return match($this) {
            self::CREATE_POST => 'โพสต์',
            self::SHARE_POST => 'แชร์',
            self::CREATE_COMMENT => 'คอมเมนต์',
            self::LIKE => 'ถูกใจ',
            self::GIVE_DONATION => 'บริจาค',
            self::RECEIVE_DONATION => 'รับบริจาค',
            self::ENROLL_COURSE => 'ลงทะเบียน',
            self::COMPLETE_COURSE => 'เรียนจบ',
            default => $this->label(),
        };
    }

    /**
     * Get the Iconify icon name for this activity type.
     */
    public function icon(): string
    {
        return match($this) {
            self::CREATE_POST => 'fluent:add-circle-24-regular',
            self::UPDATE_POST => 'fluent:edit-24-regular',
            self::DELETE_POST => 'fluent:delete-24-regular',
            self::SHARE_POST => 'fluent:share-24-regular',
            self::CREATE_COMMENT, self::UPDATE_COMMENT, self::DELETE_COMMENT => 'fluent:comment-24-regular',
            self::LIKE => 'fluent:thumb-like-24-regular',
            self::DISLIKE => 'fluent:thumb-dislike-24-regular',
            self::GIVE_DONATION, self::RECEIVE_DONATION => 'fluent:heart-24-regular',
            self::CREATE_ADVERTISE, self::VIEW_ADVERTISE => 'fluent:megaphone-24-regular',
            self::APPROVE_ADVERTISE => 'fluent:checkmark-circle-24-regular',
            self::REJECT_ADVERTISE => 'fluent:dismiss-circle-24-regular',
            self::ENROLL_COURSE, self::JOIN_COURSE => 'fluent:book-add-24-regular',
            self::COMPLETE_COURSE => 'fluent:checkmark-circle-24-regular',
            default => 'fluent:flash-24-regular',
        };
    }

    /**
     * Get legacy value mappings for backward compatibility.
     * Maps old database values to new enum values.
     */
    public static function fromLegacy(string $legacyValue): ?self
    {
        $mapping = [
            'createpost' => self::CREATE_POST,
            'updatepost' => self::UPDATE_POST,
            'deletepost' => self::DELETE_POST,
            'sharepost' => self::SHARE_POST,
            'createcomment' => self::CREATE_COMMENT,
            'givedonation' => self::GIVE_DONATION,
            'recieveddonation' => self::RECEIVE_DONATION,  // Note: typo in original
            'receiveddonation' => self::RECEIVE_DONATION,
            'createadvertise' => self::CREATE_ADVERTISE,
            'viewadvertise' => self::VIEW_ADVERTISE,
            'approveadvertise' => self::APPROVE_ADVERTISE,
            'rejectadvertise' => self::REJECT_ADVERTISE,
        ];

        return $mapping[strtolower($legacyValue)] ?? null;
    }

    /**
     * Normalize any activity type string to snake_case format.
     * Handles both legacy values and already-normalized values.
     */
    public static function normalize(string $value): string
    {
        // First, try to find in legacy mapping
        $enum = self::fromLegacy($value);
        if ($enum !== null) {
            return $enum->value;
        }

        // Try to create from value directly (already in correct format)
        $enum = self::tryFrom($value);
        if ($enum !== null) {
            return $enum->value;
        }

        // Fallback: convert camelCase or concatenated string to snake_case
        // e.g., "createPost" or "CreatePost" -> "create_post"
        $snakeCase = preg_replace('/([a-z])([A-Z])/', '$1_$2', $value);
        $snakeCase = strtolower($snakeCase);
        
        // Handle concatenated lowercase like "createpost" -> "create_post"
        // Common patterns
        $patterns = [
            '/^(create|update|delete|share|view|approve|reject|give|receive)/' => '$1_',
        ];
        
        foreach ($patterns as $pattern => $replacement) {
            if (preg_match($pattern, $snakeCase) && strpos($snakeCase, '_') === false) {
                $snakeCase = preg_replace($pattern, $replacement, $snakeCase);
                break;
            }
        }

        return $snakeCase;
    }
}
