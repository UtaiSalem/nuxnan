<?php

namespace App\Services\Profile;

use App\Models\Friendship;
use App\Models\User;
use App\Models\UserProfile;

/**
 * สิทธิ์ของผู้ชมต่อโปรไฟล์หนึ่ง — ตัดสินที่นี่ที่เดียว ใช้ทั้ง controller และ resource
 *
 * ความเป็นเพื่อนอ่านจากตาราง friendships (multicaret/laravel-acquaintances) เท่านั้น
 * ห้ามใช้ App\Models\Friend (ตาราง friends ว่างเปล่า ไม่มีโค้ดไหนเขียน)
 * ห้ามเรียก canBefriend() เพื่อเช็คสถานะ (มันแอบ unblock เป็น side effect)
 */
class ProfileAccess
{
    private ?bool $isFriend = null;

    private ?string $friendshipStatus = null;

    private ?int $mutualFriendsCount = null;

    public function __construct(
        public readonly ?User $viewer,
        public readonly User $target,
        public readonly ?UserProfile $profile = null,
    ) {}

    public static function for(?User $viewer, User $target, ?UserProfile $profile = null): self
    {
        return new self($viewer, $target, $profile ?? $target->profile);
    }

    public function isOwner(): bool
    {
        return $this->viewer !== null && (int) $this->viewer->id === (int) $this->target->id;
    }

    public function isFriend(): bool
    {
        return $this->isFriend ??= ($this->viewer !== null
            && ! $this->isOwner()
            && $this->viewer->isFriendWith($this->target));
    }

    /**
     * self | friends | pending_sent | pending_received | none | guest
     * ดูจากแถว friendships ล่าสุดของคู่นี้ (ทั้งสองทิศทาง) · denied / blocked = none
     */
    public function friendshipStatus(): string
    {
        if ($this->friendshipStatus !== null) {
            return $this->friendshipStatus;
        }

        if ($this->viewer === null) {
            return $this->friendshipStatus = 'guest';
        }

        if ($this->isOwner()) {
            return $this->friendshipStatus = 'self';
        }

        $type = $this->target->getMorphClass();
        $viewerId = (int) $this->viewer->id;
        $targetId = (int) $this->target->id;

        $latest = Friendship::query()
            ->where(function ($q) use ($type, $viewerId, $targetId) {
                $q->where(function ($q) use ($type, $viewerId, $targetId) {
                    $q->where('sender_type', $type)->where('sender_id', $viewerId)
                        ->where('recipient_type', $type)->where('recipient_id', $targetId);
                })->orWhere(function ($q) use ($type, $viewerId, $targetId) {
                    $q->where('sender_type', $type)->where('sender_id', $targetId)
                        ->where('recipient_type', $type)->where('recipient_id', $viewerId);
                });
            })
            ->orderByDesc('id')
            ->first();

        return $this->friendshipStatus = match ($latest?->status) {
            'accepted' => 'friends',
            'pending' => (int) $latest->sender_id === $viewerId ? 'pending_sent' : 'pending_received',
            default => 'none',
        };
    }

    /** public | friends | private */
    public function privacyLevel(): string
    {
        return UserProfile::normalizePrivacy($this->profile?->getAttributes()['privacy_settings'] ?? null);
    }

    /**
     * โปรไฟล์เต็ม (หมวดเกี่ยวกับ รายชื่อเพื่อน แท็บอื่น ๆ):
     * เจ้าของเสมอ · public = ทุกคน · friends = เฉพาะเพื่อน · private = เจ้าของเท่านั้น
     */
    public function canSeeFullProfile(): bool
    {
        if ($this->isOwner()) {
            return true;
        }

        return match ($this->privacyLevel()) {
            'public' => true,
            'friends' => $this->isFriend(),
            default => false,
        };
    }

    public function canSeeAbout(): bool
    {
        return $this->canSeeFullProfile();
    }

    public function canSeeFriendsList(): bool
    {
        return $this->canSeeFullProfile();
    }

    /**
     * ระดับ privacy ของโพสต์ที่ผู้ชมเห็นได้: เจ้าของ [1,2,3] · เพื่อน [2,3] · คนอื่น/guest [3]
     *
     * @return array<int, int>
     */
    public function postPrivacyLevels(): array
    {
        if ($this->isOwner()) {
            return [1, 2, 3];
        }

        return $this->isFriend() ? [2, 3] : [3];
    }

    public function canSendFriendRequest(): bool
    {
        if ($this->viewer === null || $this->isOwner()) {
            return false;
        }

        if ($this->profile && $this->profile->allow_friend_requests === false) {
            return false;
        }

        return $this->friendshipStatus() === 'none';
    }

    public function mutualFriendsCount(): int
    {
        if ($this->viewer === null || $this->isOwner()) {
            return 0;
        }

        return $this->mutualFriendsCount ??= (int) $this->viewer->getMutualFriendsCount($this->target);
    }
}
