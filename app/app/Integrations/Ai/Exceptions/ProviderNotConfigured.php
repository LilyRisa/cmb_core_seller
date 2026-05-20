<?php

namespace CMBcoreSeller\Integrations\Ai\Exceptions;

use RuntimeException;

/**
 * Provider chưa được super-admin cấu hình hoặc đã `is_active=false`. Caller
 * (Messaging) phải trả `422 AI_PROVIDER_NOT_AVAILABLE` cho tenant + hiện banner
 * "Provider đã ngừng — chọn lại" ở `/settings/messaging`.
 */
class ProviderNotConfigured extends RuntimeException {}
