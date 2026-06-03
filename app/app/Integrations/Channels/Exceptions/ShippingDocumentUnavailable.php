<?php

namespace CMBcoreSeller\Integrations\Channels\Exceptions;

use RuntimeException;

/**
 * Thrown by ChannelConnector::getShippingDocument when the marketplace will NOT return a
 * shipping label/AWB for this order. Distinguishes:
 *   - TERMINAL (`terminal=true`): no label will EVER exist — caller MUST stop retrying. E.g. Lazada
 *     DBS/SOF orders (Delivery By Seller / Seller's Own Fleet — seller dùng ĐVVC ngoài Lazada nên sàn
 *     không cấp AWB; `/order/document/get` trả code 50008 "not support operation for sof order").
 *   - TRANSIENT (`terminal=false`): label chưa render xong / lỗi tạm — caller có thể retry theo backoff.
 *
 * `reasonCode` = mã ngắn cho UI/log (vd 'lazada_dbs_sof', 'shopee_doc_create_failed').
 */
class ShippingDocumentUnavailable extends RuntimeException
{
    public function __construct(string $message, public readonly bool $terminal = false, public readonly string $reasonCode = '')
    {
        parent::__construct($message);
    }

    public static function terminal(string $reasonCode, string $message): self
    {
        return new self($message, true, $reasonCode);
    }

    public static function transient(string $reasonCode, string $message): self
    {
        return new self($message, false, $reasonCode);
    }
}
