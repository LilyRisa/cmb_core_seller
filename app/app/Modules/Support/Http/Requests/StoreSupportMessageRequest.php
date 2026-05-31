<?php

namespace CMBcoreSeller\Modules\Support\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Gửi tin CSKH phía user (`POST /support/messages`). Body HOẶC ít nhất 1 đính kèm.
 * MIME/size kiểm ở `SupportMediaService` (→ 422 ATTACHMENT_INVALID) cho thông điệp rõ.
 */
class StoreSupportMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route đã qua auth:sanctum + verified + tenant
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        $max = (int) config('support.attachments.max_files', 5);

        return [
            'body' => ['nullable', 'required_without:files', 'string', 'max:4000'],
            'files' => ['nullable', 'array', "max:{$max}"],
            'files.*' => ['file'],
        ];
    }

    /** @return array<string,string> */
    public function messages(): array
    {
        $max = (int) config('support.attachments.max_files', 5);

        return [
            'body.required_without' => 'Vui lòng nhập nội dung hoặc đính kèm tệp.',
            'files.max' => "Tối đa {$max} tệp mỗi tin nhắn.",
        ];
    }
}
