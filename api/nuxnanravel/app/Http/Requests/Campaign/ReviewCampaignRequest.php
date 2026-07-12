<?php

namespace App\Http\Requests\Campaign;

use App\Models\Advert;
use App\Services\Campaign\CampaignAuthorizationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        $campaign = $this->route('campaign');
        if (is_numeric($campaign) || is_string($campaign)) {
            $campaign = Advert::find($campaign);
        }

        return auth()->check() && $campaign instanceof Advert
            && app(CampaignAuthorizationService::class)->canReview(auth()->user(), $campaign);
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['approve', 'reject', 'pause'])],
            'rejection_reason' => ['required_if:action,reject', 'nullable', 'string', 'max:500'],
        ];
    }
}
