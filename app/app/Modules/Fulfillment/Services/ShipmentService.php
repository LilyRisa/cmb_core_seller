<?php

namespace CMBcoreSeller\Modules\Fulfillment\Services;

use CMBcoreSeller\Integrations\Carriers\CarrierRegistry;
use CMBcoreSeller\Integrations\Carriers\Support\AbstractCarrierConnector;
use CMBcoreSeller\Integrations\Carriers\Support\CarrierUnsupportedException;
use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Fulfillment\Events\ShipmentCreated;
use CMBcoreSeller\Modules\Fulfillment\Models\CarrierAccount;
use CMBcoreSeller\Modules\Fulfillment\Models\PrintJob;
use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;
use CMBcoreSeller\Modules\Fulfillment\Models\ShipmentEvent;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Inventory\Services\InventoryLedgerService;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Orders\Models\OrderItem;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus as S;
use CMBcoreSeller\Support\MediaUploader;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Creates & manages parcels (shipments) for orders via the CarrierConnector layer,
 * stores the label PDF on the media disk, and keeps the order status / stock in sync
 * (via OrderStatusSync → OrderUpserted). See SPEC 0006 §3-4, docs/03-domain/fulfillment-and-printing.md.
 */
class ShipmentService
{
    public function __construct(
        private readonly CarrierRegistry $carriers,
        private readonly OrderStatusSync $orderStatus,
        private readonly MediaUploader $media,
        private readonly InventoryLedgerService $ledger,
        private readonly ChannelRegistry $channels,
        private readonly PrintService $print,
    ) {}

    /**
     * Phiếu giao hàng tự tạo (chỉ cho **đơn manual**) — render PDF qua Gotenberg, lưu R2, gắn vào
     * `shipments.label_url/label_path` ⇒ lúc in chỉ ghép PDF, không render lại. SPEC 0013.
     *
     * **RULE**: KHÔNG bao giờ tạo phiếu tạm cho đơn sàn (`channel_account_id != null`). Lý do:
     *  - Phiếu tự tạo không có AWB của sàn ⇒ ĐVVC từ chối nhận hàng / sàn không nhận trách nhiệm.
     *  - Người bán in ra rồi gửi đi sẽ bị sàn phạt khi tracking không khớp.
     * Đơn sàn KHÔNG có tem thật ⇒ surface lỗi để người dùng "Nhận phiếu giao hàng" thử lại.
     */
    private function queueDeliverySlip(Order $order, ?int $userId): void
    {
        if ($order->channel_account_id) {
            Log::info('shipment.queue_delivery_slip_skipped_channel_order', ['order' => $order->getKey()]);
            return;
        }
        try {
            $this->print->createJob((int) $order->tenant_id, PrintJob::TYPE_DELIVERY, [$order->getKey()], [], $userId);
        } catch (\Throwable $e) {
            Log::warning('shipment.queue_delivery_slip_failed', ['order' => $order->getKey(), 'error' => $e->getMessage()]);
        }
    }

    /**
     * "Nhận phiếu giao hàng": idempotent retry kéo tem/AWB thật của sàn (đơn sàn) hoặc queue phiếu tự tạo
     * (đơn manual). KHÔNG bao giờ tạo phiếu tự tạo cho đơn sàn (rule cố định — phiếu tạm không thay được
     * AWB của sàn).
     *
     * Trả:
     *  - `no_shipment` — đơn chưa "Chuẩn bị hàng" (chưa có vận đơn).
     *  - `has_label`   — đã có phiếu, sẵn sàng in.
     *  - `need_slip`   — đơn **manual** thiếu phiếu, controller render phiếu tự tạo (1 lượt cho cả batch).
     *  - `pending_marketplace` — đơn **sàn** chưa có tem thật từ sàn (vd Lazada chưa cấp tracking, hoặc
     *    sàn rate-limit `SellerCallLimit`); controller add vào `errors[]` để user retry sau, KHÔNG temp slip.
     * SPEC 0013.
     */
    public function refetchSlip(Order $order, ?int $userId = null): string
    {
        $shipment = Shipment::query()->where('tenant_id', $order->tenant_id)->where('order_id', $order->getKey())->open()->latest('id')->first();
        if (! $shipment) {
            return 'no_shipment';
        }
        // (b) đơn sàn còn vướng cờ liên quan phiếu / mã vận đơn của sàn ⇒ thử lấy lại từ sàn (không cần thao tác app sàn)
        $reason = (string) $order->issue_reason;
        $issueAboutChannel = $order->has_issue && (
            str_contains($reason, 'phiếu giao hàng')
            || str_contains($reason, 'mã vận đơn')
            || str_contains($reason, 'sắp xếp vận chuyển')
            || str_contains($reason, 'in đơn')
        );
        $justArranged = false;
        if ($order->channel_account_id && $issueAboutChannel) {
            try {
                $arr = $this->arrangeOnChannel($order);
                $justArranged = is_array($arr) && ! empty($arr['tracking_no']);
                if (is_array($arr)) {
                    $patch = [];
                    if (! empty($arr['tracking_no']) && blank($shipment->tracking_no)) {
                        $shipment->forceFill(['tracking_no' => $arr['tracking_no']])->save();
                    }
                    if (! empty($arr['package_id']) && blank($shipment->package_no)) {
                        $shipment->forceFill(['package_no' => $arr['package_id']])->save();
                    }
                    if (! empty($arr['carrier']) && blank($shipment->carrier)) {
                        $shipment->forceFill(['carrier' => $arr['carrier']])->save();
                    }
                    if (! empty($arr['tracking_no'])) {
                        $patch = ['has_issue' => false, 'issue_reason' => null];
                    }
                    if ($patch !== []) {
                        $order->forceFill($patch)->save();
                    }
                }
            } catch (\Throwable $e) {
                Log::info('shipment.refetch_slip_arrange_failed', ['order' => $order->getKey(), 'error' => $e->getMessage()]);
            }
            $shipment->refresh();
        }
        // (a) kéo tem thật của sàn nếu có thể — retry chỉ khi vừa arrange xong (propagation Lazada)
        if (blank($shipment->label_path) && $shipment->tracking_no) {
            $this->fetchAndStoreChannelLabel($order, $shipment, justArranged: $justArranged);
            $shipment->refresh();
        }

        if (! blank($shipment->label_path)) {
            return 'has_label';
        }

        // Đơn sàn KHÔNG có tem ⇒ pending (không tự tạo phiếu tạm). Đơn manual ⇒ controller tự tạo.
        return $order->channel_account_id ? 'pending_marketplace' : 'need_slip';
    }

