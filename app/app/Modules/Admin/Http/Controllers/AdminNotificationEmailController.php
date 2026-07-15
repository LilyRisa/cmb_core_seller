<?php

namespace CMBcoreSeller\Modules\Admin\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Admin\Models\AdminNotificationRecipient;
use CMBcoreSeller\Modules\Admin\Models\AdminNotificationSubscription;
use CMBcoreSeller\Modules\Admin\Notifications\AdminAlertNotification;
use CMBcoreSeller\Modules\Admin\Notifications\NotificationTypeCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

/**
 * CRUD email nhận thông báo cấp nền tảng + gửi test (SPEC 2026-07-15). Guard `admin_web`.
 */
class AdminNotificationEmailController extends Controller
{
    public function index(): JsonResponse
    {
        $rows = AdminNotificationRecipient::query()->with('subscriptions')->orderBy('id')->get();

        return response()->json(['data' => $rows->map($this->row(...))->all()]);
    }

    public function types(): JsonResponse
    {
        $types = collect(NotificationTypeCatalog::all())
            ->map(fn ($label, $code) => ['code' => $code, 'label' => $label])
            ->values();

        return response()->json(['data' => $types->all()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateData($request);

        $recipient = DB::transaction(function () use ($data) {
            $recipient = AdminNotificationRecipient::create([
                'email' => $data['email'],
                'label' => $data['label'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);
            $this->syncSubscriptions($recipient, $data['notification_types']);

            return $recipient;
        });

        return response()->json(['data' => $this->row($recipient->load('subscriptions'))], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $recipient = AdminNotificationRecipient::query()->findOrFail((int) $id);
        $data = $this->validateData($request, partial: true, ignoreId: $recipient->id);

        DB::transaction(function () use ($recipient, $data) {
            if (array_key_exists('email', $data)) {
                $recipient->email = $data['email'];
            }
            if (array_key_exists('label', $data)) {
                $recipient->label = $data['label'];
            }
            if (array_key_exists('is_active', $data)) {
                $recipient->is_active = $data['is_active'];
            }
            $recipient->save();

            if (array_key_exists('notification_types', $data)) {
                $this->syncSubscriptions($recipient, $data['notification_types']);
            }
        });

        return response()->json(['data' => $this->row($recipient->fresh('subscriptions'))]);
    }

    public function destroy(string $id): JsonResponse
    {
        AdminNotificationRecipient::query()->findOrFail((int) $id)->delete();

        return response()->json(['data' => ['deleted' => true]]);
    }

    public function test(string $id): JsonResponse
    {
        $recipient = AdminNotificationRecipient::query()->findOrFail((int) $id);

        Notification::route('mail', $recipient->email)->notify(new AdminAlertNotification('test', []));

        return response()->json(['data' => ['sent' => true]]);
    }

    /** @return array<string,mixed> */
    private function validateData(Request $request, bool $partial = false, ?int $ignoreId = null): array
    {
        $req = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'email' => [$req, 'email', 'max:255', Rule::unique('admin_notification_recipients', 'email')->ignore($ignoreId)],
            'label' => ['sometimes', 'nullable', 'string', 'max:120'],
            'is_active' => ['sometimes', 'boolean'],
            'notification_types' => [$req, 'array'],
            'notification_types.*' => ['string', function ($attribute, $value, $fail) {
                if (! NotificationTypeCatalog::isValid($value)) {
                    $fail("Loại thông báo \"{$value}\" không hợp lệ.");
                }
            }],
        ]);
    }

    /** @param list<string> $types */
    private function syncSubscriptions(AdminNotificationRecipient $recipient, array $types): void
    {
        $recipient->subscriptions()->delete();
        foreach (array_unique($types) as $type) {
            AdminNotificationSubscription::create([
                'admin_notification_recipient_id' => $recipient->id,
                'notification_type' => $type,
                'created_at' => now(),
            ]);
        }
    }

    /** @return array<string,mixed> */
    private function row(AdminNotificationRecipient $recipient): array
    {
        return [
            'id' => $recipient->id,
            'email' => $recipient->email,
            'label' => $recipient->label,
            'is_active' => $recipient->is_active,
            'notification_types' => $recipient->subscriptions->pluck('notification_type')->values()->all(),
        ];
    }
}
