<?php

namespace CMBcoreSeller\Modules\Messaging\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\MessagingTag;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/** CRUD thẻ hội thoại (tên + màu). Gắn thẻ vào hội thoại qua PATCH conversations/{id} (tags=[ids]). */
class TagController extends Controller
{
    public function index(): JsonResponse
    {
        Gate::authorize('messaging.view');
        $tags = MessagingTag::query()->orderBy('name')->get()
            ->map(fn (MessagingTag $t) => ['id' => $t->id, 'name' => $t->name, 'color' => $t->color]);

        return response()->json(['data' => $tags]);
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('messaging.reply');
        $tenantId = app(CurrentTenant::class)->id();
        $data = $request->validate([
            'name' => [
                'required', 'string', 'min:1', 'max:40',
                Rule::unique('messaging_tags')->where('tenant_id', $tenantId),
            ],
            'color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);
        $tag = MessagingTag::query()->create($data);

        return response()->json(['data' => ['id' => $tag->id, 'name' => $tag->name, 'color' => $tag->color]], 201);
    }

    public function update(int $id, Request $request): JsonResponse
    {
        Gate::authorize('messaging.reply');
        $tenantId = app(CurrentTenant::class)->id();
        $data = $request->validate([
            'name' => [
                'sometimes', 'string', 'min:1', 'max:40',
                Rule::unique('messaging_tags')->where('tenant_id', $tenantId)->ignore($id),
            ],
            'color' => ['sometimes', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);
        $tag = MessagingTag::query()->findOrFail($id);
        $tag->update($data);

        return response()->json(['data' => ['id' => $tag->id, 'name' => $tag->name, 'color' => $tag->color]]);
    }

    public function destroy(int $id): JsonResponse
    {
        Gate::authorize('messaging.reply');
        $tag = MessagingTag::query()->findOrFail($id);
        $tagId = $tag->id;
        $tenantId = $tag->tenant_id;
        $tag->delete();

        // Gỡ id thẻ khỏi mọi conversations.tags của tenant (best-effort, không đụng logic khác).
        Conversation::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->whereJsonContains('tags', $tagId)
            ->get()
            ->each(function (Conversation $c) use ($tagId) {
                $c->tags = array_values(array_filter((array) ($c->tags ?? []), fn ($t) => (int) $t !== (int) $tagId));
                $c->save();
            });

        return response()->json(['data' => ['ok' => true]]);
    }
}