    /**
     * Đơn sàn — gọi sàn "arrange shipment" ("luồng A"): tự đẩy trạng thái "sắp xếp vận chuyển / đã in đơn"
     * lên sàn & lấy AWB. Trả `['tracking_no'=>?,'carrier'=>?,'raw_status'=>?,'package_id'=>?]` nếu connector
     * hỗ trợ `shipping.arrange`; trả `null` nếu chưa hỗ trợ; gọi sàn lỗi ⇒ ném {@see RuntimeException}. SPEC 0014.
     *
     * @return array{tracking_no:?string,carrier:?string,raw_status:?string,package_id:?string}|null
     */
    private function arrangeOnChannel(Order $order): ?array
    {
        if (! $order->channel_account_id) {
            return null;
        }
        $account = ChannelAccount::query()->find($order->channel_account_id);
        if (! $account || ! $this->channels->has($account->provider) || ! $this->channels->for($account->provider)->supports('shipping.arrange')) {
            return null;
        }
        try {
            $r = $this->channels->for($account->provider)->arrangeShipment($account->authContext(), (string) $order->external_order_id, ['packages' => (array) ($order->packages ?? [])]);
        } catch (\Throwable $e) {
            Log::warning('shipment.arrange_on_channel_failed', ['order' => $order->getKey(), 'provider' => $account->provider, 'error' => $e->getMessage()]);
            // Đơn sàn: KHÔNG tạo phiếu giao hàng tạm thay tem thật của sàn (rule cố định — phiếu tạm không
            // dùng được khi bàn giao ĐVVC của sàn). Chỉ surface lỗi để người dùng bấm "Nhận phiếu giao hàng"
            // thử lại sau (vd Lazada `SellerCallLimit` thường ban 1–10s, retry tự khắc qua).
            throw new RuntimeException('Chưa lấy được phiếu giao hàng từ sàn lần này — bấm "Nhận phiếu giao hàng" để thử lại. Không cần thao tác gì trên app của sàn.');
        }
        if (! empty($r['raw_status'])) {
            $order->forceFill(['raw_status' => (string) $r['raw_status']])->save();
        }

        return [
            'tracking_no' => ! empty($r['tracking_no']) ? (string) $r['tracking_no'] : null,
            'carrier' => ! empty($r['carrier']) ? (string) $r['carrier'] : null,
            'raw_status' => ! empty($r['raw_status']) ? (string) $r['raw_status'] : null,
            'package_id' => ! empty($r['package_id']) ? (string) $r['package_id'] : null,
        ];
    }

