<?php

namespace CMBcoreSeller\Modules\Marketing\Http\Requests;

use CMBcoreSeller\Modules\Marketing\Services\CampaignInsightAnalysisService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates per-campaign AI analysis options: window (days), chosen metrics,
 * and whether to include post engagement.
 */
class CampaignAiInsightRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Gate checked in controller (marketing.view).
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'days' => ['nullable', 'integer', 'min:1', 'max:90'],
            'metrics' => ['nullable', 'array'],
            'metrics.*' => ['string', Rule::in(array_keys(CampaignInsightAnalysisService::METRICS))],
            'include_engagement' => ['nullable', 'boolean'],
        ];
    }

    /** @return array{days?:int, metrics?:list<string>, include_engagement?:bool} */
    public function params(): array
    {
        /** @var array{days?:int, metrics?:list<string>, include_engagement?:bool} $out */
        $out = $this->only(['days', 'metrics', 'include_engagement']);

        return $out;
    }
}
