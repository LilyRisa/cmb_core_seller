<?php

namespace CMBcoreSeller\Modules\Support\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * CSKH (admin) gửi tin vào hội thoại (`POST /admin/support-conversations/{id}/messages`).
 * Body HOẶC ít nhất 1 đính kèm. MIME/size kiểm ở `SupportMediaService`.
 */
class StoreAdminSupportMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route đã qua auth:admin_web
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        $max = (int) config('support.attachments.max_files', 5);

        return [
            'body' => ['nullable', 'required_without:files', 'string', 'max:8000'],
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
