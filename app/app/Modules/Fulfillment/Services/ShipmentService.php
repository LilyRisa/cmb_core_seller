<?php

namespace CMBcoreSeller\Modules\Fulfillment\Services;

use CMBcoreSeller\Integrations\Carriers\CarrierRegistry;
use CMBcoreSeller\Integrations\Carriers\Support\AbstractCarrierConnector;
use CMBcoreSeller\Integrations\Carriers\Support\CarrierUnsupportedException;
use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Fulfillment\Events\ShipmentCreated;
use CMBcoreSeller\Modules\Fulfillment\Models\CarrierAccount;
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
    ) {}

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
            throw new RuntimeException('Không cập nhật được trạng thái "đã in đơn" lên sàn: '.$e->getMessage());
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

    /** Best-effort: kéo tem/AWB **thật của sàn** (PDF) về kho media & gắn vào vận đơn — KHÔNG tự vẽ tem. SPEC 0006 §9.1 / 0014. */
    private function fetchAndStoreChannelLabel(Order $order, Shipment $shipment): void
    {
        $account = $order->channel_account_id ? ChannelAccount::query()->find($order->channel_account_id) : null;
        if (! $account || ! $this->channels->has($account->provider) || ! $this->channels->for($account->provider)->supports('shipping.document') || blank($shipment->package_no)) {
            return;
        }
        try {
            $doc = $this->channels->for($account->provider)->getShippingDocument($account->authContext(), (string) $order->external_order_id, [
                'type' => 'SHIPPING_LABEL', 'externalPackageId' => (string) $shipment->package_no,
            ]);
            $bytes = (string) $doc['bytes'];
            if ($bytes === '') {
                return;
            }
            $stored = $this->media->storeBytes($bytes, (int) $order->tenant_id, 'labels', 'sh'.$shipment->getKey().'-'.Str::ulid(), 'pdf');
            $shipment->forceFill(['label_url' => $stored['url'], 'label_path' => $stored['path']])->save();
        } catch (\Throwable $e) {
            Log::info('shipment.fetch_channel_label_failed', ['shipment' => $shipment->getKey(), 'provider' => $account->provider, 'error' => $e->getMessage()]);
        }
    }

    /**
     * "Chuẩn bị hàng" cho **đơn sàn**: tự gọi sàn arrange-shipment (đẩy trạng thái "đã in đơn" lên sàn) + lấy
     * AWB & tem thật của sàn (`getShippingDocument`) — KHÔNG tự sinh mã/tem giả. Gọi sàn lỗi / connector chưa
     * hỗ trợ "luồng A" ⇒ vẫn tạo vận đơn cục bộ (mã có thể trống, gắn cờ `has_issue` nhắc nhở), không chặn.
     * Đơn → `processing`. SPEC 0013/0014.
     */
    private function prepareChannelOrder(Order $order, ?int $userId): Shipment
    {
        $tenantId = (int) $order->tenant_id;
        $pkg = (array) (($order->packages ?? [])[0] ?? []);
        $tracking = ! empty($pkg['trackingNo']) ? (string) $pkg['trackingNo'] : null;
        $carrier = ! empty($pkg['carrier']) ? (string) $pkg['carrier'] : null;
        $packageId = ! empty($pkg['externalPackageId']) ? (string) $pkg['externalPackageId'] : null;
        $issue = null;

        if ($tracking === null) {
            try {
                $arr = $this->arrangeOnChannel($order);   // null = connector chưa hỗ trợ; ném lỗi nếu gọi sàn lỗi
                if ($arr === null) {
                    $issue = 'Chưa lấy được mã vận đơn từ sàn — bật đồng bộ fulfillment ("luồng A") hoặc "Sắp xếp vận chuyển" trên app sàn rồi "Đồng bộ đơn".';
                } else {
                    $tracking = $arr['tracking_no'];
                    $carrier = $arr['carrier'] ?: $carrier;
                    $packageId = $arr['package_id'] ?: $packageId;
                    if ($tracking === null) {
                        $issue = 'Đã yêu cầu sàn sắp xếp vận chuyển; chờ sàn cấp mã vận đơn (sẽ đồng bộ về sau).';
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('shipment.prepare_channel_arrange_failed', ['order' => $order->getKey(), 'error' => $e->getMessage()]);
                $issue = $e->getMessage();
            }
        }
        $carrier = $carrier ?: ($order->carrier ?: $order->source);

        $shipment = DB::transaction(function () use ($tenantId, $order, $carrier, $tracking, $packageId, $issue, $userId) {
            $sh = Shipment::query()->create([
                'tenant_id' => $tenantId, 'order_id' => $order->getKey(), 'carrier' => $carrier,
                'carrier_account_id' => null, 'package_no' => $packageId, 'tracking_no' => $tracking, 'status' => Shipment::STATUS_CREATED,
                'weight_grams' => $this->estimateWeight($order, $tenantId),
                'cod_amount' => $order->is_cod ? (int) ($order->cod_amount ?: $order->grand_total) : 0, 'raw' => [],
            ]);
            $this->recordEvent($sh, 'created', $tracking ? 'Đã chuẩn bị hàng — mã vận đơn của sàn: '.$tracking : 'Đã chuẩn bị hàng — chờ mã vận đơn từ sàn', Shipment::STATUS_CREATED, ShipmentEvent::SOURCE_SYSTEM, null, $userId);
            $patch = $order->carrier ? [] : ['carrier' => $carrier];
            if ($issue) {
                $patch += ['has_issue' => true, 'issue_reason' => $issue];
            } elseif ($order->has_issue && str_contains((string) $order->issue_reason, 'mã vận đơn')) {
                $patch += ['has_issue' => false, 'issue_reason' => null];
            }
            if ($patch !== []) {
                $order->forceFill($patch)->save();
            }

            return $sh;
        });
        if ($shipment->tracking_no) {
            $this->fetchAndStoreChannelLabel($order, $shipment);   // kéo tem thật của sàn về (best-effort)
        }
        $this->orderStatus->apply($order, S::Processing, 'system', [S::Pending], $userId);
        $shipment->refresh();
        ShipmentCreated::dispatch($shipment);

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
            if (! $order->carrier) {
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
     * Đã gói hàng & quét đơn nội bộ xong: vận đơn created/pending → packed; **đơn processing → ready_to_ship**
     * (sẵn sàng bàn giao ĐVVC). Chưa trừ tồn — trừ tồn ở bước bàn giao (handover / sàn báo shipped).
     * Idempotent. Trả true nếu có chuyển trạng thái. SPEC 0013 (mở rộng SPEC 0009).
     */
    public function markPacked(Shipment $shipment, string $source = 'user', ?int $userId = null): bool
    {
        if ($shipment->isCancelled()) {
            throw new RuntimeException('Vận đơn đã huỷ.');
        }
        if (in_array($shipment->status, [Shipment::STATUS_PACKED, ...Shipment::HANDED_OVER_STATUSES], true)) {
            return false; // already packed/handed over — no-op (anti double-scan, rule 5)
        }
        $shipment->forceFill(['status' => Shipment::STATUS_PACKED, 'packed_at' => now()])->save();
        $this->recordEvent($shipment, 'packed', 'Đã đóng gói & quét đơn', Shipment::STATUS_PACKED, $source, null, $userId);
        if ($order = $this->orderFor($shipment)) {
            $this->orderStatus->apply($order, S::ReadyToShip, $source, [S::Pending, S::Processing], $userId);
        }

        return true;
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
        $known = [Shipment::STATUS_CREATED, Shipment::STATUS_PICKED_UP, Shipment::STATUS_IN_TRANSIT, Shipment::STATUS_DELIVERED, Shipment::STATUS_FAILED, Shipment::STATUS_RETURNED];
        if ($newStatus && in_array($newStatus, $known, true) && $newStatus !== $shipment->status) {
            $attrs = ['status' => $newStatus];
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
