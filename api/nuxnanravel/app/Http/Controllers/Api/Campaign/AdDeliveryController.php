<?php

namespace App\Http\Controllers\Api\Campaign;

use App\Http\Controllers\Controller;
use App\Http\Requests\Campaign\CompleteDeliveryRequest;
use App\Http\Requests\Campaign\HeartbeatDeliveryRequest;
use App\Http\Requests\Campaign\StartDeliveryRequest;
use App\Models\Advert;
use App\Models\CampaignDeliveryEvent;
use App\Services\Campaign\AdDeliveryService;
use Illuminate\Http\JsonResponse;

class AdDeliveryController extends Controller
{
    public function start(Advert $advert, StartDeliveryRequest $request, AdDeliveryService $service): JsonResponse
    {
        $result = $service->startSession($advert, $request->user(), $request->string('session_id')->toString(), $request->input('device_fingerprint'), hash('sha256', $request->ip()), $request->userAgent());

        return response()->json(['token' => $result['token'], 'delivery_id' => $result['deliveryId'], 'required_duration' => $result['required_duration']]);
    }

    public function heartbeat(CampaignDeliveryEvent $delivery, HeartbeatDeliveryRequest $request, AdDeliveryService $service): JsonResponse
    {
        $service->heartbeat($delivery, $request->string('token')->toString(), (float) $request->input('visibility_ratio'));

        return response()->json(['ok' => true]);
    }

    public function complete(CampaignDeliveryEvent $delivery, CompleteDeliveryRequest $request, AdDeliveryService $service): JsonResponse
    {
        $result = $service->complete($delivery, $request->string('token')->toString());

        return response()->json(['valid' => $result['valid'], 'reason' => $result['reason']]);
    }
}
