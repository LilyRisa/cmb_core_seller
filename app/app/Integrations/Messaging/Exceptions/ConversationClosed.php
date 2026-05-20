<?php

namespace CMBcoreSeller\Integrations\Messaging\Exceptions;

use RuntimeException;

/**
 * Sàn từ chối gửi tin vì conversation đã bị đóng (buyer block, shop deauthorized,
 * Shopee đóng chat sau X ngày inactive). `SendMessage` job mark
 * `messages.delivery_status = failed` với `failure_code = 'conversation_closed'`.
 */
class ConversationClosed extends RuntimeException {}
