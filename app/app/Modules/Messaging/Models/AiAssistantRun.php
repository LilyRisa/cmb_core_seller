<?php

namespace CMBcoreSeller\Modules\Messaging\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * Audit + cost tracking 1 lần gọi LLM. Super-admin `/admin/messaging/ai-usage`
 * aggregate theo tenant + month để charge.
 */
class AiAssistantRun extends Model
{
    use BelongsToTenant;

    public const MODE_SUGGEST = 'suggest';

    public const MODE_AUTO = 'auto';

    public const MODE_INTENT = 'intent';

    public const MODE_RAG = 'rag';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_ERROR = 'error';

    public const STATUS_TIMEOUT = 'timeout';

    public const STATUS_BLOCKED_BY_GUARDRAIL = 'blocked_by_guardrail';

    protected $fillable = [
        'tenant_id', 'conversation_id', 'message_id',
        'provider_code', 'model', 'mode',
        'prompt_tokens', 'completion_tokens', 'cost_micro_vnd', 'duration_ms',
        'status', 'error', 'meta', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'prompt_tokens' => 'integer',
            'completion_tokens' => 'integer',
            'cost_micro_vnd' => 'integer',
            'duration_ms' => 'integer',
            'meta' => 'array',
        ];
    }
}
