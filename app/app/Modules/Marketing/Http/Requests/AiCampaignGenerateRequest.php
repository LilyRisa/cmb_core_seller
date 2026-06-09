<?php

namespace CMBcoreSeller\Modules\Marketing\Http\Requests;

use CMBcoreSeller\Integrations\Ads\Facebook\FacebookAdsCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AiCampaignGenerateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // controller gates qua Gate::authorize('marketing.ads.create')
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'page_id' => ['required', 'string', 'max:64'],
            'page_post_id' => ['required', 'string', 'max:128'],
            'objective' => ['required', 'string', Rule::in(FacebookAdsCatalog::objectives())],
            'mode' => ['required', 'string', Rule::in(['test', 'scale'])],
            'placement_mode' => ['required', 'string', Rule::in(['advantage_plus', 'manual'])],
            'prompt' => ['nullable', 'string', 'max:2000'],
            'caption' => ['nullable', 'string', 'max:5000'],
            'likes' => ['nullable', 'integer', 'min:0'],
            'comments' => ['nullable', 'integer', 'min:0'],
            'shares' => ['nullable', 'integer', 'min:0'],
            'link_url' => ['nullable', 'url', 'max:2048'],
            'landing_url' => ['nullable', 'url', 'max:2048'],
            'cta_type' => ['nullable', 'string', 'max:64'],
            'pixel_id' => ['nullable', 'required_if:objective,conversions', 'string', 'max:64'],
            'conversion_event' => ['nullable', 'required_if:objective,conversions', 'string', 'max:64'],
            'start_time' => ['nullable', 'date'],
        ];
    }
}
