// Curated action-code list for AdminAuditLogsPage's action-filter autocomplete.
// Compiled 2026-07-21 by grepping `AuditLog::record(...)` and `'action' => '...'` audit-log write
// call sites across app/app/Modules/**/*.php (docs/superpowers/plans/
// 2026-07-21-admin-redesign-phase2d-remaining-pages.md, Task 2, Step 1 — includes the exact grep
// commands and the YAGNI reasoning for why this stays a static frontend list instead of a new
// backend distinct-actions endpoint).
//
// This is a convenience list, not a hard constraint: the backend filter
// (AdminAuditLogController::index) already accepts free text and turns `*` into a SQL `LIKE`
// wildcard, so typing any action code — listed here or not — still filters correctly. Namespace
// wildcards below cover modules whose full leaf-action list isn't individually enumerated
// (messaging.*, tenant.*, support.*, visual_search.*, marketing.*); admin.* — this page's primary
// use case per its own subtitle — is enumerated in full.
export const AUDIT_ACTION_CODES: string[] = [
    // Namespace shortcuts (wildcard `*` -> LIKE on backend)
    'admin.*',
    'tenant.*',
    'messaging.*',
    'support.*',
    'visual_search.*',
    'marketing.*',

    // admin.* — đầy đủ, đây là nhóm hành động chính trang này phục vụ
    'admin.auth.login',
    'admin.auth.logout',
    'admin.auth.change_password',
    'admin.admin_user.create',
    'admin.admin_user.update',
    'admin.admin_user.reset_password',
    'admin.admin_user.suspend',
    'admin.admin_user.reactivate',
    'admin.user.update',
    'admin.user.reset_password',
    'admin.user.suspend',
    'admin.user.reactivate',
    'admin.tenant.suspend',
    'admin.tenant.reactivate',
    'admin.subscription.change',
    'admin.trial.extend',
    'admin.feature_override.set',
    'admin.ai_credit.adjust',
    'admin.channel_account.delete',
    'admin.invoice.create_manual',
    'admin.invoice.mark_paid',
    'admin.invoice.mark_paid.noop',
    'admin.payment.refund',
    'admin.voucher.create',
    'admin.voucher.update',
    'admin.voucher.disable',
    'admin.voucher.grant',
    'admin.plan.create',
    'admin.plan.update',
    'admin.broadcast.send',
    'admin.pro_trial.settings',
    'admin.setting.update',
    'admin.setting.reveal',
];
