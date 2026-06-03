<?php

namespace CMBcoreSeller\Modules\Tenancy\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Đăng ký Expo Push token cho thiết bị mobile — SPEC 0029.
 */
class RegisterMobileDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'expo_push_token' => ['required', 'string', 'max:255'],
            'platform' => ['required', 'string', 'in:ios,android'],
        ];
    }
}