    /**
     * Best-effort: kéo tem/AWB **thật của sàn** (PDF) về kho media & gắn vào vận đơn — KHÔNG tự vẽ tem. SPEC 0006 §9.1 / 0014.
     * `$justArranged=true` ⇒ vừa gọi `/order/rts` (Lazada) hoặc `/packages/{id}/ship` (TikTok) xong ⇒ tem có
     * thể chưa kịp publish ⇒ retry với backoff. User retry thủ công ("Nhận phiếu giao hàng") thì $justArranged=false.
     * `$skipAsyncRetry=true` ⇒ KHÔNG enqueue async job khi exhaust (dùng khi đã chạy từ job — Laravel queue tự
     * retry backoff). Mặc định false ⇒ sync exhaust thì queue job retry sau 15s/30s/60s/120s/300s.
     */
    private function fetchAndStoreChannelLabel(Order $order, Shipment $shipment, bool $justArranged = false, bool $skipAsyncRetry = false): void
    {
        $account = $order->channel_account_id ? ChannelAccount::query()->find($order->channel_account_id) : null;
        if (! $account || ! $this->channels->has($account->provider) || ! $this->channels->for($account->provider)->supports('shipping.document')) {
            return;
        }
        // TikTok yêu cầu `externalPackageId`; Lazada `/order/document/get` yêu cầu `order_item_ids` —
        // **PHẢI** là trường `items[].order_item_id` trong raw Lazada (KHÁC `order_id`). Vd raw có
        // `order_id=525106346980318` và `items[0].order_item_id=525106347080318` ⇒ truyền 525106347080318.
        // Thứ tự ưu tiên (cho đúng items của shipment hiện tại — đỡ phải refetch sàn):
        //   1. shipments.raw.external_item_ids (đã lưu lúc arrangeShipment chạy /order/pack — chính xác nhất)
        //   2. order_items.external_item_id (toàn bộ items của đơn — fallback cho shipment legacy chưa lưu raw)
        //   3. Connector tự fetch /order/items/get nếu cả 2 trên rỗng.
        $shipmentItemIds = array_values(array_filter(array_map('intval', (array) data_get($shipment->raw, 'external_item_ids', [])), fn ($v) => $v > 0));
        $itemIds = $shipmentItemIds !== [] ? $shipmentItemIds : OrderItem::withoutGlobalScope(TenantScope::class)
            ->where('order_id', $order->getKey())
            ->whereNotNull('external_item_id')
            ->pluck('external_item_id')
            ->map(fn ($v) => (int) $v)
            ->filter(fn ($v) => $v > 0)
            ->values()
            ->all();
        if (blank($shipment->package_no) && $account->provider === 'tiktok') {
            // TikTok bắt buộc package_no; thiếu thì khỏi gọi.
            return;
        }
        // Lazada 3PL (LEX VN / GHN / J&T) render AWB PDF *async* 5–30s sau /order/rts. Sync retry chỉ ngắn
        // (justArranged: 3 attempts ~4s, refetch: 2 attempts ~2s) để không block bulk request quá lâu — đủ
        // bắt 80% case PDF render trong cùng giây. Nếu vẫn rỗng ⇒ queue {@see FetchChannelLabel} job retry sau
        // 15s/30s/60s/120s/300s ⇒ tự lấy xong đẩy R2 ⇒ user mở list thấy `has_label=true` không cần click gì.
        // Khi lấy được bytes ⇒ media->storeBytes đẩy R2 + lưu `label_path`; render sau đọc R2 (`media->get`)
        // ⇒ KHÔNG bao giờ gọi lại sàn cho cùng vận đơn nữa. SPEC 0013 §6 / 0008b.
        $attempts = $account->provider === 'lazada' ? ($justArranged ? 3 : 2) : 1;
        $delayMs = 1_500;
        $lastError = null;
        for ($i = 1; $i <= $attempts; $i++) {
            try {
                // SHIPPING_LABEL_AND_PACKING_SLIP ⇒ PDF gồm cả tem vận đơn + phiếu đóng gói (có tên SP / SKU / SL) — SPEC 0013 §6.
                $doc = $this->channels->for($account->provider)->getShippingDocument($account->authContext(), (string) $order->external_order_id, [
                    'type' => 'SHIPPING_LABEL_AND_PACKING_SLIP',
                    'externalPackageId' => (string) $shipment->package_no,
                    'order_item_ids' => $itemIds,
                ]);
                $bytes = (string) $doc['bytes'];
                if ($bytes === '') {
                    return;
                }
                $stored = $this->media->storeBytes($bytes, (int) $order->tenant_id, 'labels', 'sh'.$shipment->getKey().'-'.Str::ulid(), 'pdf');
                // Lấy tem thành công ⇒ clear `label_fetch_next_retry_at` để vận đơn rời sub-tab "Đang tải lại".
                $shipment->forceFill(['label_url' => $stored['url'], 'label_path' => $stored['path'], 'label_fetch_next_retry_at' => null])->save();

                return;
            } catch (\Throwable $e) {
                $lastError = $e;
                if ($i < $attempts) {
                    // Exponential: 1s, 2s, 4s, 8s — propagation window for 3PL async render
                    $wait = $delayMs * (2 ** ($i - 1));
                    usleep(min(8_000_000, $wait * 1_000));
                }
            }
        }
        Log::info('shipment.fetch_channel_label_failed', ['shipment' => $shipment->getKey(), 'provider' => $account->provider, 'attempts' => $attempts, 'error' => $lastError?->getMessage()]);
        // Sync retry exhausted ⇒ queue async retry (15s/30s/60s/120s/300s) — đặc biệt cho Lazada 3PL render
        // PDF async 5–30s+ sau /order/rts. Khi job lấy được tem ⇒ đẩy R2 ⇒ list FE tự thấy `has_label=true`.
        // Skip nếu đang chạy từ job (Laravel queue tự retry theo backoff — tránh enqueue đệ quy).
        if (! $skipAsyncRetry && $account->provider === 'lazada') {
            $delaySec = 15;
            \CMBcoreSeller\Modules\Fulfillment\Jobs\FetchChannelLabel::dispatch((int) $shipment->getKey())
                ->onQueue('labels')->delay(now()->addSeconds($delaySec));
            // Đánh dấu vận đơn vào sub-tab "Đang tải lại" — `label_fetch_next_retry_at > NOW()` ⇒
            // OrderController::applySlipFilter() phân loại sang `loading` (không gộp vào `failed`/`Nhận phiếu`).
            $shipment->forceFill(['label_fetch_next_retry_at' => now()->addSeconds($delaySec)])->save();
        }
    }

    /**
     * Public entry cho `FetchChannelLabel` job — chạy lại 1 lượt fetch tem từ background queue. Đặt
     * `$skipAsyncRetry=true` để KHÔNG enqueue thêm job mới ở đáy (Laravel queue tự retry theo backoff).
     * Caller (job) check `shipment->label_path` sau khi gọi để biết đã lấy được chưa.
     */
    public function retryChannelLabelFetch(Order $order, Shipment $shipment): void
    {
        $this->fetchAndStoreChannelLabel($order, $shipment, justArranged: false, skipAsyncRetry: true);
    }

