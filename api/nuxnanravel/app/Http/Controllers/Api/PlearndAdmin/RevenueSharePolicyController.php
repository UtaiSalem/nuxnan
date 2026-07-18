<?php

namespace App\Http\Controllers\Api\PlearndAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RevenueSharePolicy\StoreRevenueSharePolicyRequest;
use App\Http\Requests\RevenueSharePolicy\UpdateRevenueSharePolicyRequest;
use App\Http\Resources\RevenueSharePolicy\RevenueSharePolicyResource;
use App\Models\CampaignDeliveryEvent;
use App\Models\RevenueSharePolicy;
use Illuminate\Http\Request;

class RevenueSharePolicyController extends Controller
{
    public function index(Request $r)
    {
        $q = RevenueSharePolicy::with('creator')->latest();
        if ($r->filled('scope_type')) {
            $q->where('scope_type', $r->scope_type);
        } if ($r->filled('scope_id')) {
            $q->where('scope_id', $r->scope_id);
        } if ($r->boolean('active_only')) {
            $q->active();
        } if ($r->filled('search')) {
            $q->where('notes', 'like', '%'.$r->search.'%');
        }

return RevenueSharePolicyResource::collection($q->paginate($r->integer('per_page', 15)));
    }

    public function store(StoreRevenueSharePolicyRequest $r)
    {
        return new RevenueSharePolicyResource(RevenueSharePolicy::create($r->validated() + ['created_by' => auth()->id(), 'version' => 1]));
    }

    public function update(RevenueSharePolicy $policy, UpdateRevenueSharePolicyRequest $r)
    {
        $policy->update($r->validated() + ['version' => $policy->version + 1]);

        return new RevenueSharePolicyResource($policy->fresh('creator'));
    }

    public function usage(RevenueSharePolicy $policy)
    {
        $count = CampaignDeliveryEvent::where('metadata', 'like', '%"policy_id":'.$policy->id.'%')->count();

        return response()->json(['policy_id' => $policy->id, 'usage_count' => $count]);
    }
}
