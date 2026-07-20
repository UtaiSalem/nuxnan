<?php

namespace App\Services\Campaign;

use App\Models\Advert;
use Illuminate\Database\Eloquent\Builder;

class CampaignDeliveryService
{
    public function query(string $scope = 'public', ?int $academyId = null, ?int $courseId = null): Builder
    {
        $now = now();

        $query = Advert::query()
            ->with(['advertiser', 'academy', 'course'])
            ->where('campaign_type', 'advertisement')
            ->where('review_status', 'approved')
            ->where('remaining_views', '>', 0)
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('active_from')->orWhere('active_from', '<=', $now);
            })
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('active_until')->orWhere('active_until', '>=', $now);
            });

        if ($scope === 'course' && $courseId) {
            return $query->where(function (Builder $q) use ($courseId, $academyId) {
                $q->where(function (Builder $direct) use ($courseId) {
                    $direct->where('scope_type', 'course')->where('course_id', $courseId);
                })->orWhere(function (Builder $inherited) use ($academyId) {
                    $inherited->where('scope_type', 'academy')->where('academy_id', $academyId);
                });
            });
        }

        if ($scope === 'academy' && $academyId) {
            return $query->where(function (Builder $q) use ($academyId) {
                $q->where(function (Builder $academy) use ($academyId) {
                    $academy->where('scope_type', 'academy')->where('academy_id', $academyId);
                })->orWhere(function (Builder $course) use ($academyId) {
                    $course->where('scope_type', 'course')->where('academy_id', $academyId)->where('inherit_to_academy', true);
                });
            });
        }

        return $query->where('scope_type', 'public');
    }
}