    /**
     * "Chuẩn bị hàng" cho **đơn sàn**: tự gọi sàn arrange-shipment (đẩy trạng thái "đã in đơn" lên sàn) + lấy
     * AWB & tem thật của sàn (`getShippingDocument`) — KHÔNG tự sinh mã/tem giả, KHÔNG tự tạo phiếu giao hàng
     * tạm. Gọi sàn lỗi / connector chưa hỗ trợ "luồng A" ⇒ vẫn tạo vận đơn cục bộ (mã có thể trống, gắn cờ
     * `has_issue` để hướng dẫn "Nhận phiếu giao hàng"). Đơn → `processing`. SPEC 0013/0014.
     */
    private function prepareChannelOrder(Order $order, ?int $userId): Shipment
    {
        $tenantId = (int) $order->tenant_id;
        $pkg = (array) (($order->packages ?? [])[0] ?? []);
        $tracking = ! empty($pkg['trackingNo']) ? (string) $pkg['trackingNo'] : null;
        $carrier = ! empty($pkg['carrier']) ? (string) $pkg['carrier'] : null;
        $packageId = ! empty($pkg['externalPackageId']) ? (string) $pkg['externalPackageId'] : null;
        $issue = null;

        $externalItemIds = [];
        if ($tracking === null) {
            try {
                $arr = $this->arrangeOnChannel($order);   // null = kênh bán chưa hỗ trợ tự lấy phiếu; ném lỗi nếu gọi sàn lỗi
                if ($arr === null) {
                    $issue = 'Kênh bán này chưa hỗ trợ tự lấy phiếu giao hàng tự động — bấm "Nhận phiếu giao hàng" để thử lại sau.';
                } else {
                    $tracking = $arr['tracking_no'];
                    $carrier = $arr['carrier'] ?: $carrier;
                    $packageId = $arr['package_id'] ?: $packageId;
                    // Lazada trả `external_item_ids` ⇒ lưu vào `shipment.raw` cho bước /order/rts về sau
                    // (markPacked → pushReadyToShip). order_item_id ≠ order_id — phải lưu để Lazada chấp nhận.
                    $externalItemIds = array_values(array_filter(array_map('intval', (array) ($arr['external_item_ids'] ?? [])), fn ($v) => $v > 0));
                    if ($tracking === null) {
                        $issue = 'Đã sắp xếp vận chuyển trên sàn — đang chờ sàn cấp mã vận đơn, hệ thống sẽ tự cập nhật khi có.';
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('shipment.prepare_channel_arrange_failed', ['order' => $order->getKey(), 'error' => $e->getMessage()]);
                $issue = $e->getMessage();
            }
        }
        $carrier = $carrier ?: ($order->carrier ?: $order->source);

        $shipment = DB::transaction(function () use ($tenantId, $order, $carrier, $tracking, $packageId, $issue, $userId, $externalItemIds) {
            $sh = Shipment::query()->create([
                'tenant_id' => $tenantId, 'order_id' => $order->getKey(), 'carrier' => $carrier,
                'carrier_account_id' => null, 'package_no' => $packageId, 'tracking_no' => $tracking, 'status' => Shipment::STATUS_CREATED,
                'weight_grams' => $this->estimateWeight($order, $tenantId),
                'cod_amount' => $order->is_cod ? (int) ($order->cod_amount ?: $order->grand_total) : 0,
                // `raw.external_item_ids` cần cho Lazada /order/rts ở markPacked — KHÁC `order_id`/`package_no`.
                'raw' => $externalItemIds ? ['external_item_ids' => $externalItemIds] : [],
            ]);
            $this->recordEvent($sh, 'created', $tracking ? 'Đã chuẩn bị hàng — mã vận đơn của sàn: '.$tracking : 'Đã chuẩn bị hàng — chờ mã vận đơn từ sàn', Shipment::STATUS_CREATED, ShipmentEvent::SOURCE_SYSTEM, null, $userId);
            // Sync `orders.carrier` về ĐÚNG carrier của shipment — Lazada thường map provider lúc /order/pack
            // (vd seller chọn "GHN" ⇒ Lazada trả "Giao Hang Nhanh Vietnam"); denormalization lệch sẽ làm
            // chip "Vận chuyển" hiện carrier cũ trong khi list query đã follow vận đơn ⇒ click chip ra empty.
            $patch = ($carrier && $order->carrier !== $carrier) ? ['carrier' => $carrier] : [];
            if ($issue) {
                $patch += ['has_issue' => true, 'issue_reason' => Str::limit((string) $issue, 240)];
            } elseif ($order->has_issue && (str_contains((string) $order->issue_reason, 'mã vận đơn') || str_contains((string) $order->issue_reason, 'phiếu giao hàng') || str_contains((string) $order->issue_reason, 'sắp xếp vận chuyển'))) {
                $patch += ['has_issue' => false, 'issue_reason' => null];
            }
            if ($patch !== []) {
                $order->forceFill($patch)->save();
            }

            return $sh;
        });
        if ($shipment->tracking_no) {
            // justArranged=true ⇒ vừa /order/rts (Lazada) hoặc /packages/.../ship (TikTok) ⇒ retry tem cho Lazada (propagation 1–3s)
            $this->fetchAndStoreChannelLabel($order, $shipment, justArranged: true);
        }
        $this->orderStatus->apply($order, S::Processing, 'system', [S::Pending], $userId);
        $shipment->refresh();
        ShipmentCreated::dispatch($shipment);
        // KHÔNG queue phiếu giao hàng tự tạo cho đơn sàn (rule cố định — phiếu tạm không thay được AWB của
        // sàn; ĐVVC sẽ từ chối). Nếu chưa lấy được tem thật, đã có cờ `has_issue` để người dùng "Nhận phiếu
        // giao hàng" thử lại — và `refetchSlip()` cũng KHÔNG tạo phiếu tạm cho đơn sàn.

        return $shipment;
    }

    /**
     * Đơn "hết hàng / âm tồn": có ≥1 dòng mà SKU đã đặt vượt tồn vật lý (tổng `available_cached` < 0).
     * In phiếu giao hàng cho đơn như vậy ⇒ ĐVVC tới lấy mà không có hàng ⇒ shop bị phạt. SPEC 0013.
     */
    public function isOutOfStock(Order $order): bool
    {
        $tenantId = (int) $order->tenant_id;
        $skuIds = OrderItem::withoutGlobalScope(TenantScope::class)
            ->where('order_id', $order->getKey())->whereNotNull('sku_id')->pluck('sku_id')->unique();
        foreach ($skuIds as $skuId) {
            if ($this->ledger->netStockForSku($tenantId, (int) $skuId) < 0) {
                return true;
            }
        }

        return false;
    }

    /** Resolve the carrier account to use (explicit id → default → fall back to built-in `manual`). */
    private function resolveAccount(int $tenantId, ?int $carrierAccountId): ?CarrierAccount
    {
        if ($carrierAccountId) {
            return CarrierAccount::query()->where('tenant_id', $tenantId)->where('is_active', true)->findOrFail($carrierAccountId);
        }

        return CarrierAccount::query()->where('tenant_id', $tenantId)->where('is_active', true)
            ->orderByDesc('is_default')->orderBy('id')->first();
    }

    /**
     * Create a shipment for an order (or return the existing open one — 1 order = 1 active shipment).
     *
     * @param  array<string,mixed>  $opts  tracking_no?, cod_amount?, weight_grams?, note?, required_note?
     */
    public function createForOrder(Order $order, ?int $carrierAccountId, ?string $service, array $opts = [], ?int $userId = null): Shipment
    {
        $tenantId = (int) $order->tenant_id;

        $existing = Shipment::query()->where('tenant_id', $tenantId)->where('order_id', $order->getKey())->open()->first();
        if ($existing) {
            return $existing;
        }
        if ($order->status->isTerminal() || in_array($order->status, [S::Returning, S::ReturnedRefunded], true)) {
            throw new RuntimeException('Đơn ở trạng thái không thể chuẩn bị hàng / tạo vận đơn.');
        }
        // Chặn "chuẩn bị hàng / in phiếu giao hàng" khi đơn hết hàng (âm tồn) — SPEC 0013.
        if ($this->isOutOfStock($order)) {
            throw new RuntimeException('Đơn có SKU hết hàng (âm tồn) — không thể chuẩn bị hàng / lấy phiếu giao hàng. Hãy nhập thêm hàng rồi thử lại.');
        }
        // Đơn sàn: dùng mã vận đơn / AWB của sàn (đồng bộ về hoặc "luồng A"), cập nhật trạng thái "đã in đơn"
        // lên sàn — không tự sinh mã vận đơn giả như đơn manual. SPEC 0013/0014.
        if ($order->channel_account_id) {
            return $this->prepareChannelOrder($order, $userId);
        }

        $account = $this->resolveAccount($tenantId, $carrierAccountId);
        if ($account) {
            $carrierCode = $account->carrier;
            $accountArr = $account->toConnectorArray();
            $service = $service ?: $account->default_service;
        } else {
            $carrierCode = 'manual';
            $accountArr = ['carrier' => 'manual', 'credentials' => [], 'meta' => []];
        }
        if (! $this->carriers->has($carrierCode)) {
            throw new RuntimeException("Đơn vị vận chuyển [{$carrierCode}] chưa được bật.");
        }
        $connector = $this->carriers->for($carrierCode);

        $weight = isset($opts['weight_grams']) ? (int) $opts['weight_grams'] : $this->estimateWeight($order, $tenantId);
        $cod = isset($opts['cod_amount']) ? (int) $opts['cod_amount'] : ($order->is_cod ? (int) ($order->cod_amount ?: $order->grand_total) : 0);

        $payload = $this->buildCreatePayload($order, $tenantId, $service, $weight, $cod, $opts, $accountArr);

        $result = $connector->createShipment($accountArr, $payload);
        $tracking = (string) ($result['tracking_no'] ?? '');
        if ($tracking === '') {
            throw new RuntimeException('Đơn vị vận chuyển không trả về mã vận đơn.');
        }

        $shipment = DB::transaction(function () use ($tenantId, $order, $carrierCode, $account, $tracking, $service, $weight, $cod, $result, $userId) {
            $shipment = Shipment::query()->create([
                'tenant_id' => $tenantId, 'order_id' => $order->getKey(), 'carrier' => $carrierCode,
                'carrier_account_id' => $account?->getKey(), 'tracking_no' => $tracking, 'status' => Shipment::STATUS_CREATED,
                'service' => $service, 'weight_grams' => $weight, 'cod_amount' => $cod,
                'fee' => (int) ($result['fee'] ?? 0), 'raw' => $result['raw'] ?? $result,
            ]);
            $this->recordEvent($shipment, 'created', 'Đã tạo vận đơn', Shipment::STATUS_CREATED, ShipmentEvent::SOURCE_SYSTEM, null, $userId);
            if ($order->carrier !== $carrierCode) {
                $order->forceFill(['carrier' => $carrierCode])->save();
            }

            return $shipment;
        });

        // Fetch & store the carrier label (best effort — a missing label must not fail the shipment).
        $this->fetchLabel($shipment, $connector, $accountArr);

        // "Chuẩn bị hàng" = đã tạo vận đơn / lấy phiếu giao hàng ⇒ đơn pending → processing (xử lý nội bộ:
        // gói + quét). Chỉ chuyển sang ready_to_ship khi NV xác nhận đã gói (markPacked). SPEC 0013.
        $this->orderStatus->apply($order, S::Processing, 'system', [S::Pending], $userId);

        $shipment->refresh();
        ShipmentCreated::dispatch($shipment);
        if (blank($shipment->label_path)) {
            $this->queueDeliverySlip($order, $userId);   // ĐVVC manual không có tem ⇒ kéo phiếu giao hàng tự tạo về R2 — SPEC 0013
        }

        return $shipment;
    }

    /**
     * @param  list<int>  $orderIds
     * @return array{created: list<Shipment>, errors: list<array{order_id:int,message:string}>}
     */
    public function bulkCreate(int $tenantId, array $orderIds, ?int $carrierAccountId, ?string $service, ?int $userId = null): array
    {
        $created = [];
        $errors = [];
        $orders = Order::query()->where('tenant_id', $tenantId)->whereIn('id', $orderIds)->whereNull('deleted_at')->get()->keyBy('id');
        foreach ($orderIds as $oid) {
            $order = $orders->get($oid);
            if (! $order) {
                $errors[] = ['order_id' => (int) $oid, 'message' => 'Không tìm thấy đơn.'];

                continue;
            }
            try {
                $created[] = $this->createForOrder($order, $carrierAccountId, $service, [], $userId);
            } catch (\Throwable $e) {
                $errors[] = ['order_id' => (int) $oid, 'message' => $e->getMessage()];
            }
        }

        return ['created' => $created, 'errors' => $errors];
    }

    /**
     * Đã gói hàng & quét đơn nội bộ xong:
     *   - Carrier có cap `awaiting_pickup_flow` (GHN, GHTK/J&T sau này): shipment → `awaiting_pickup`
     *     ("Chờ lấy hàng") — đơn đã trong hệ thống ĐVVC từ bước "Chuẩn bị hàng" (createShipment đã gọi
     *     trước & nhận tracking), không cần API call mới; chỉ flip local state để FE hiển thị đúng badge.
     *   - Manual / đơn sàn không có cap: shipment → `packed` (behavior cũ).
     *
     * **Đơn processing → ready_to_ship** (sẵn sàng bàn giao ĐVVC). Chưa trừ tồn — trừ tồn ở bước bàn
     * giao (handover / sàn báo shipped / ĐVVC báo picked).
     *
     * Đối với connector có `shipping.ready_to_ship` capability (Lazada): đẩy /order/rts lên sàn để Lazada
     * cập nhật `packed → ready_to_ship` — đúng spec 3-tab app khớp 3 trạng thái Lazada (xem `lazada_order.md`).
     * TikTok không có bước này (cap=false) ⇒ skip — TikTok đã ở `AWAITING_COLLECTION` từ arrange.
     *
     * Idempotent. Trả true nếu có chuyển trạng thái. SPEC 0013 (mở rộng SPEC 0009) + SPEC 0021 (awaiting_pickup).
     */
    public function markPacked(Shipment $shipment, string $source = 'user', ?int $userId = null): bool
    {
        if ($shipment->isCancelled()) {
            throw new RuntimeException('Vận đơn đã huỷ.');
        }
        if (in_array($shipment->status, [Shipment::STATUS_PACKED, Shipment::STATUS_AWAITING_PICKUP, ...Shipment::HANDED_OVER_STATUSES], true)) {
            return false; // already packed/awaiting_pickup/handed over — no-op (anti double-scan, rule 5)
        }
        $order = $this->orderFor($shipment);
        // Push lên sàn TRƯỚC khi flip shipment.status — nếu sàn reject (vd Lazada item bị buyer huỷ), giữ
        // shipment ở `created` để user retry, không bị mất sync giữa app & sàn.
        if ($order) {
            $this->pushReadyToShipOnChannel($order, $shipment);
        }
        // SPEC 0021 — `awaiting_pickup_flow` capability: GHN có (đơn manual sau khi /create-order đã ở
        // trạng thái `ready_to_pick` của GHN, đợi shipper); GHTK/J&T sẽ thêm khi connector lên. Core
        // không hard-code 'ghn' — chỉ hỏi capability.
        $useAwaitingPickup = $this->carriers->has($shipment->carrier)
            && $this->carriers->for($shipment->carrier) instanceof AbstractCarrierConnector
            && $this->carriers->for($shipment->carrier)->supports('awaiting_pickup_flow');
        $newStatus = $useAwaitingPickup ? Shipment::STATUS_AWAITING_PICKUP : Shipment::STATUS_PACKED;
        $eventDesc = $useAwaitingPickup ? 'Đã sẵn sàng bàn giao — chờ ĐVVC tới lấy hàng' : 'Đã đóng gói & quét đơn';
        $shipment->forceFill(['status' => $newStatus, 'packed_at' => now()])->save();
        $this->recordEvent($shipment, 'packed', $eventDesc, $newStatus, $source, null, $userId);
        if ($order) {
            $this->orderStatus->apply($order, S::ReadyToShip, $source, [S::Pending, S::Processing], $userId);
        }

        return true;
    }

    /**
     * Đẩy /order/rts (Lazada) — đơn từ `packed` → `ready_to_ship` trên sàn. Capability-gated: chỉ chạy nếu
     * connector khai báo `shipping.ready_to_ship`=true (Lazada). TikTok / Shopee / Manual: skip.
     * Lỗi gọi sàn ⇒ gắn `has_issue` lên order để user retry "Nhận phiếu giao hàng"; KHÔNG throw — markPacked
     * vẫn tiếp tục để app không bị kẹt khi sàn flaky.
     */
    private function pushReadyToShipOnChannel(Order $order, Shipment $shipment): void
    {
        if (! $order->channel_account_id || ! $shipment->tracking_no) {
            return;
        }
        $account = ChannelAccount::query()->find($order->channel_account_id);
        if (! $account || ! $this->channels->has($account->provider) || ! $this->channels->for($account->provider)->supports('shipping.ready_to_ship')) {
            return;
        }
        $rawItemIds = array_values(array_filter(array_map('intval', (array) data_get($shipment->raw, 'external_item_ids', [])), fn ($v) => $v > 0));
        try {
            $this->channels->for($account->provider)->pushReadyToShip($account->authContext(), (string) $order->external_order_id, [
                'tracking_no' => (string) $shipment->tracking_no,
                'shipment_provider' => (string) $shipment->carrier,
                'external_item_ids' => $rawItemIds,   // KHÁC order_id — Lazada /order/rts yêu cầu order_item_id đúng từng item
                'packageId' => $shipment->package_no ? (string) $shipment->package_no : null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('shipment.push_ready_to_ship_failed', ['order' => $order->getKey(), 'provider' => $account->provider, 'error' => $e->getMessage()]);
            $order->forceFill([
                'has_issue' => true,
                'issue_reason' => Str::limit('Sàn chưa nhận lệnh "sẵn sàng bàn giao" (RTS): '.$e->getMessage(), 240),
            ])->save();
        }
    }

    /** Hand a parcel over to the carrier: created/packed → picked_up, order → shipped, stock leaves. Idempotent. */
    public function handover(Shipment $shipment, string $source = 'system', ?int $userId = null, string $eventCode = 'handover'): bool
    {
        if ($shipment->isCancelled()) {
            throw new RuntimeException('Vận đơn đã huỷ.');
        }
        if (in_array($shipment->status, Shipment::HANDED_OVER_STATUSES, true)) {
            return false; // already handed over — no-op (anti double-scan, rule 5)
        }
        DB::transaction(function () use ($shipment, $source, $userId, $eventCode) {
            $patch = ['status' => Shipment::STATUS_PICKED_UP, 'picked_up_at' => now()];
            if ($shipment->packed_at === null) {
                $patch['packed_at'] = now();   // handing over without an explicit "pack" step
            }
            $shipment->forceFill($patch)->save();
            $this->recordEvent($shipment, $eventCode, $eventCode === 'packed_scanned' ? 'Đã quét bàn giao' : 'Đã bàn giao ĐVVC', Shipment::STATUS_PICKED_UP, $source, null, $userId);
        });
        if ($order = $this->orderFor($shipment)) {
            $this->orderStatus->apply($order, S::Shipped, $source, [S::Pending, S::Processing, S::ReadyToShip], $userId);
        }

        return true;
    }

    public function cancel(Shipment $shipment, ?int $userId = null): void
    {
        if ($shipment->isCancelled()) {
            return;
        }
        $alreadyShipped = in_array($shipment->status, [Shipment::STATUS_PICKED_UP, Shipment::STATUS_IN_TRANSIT, Shipment::STATUS_DELIVERED], true);
        if ($this->carriers->has($shipment->carrier)) {
            try {
                $this->carriers->for($shipment->carrier)->cancel($shipment->carrierAccount?->toConnectorArray() ?? [], (string) $shipment->tracking_no);
            } catch (\Throwable $e) {
                Log::warning('shipment.cancel_carrier_failed', ['shipment' => $shipment->getKey(), 'error' => $e->getMessage()]);
            }
        }
        DB::transaction(function () use ($shipment, $userId) {
            $shipment->forceFill(['status' => Shipment::STATUS_CANCELLED])->save();
            $this->recordEvent($shipment, 'cancelled', 'Đã huỷ vận đơn', Shipment::STATUS_CANCELLED, ShipmentEvent::SOURCE_USER, null, $userId);
        });
        if (! $alreadyShipped && ($order = $this->orderFor($shipment)) && $order->status === S::ReadyToShip) {
            $this->orderStatus->apply($order, S::Processing, 'system', [S::ReadyToShip], $userId);
        }
    }

    /** Poll the carrier for tracking, append new events, sync shipment & order status. */
    public function syncTracking(Shipment $shipment): void
    {
        if (! $this->carriers->has($shipment->carrier) || ! $shipment->tracking_no) {
            return;
        }
        $connector = $this->carriers->for($shipment->carrier);
        try {
            $data = $connector->getTracking($shipment->carrierAccount?->toConnectorArray() ?? [], $shipment->tracking_no);
        } catch (\Throwable $e) {
            Log::warning('shipment.track_failed', ['shipment' => $shipment->getKey(), 'error' => $e->getMessage()]);

            return;
        }
        foreach ((array) ($data['events'] ?? []) as $ev) {
            $occurred = $this->parseTime($ev['occurred_at'] ?? null);
            ShipmentEvent::withoutGlobalScope(TenantScope::class)->firstOrCreate(
                ['shipment_id' => $shipment->getKey(), 'code' => (string) ($ev['code'] ?? 'update'), 'occurred_at' => $occurred],
                ['tenant_id' => $shipment->tenant_id, 'description' => $ev['description'] ?? null, 'status' => $ev['status'] ?? null, 'source' => 'carrier', 'raw' => $ev['raw'] ?? $ev, 'created_at' => now()],
            );
        }
        $newStatus = $data['status'] ?? null;
        $known = [Shipment::STATUS_CREATED, Shipment::STATUS_AWAITING_PICKUP, Shipment::STATUS_PICKED_UP, Shipment::STATUS_IN_TRANSIT, Shipment::STATUS_DELIVERED, Shipment::STATUS_FAILED, Shipment::STATUS_RETURNED];
        if ($newStatus && in_array($newStatus, $known, true) && $newStatus !== $shipment->status) {
            $attrs = ['status' => $newStatus];
            if ($newStatus === Shipment::STATUS_PICKED_UP && $shipment->picked_up_at === null) {
                $attrs['picked_up_at'] = now(); // GHN báo "picked" qua webhook/polling ⇒ ghi mốc thực tế
            }
            if ($newStatus === Shipment::STATUS_DELIVERED) {
                $attrs['delivered_at'] = now();
            }
            $shipment->forceFill($attrs)->save();
            $this->syncOrderToShipmentStatus($shipment, $newStatus);
        }
    }

    /** Resolve a scanned barcode (tracking number or order code) to a shipment within the tenant. */
    public function findByScanCode(int $tenantId, string $code): ?Shipment
    {
        $code = trim($code);
        if ($code === '') {
            return null;
        }
        $byTracking = Shipment::query()->where('tenant_id', $tenantId)->where('tracking_no', $code)->open()->latest('id')->first();
        if ($byTracking) {
            return $byTracking;
        }
        $orderIds = Order::query()->where('tenant_id', $tenantId)->whereNull('deleted_at')
            ->where(fn ($q) => $q->where('order_number', $code)->orWhere('external_order_id', $code))
            ->pluck('id');
        if ($orderIds->isEmpty() && ctype_digit($code)) {
            $orderIds = Order::query()->where('tenant_id', $tenantId)->whereNull('deleted_at')->where('id', (int) $code)->pluck('id');
        }

        return $orderIds->isEmpty() ? null
            : Shipment::query()->where('tenant_id', $tenantId)->whereIn('order_id', $orderIds)->open()->latest('id')->first();
    }

    // ---- internals -----------------------------------------------------------------

    private function fetchLabel(Shipment $shipment, $connector, array $accountArr): void
    {
        if (! ($connector instanceof AbstractCarrierConnector) || ! $connector->supports('getLabel')) {
            return;
        }
        try {
            $label = $connector->getLabel($accountArr, (string) $shipment->tracking_no);
            $bytes = (string) $label['bytes'];
            if ($bytes === '') {
                return;
            }
            $stored = $this->media->storeBytes($bytes, (int) $shipment->tenant_id, 'labels', (string) $shipment->getKey(), 'pdf');
            $shipment->forceFill(['label_url' => $stored['url'], 'label_path' => $stored['path']])->save();
        } catch (CarrierUnsupportedException) {
            // ignore
        } catch (\Throwable $e) {
            Log::warning('shipment.label_fetch_failed', ['shipment' => $shipment->getKey(), 'error' => $e->getMessage()]);
        }
    }

    private function buildCreatePayload(Order $order, int $tenantId, ?string $service, int $weight, int $cod, array $opts, array $accountArr): array
    {
        $addr = (array) ($order->shipping_address ?? []);
        $from = (array) ($accountArr['meta']['from_address'] ?? []);
        $recipient = [
            'name' => $addr['fullName'] ?? $addr['name'] ?? $order->buyer_name,
            'phone' => $addr['phone'] ?? $order->buyer_phone,
            'address' => trim(implode(', ', array_filter([$addr['line1'] ?? null, $addr['address'] ?? null, $addr['ward'] ?? null, $addr['district'] ?? null, $addr['province'] ?? null]))) ?: ($addr['address'] ?? null),
            'ward_code' => $addr['ward_code'] ?? null,
            'district_id' => $addr['district_id'] ?? null,
            'province' => $addr['province'] ?? null,
        ];

        return [
            'recipient' => $recipient,
            'sender' => $from,
            'parcel' => ['weight_grams' => $weight, 'length_cm' => $opts['length_cm'] ?? 15, 'width_cm' => $opts['width_cm'] ?? 15, 'height_cm' => $opts['height_cm'] ?? 10],
            'cod_amount' => $cod,
            'service' => $service,
            'required_note' => $opts['required_note'] ?? null,
            'content' => 'Đơn '.($order->order_number ?? $order->external_order_id ?? ('#'.$order->getKey())),
            'client_order_code' => (string) ($order->order_number ?? $order->external_order_id ?? $order->getKey()),
            'tracking_no' => $opts['tracking_no'] ?? null,
            'fee' => $opts['fee'] ?? 0,
            'to_district_id' => $addr['district_id'] ?? null,
            'to_ward_code' => $addr['ward_code'] ?? null,
        ];
    }

    private function estimateWeight(Order $order, int $tenantId): int
    {
        $items = OrderItem::withoutGlobalScope(TenantScope::class)->where('order_id', $order->getKey())->get(['sku_id', 'quantity']);
        $skuIds = $items->pluck('sku_id')->filter()->unique()->all();
        $weights = $skuIds ? Sku::withoutGlobalScope(TenantScope::class)->whereIn('id', $skuIds)->pluck('weight_grams', 'id') : collect();
        $default = (int) config('fulfillment.default_weight_grams', 500);
        $total = 0;
        foreach ($items as $it) {
            $w = ($it->sku_id && $weights->get($it->sku_id)) ? (int) $weights->get($it->sku_id) : $default;
            $total += $w * max(1, (int) $it->quantity);
        }

        return max($total, $default);
    }

    private function recordEvent(Shipment $shipment, string $code, ?string $desc, ?string $status, string $source, ?array $raw, ?int $userId): void
    {
        ShipmentEvent::withoutGlobalScope(TenantScope::class)->firstOrCreate(
            ['shipment_id' => $shipment->getKey(), 'code' => $code, 'occurred_at' => now()],
            ['tenant_id' => $shipment->tenant_id, 'description' => $desc, 'status' => $status, 'source' => $source, 'raw' => $raw ? ($userId ? $raw + ['by' => $userId] : $raw) : ($userId ? ['by' => $userId] : null), 'created_at' => now()],
        );
    }

    private function orderFor(Shipment $shipment): ?Order
    {
        return Order::query()->where('tenant_id', $shipment->tenant_id)->whereNull('deleted_at')->find($shipment->order_id);
    }

    private function syncOrderToShipmentStatus(Shipment $shipment, string $shipmentStatus): void
    {
        $order = $this->orderFor($shipment);
        if (! $order) {
            return;
        }
        $map = [
            // SPEC 0021 — awaiting_pickup ⇒ order vẫn ở "Chờ bàn giao" (ready_to_ship); chưa shipped vì
            // shipper chưa thực sự lấy hàng. Chỉ khi GHN báo `picked` ⇒ shipment.picked_up ⇒ order.shipped.
            Shipment::STATUS_AWAITING_PICKUP => S::ReadyToShip,
            Shipment::STATUS_PICKED_UP => S::Shipped, Shipment::STATUS_IN_TRANSIT => S::Shipped,
            Shipment::STATUS_DELIVERED => S::Delivered, Shipment::STATUS_FAILED => S::DeliveryFailed,
            Shipment::STATUS_RETURNED => S::Returning,
        ];
        if ($to = $map[$shipmentStatus] ?? null) {
            $this->orderStatus->apply($order, $to, 'carrier');
        }
    }

    private function parseTime($v): Carbon
    {
        try {
            return $v ? Carbon::parse($v) : now();
        } catch (\Throwable) {
            return now();
        }
    }
}
