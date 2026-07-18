<?php

namespace App\Http\Controllers\Api\PlearndAdmin;

use App\Http\Controllers\Controller;
use App\Http\Resources\RiskEvent\RiskEventResource;
use App\Models\RiskEvent;
use Illuminate\Http\Request;

class RiskEventController extends Controller
{
    public function index(Request $request)
    {
        $q = RiskEvent::with('subject')->latest('created_at');
        foreach (['rule_name', 'severity', 'status', 'subject_type'] as $f) {
            if ($request->filled($f)) {
                $q->where($f, $request->$f);
            }
        } if ($request->filled('search')) {
            $q->where('evidence', 'like', '%'.$request->search.'%');
        }

return RiskEventResource::collection($q->paginate($request->integer('per_page', 20)));
    }

    public function show(RiskEvent $risk)
    {
        return new RiskEventResource($risk->load('subject', 'resolvedBy'));
    }

    public function acknowledge(RiskEvent $risk)
    {
        $risk->update(['status' => RiskEvent::STATUS_ACKNOWLEDGED]);

        return new RiskEventResource($risk->fresh('subject'));
    }

    public function resolve(RiskEvent $risk, Request $request)
    {
        return $this->finish($risk, $request, RiskEvent::STATUS_RESOLVED);
    }

    public function dismiss(RiskEvent $risk, Request $request)
    {
        return $this->finish($risk, $request, RiskEvent::STATUS_DISMISSED);
    }

    private function finish(RiskEvent $risk, Request $request, string $status)
    {
        $data = $request->validate(['resolution_note' => 'required|string|max:2000']);
        $risk->update(['status' => $status, 'resolution_note' => $data['resolution_note'], 'resolved_by' => $request->user()->id, 'resolved_at' => now()]);

        return new RiskEventResource($risk->fresh('subject', 'resolvedBy'));
    }
}
