<?php

namespace CMBcoreSeller\Integrations;

use CMBcoreSeller\Integrations\Ads\AdsRegistry;
use CMBcoreSeller\Integrations\Ads\Facebook\FacebookAdsConnector;
use CMBcoreSeller\Integrations\Ads\TikTok\TikTokAdsConnector;
use CMBcoreSeller\Integrations\Ai\AiAssistantRegistry;
use CMBcoreSeller\Integrations\Ai\Claude\ClaudeConnector;
use CMBcoreSeller\Integrations\Ai\CustomHttp\CustomHttpConnector;
use CMBcoreSeller\Integrations\Ai\Manual\ManualAiAssistantConnector;
use CMBcoreSeller\Integrations\Ai\OpenAi\OpenAiConnector;
use CMBcoreSeller\Integrations\Carriers\CarrierRegistry;
use CMBcoreSeller\Integrations\Carriers\Ghn\GhnConnector;
use CMBcoreSeller\Integrations\Carriers\Ghtk\GhtkConnector;
use CMBcoreSeller\Integrations\Carriers\Manual\ManualCarrierConnector;
use CMBcoreSeller\Integrations\Carriers\ViettelPost\ViettelPostConnector;
use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Integrations\Channels\Lazada\LazadaConnector;
use CMBcoreSeller\Integrations\Channels\Lazada\LazadaPublisher;
use CMBcoreSeller\Integrations\Channels\Manual\ManualConnector;
use CMBcoreSeller\Integrations\Channels\PublisherRegistry;
use CMBcoreSeller\Integrations\Channels\Shopee\ShopeeClient;
use CMBcoreSeller\Integrations\Channels\Shopee\ShopeeConnector;
use CMBcoreSeller\Integrations\Channels\Shopee\ShopeePublisher;
use CMBcoreSeller\Integrations\Channels\Shopee\ShopeeWebhookVerifier;
use CMBcoreSeller\Integrations\Channels\TikTok\TikTokConnector;
use CMBcoreSeller\Integrations\Channels\TikTok\TikTokPublisher;
use CMBcoreSeller\Integrations\EInvoice\EInvoiceRegistry;
use CMBcoreSeller\Integrations\EInvoice\MisaMeInvoice\MisaMeInvoiceConnector;
use CMBcoreSeller\Integrations\Embedding\Image\Clip\ClipEmbedder;
use CMBcoreSeller\Integrations\Embedding\Image\Contracts\ImageEmbedder;
use CMBcoreSeller\Integrations\Embedding\Image\ImageEmbedderRegistry;
use CMBcoreSeller\Integrations\Messaging\Facebook\FacebookPageConnector;
use CMBcoreSeller\Integrations\Messaging\Facebook\FacebookSignatureVerifier;
use CMBcoreSeller\Integrations\Messaging\Lazada\LazadaChatConnector;
use CMBcoreSeller\Integrations\Messaging\Manual\ManualMessagingConnector;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Integrations\Messaging\Shopee\ShopeeChatConnector;
use CMBcoreSeller\Integrations\Messaging\TikTok\TikTokChatConnector;
use CMBcoreSeller\Integrations\Messaging\Zalo\ZaloClient;
use CMBcoreSeller\Integrations\Messaging\Zalo\ZaloOaConnector;
use CMBcoreSeller\Integrations\Messaging\Zalo\ZaloSignatureVerifier;
use CMBcoreSeller\Integrations\Pancake\PancakeBadReportProvider;
use CMBcoreSeller\Integrations\Payments\Momo\MomoConnector;
use CMBcoreSeller\Integrations\Payments\PaymentRegistry;
use CMBcoreSeller\Integrations\Payments\SePay\SePayConnector;
use CMBcoreSeller\Integrations\Payments\VnPay\VnPayConnector;
use CMBcoreSeller\Integrations\Vector\Contracts\VectorStore;
use CMBcoreSeller\Integrations\Vector\Qdrant\QdrantStore;
use CMBcoreSeller\Integrations\Vector\VectorStoreRegistry;
use CMBcoreSeller\Modules\Customers\Contracts\CustomerBadReportProvider;
use Illuminate\Support\ServiceProvider;

/**
 * Wires the integration layer: the channel & carrier registries (singletons)
 * and registers the connectors enabled in config/integrations.php.
 *
 * To add a marketplace: create app/Integrations/Channels/<Name>/<Name>Connector,
 * add it to $channelConnectors below, and enable it in config/integrations.php.
 * To add a carrier: same under app/Integrations/Carriers/<Name>/.
 */
class IntegrationsServiceProvider extends ServiceProvider
{
    /**
     * Known channel connectors. The registry only loads the ones whose code
     * is listed in config('integrations.channels').
     *
     * @var array<string, class-string>
     */
    protected array $channelConnectors = [
        'manual' => ManualConnector::class,
        'tiktok' => TikTokConnector::class,
        'lazada' => LazadaConnector::class,
        'shopee' => ShopeeConnector::class,
    ];

    /**
     * Known carrier connectors. `manual` is always loaded; the rest are loaded per
     * config('integrations.carriers'). Add a carrier = a class here + the code in env.
     *
     * @var array<string, class-string>
     */
    protected array $carrierConnectors = [
        'manual' => ManualCarrierConnector::class,
        'ghn' => GhnConnector::class,
        'ghtk' => GhtkConnector::class,
        'viettelpost' => ViettelPostConnector::class,
        // 'jt'   => \CMBcoreSeller\Integrations\Carriers\JtExpress\JtExpressConnector::class,
    ];

    /**
     * Payment gateway connectors (Phase 6.4 / SPEC 0018). PaymentRegistry chỉ nạp những
     * gateway có trong `config('integrations.payments.enabled')`.
     *
     * @var array<string, class-string>
     */
    protected array $paymentConnectors = [
        'sepay' => SePayConnector::class,
        'vnpay' => VnPayConnector::class,
        'momo' => MomoConnector::class,                 // skeleton — capability=false
    ];

    /**
     * Nhà cung cấp HĐĐT (SPEC 0041). EInvoiceRegistry chỉ nạp provider có trong
     * config('integrations.einvoice.enabled').
     *
     * @var array<string, class-string>
     */
    protected array $einvoiceConnectors = [
        'misa' => MisaMeInvoiceConnector::class,
    ];

    /**
     * Messaging connectors (Phase 7.x đề xuất — SPEC-0024 / ADR-0017).
     * Registry chỉ nạp những provider trong `config('integrations.messaging')`.
     * `manual` luôn nạp (test/dev — tương tự ManualConnector cho Channels).
     *
     * S1: chỉ có `manual`. S2 thêm `facebook_page`, S4 thêm `tiktok_chat`/`shopee_chat`,
     * S8 cân nhắc `lazada_chat`.
     *
     * @var array<string, class-string>
     */
    protected array $messagingConnectors = [
        'manual' => ManualMessagingConnector::class,
        'facebook_page' => FacebookPageConnector::class,  // S2
        'tiktok_chat' => TikTokChatConnector::class,        // S4
        'lazada_chat' => LazadaChatConnector::class,        // S8 (best-effort, §11 Q3)
        'shopee_chat' => ShopeeChatConnector::class,        // SPEC-0024 (spec 2026-05-21)
        'zalo_oa' => ZaloOaConnector::class,                // Phase 1 Zalo OA
    ];

    /**
     * Ads providers (SPEC 2026-06-04). Loaded per `config('integrations.ads')`.
     *
     * @var array<string, class-string>
     */
    protected array $adsConnectors = [
        'facebook' => FacebookAdsConnector::class,
        'tiktok' => TikTokAdsConnector::class,
    ];

    /**
     * AI assistant connectors (SPEC-0024 / ADR-0018) — map theo **adapter** (loại
     * API), KHÔNG theo instance code. 1 connector phục vụ nhiều instance
     * (rows `ai_providers`); registry inject instance `code` lúc resolve.
     *
     * adapter:
     *   anthropic         → Claude (Messages API)
     *   openai_compatible → OpenAI/DeepSeek/Qwen/OpenRouter/Gemini (Chat Completions)
     *   custom_http       → endpoint HTTP tuỳ chỉnh, super-admin khai báo template (SPEC-0026)
     *   manual            → deterministic stub (test/dev, free)
     *
     * Activation đọc từ bảng `ai_providers` (super-admin quản qua /admin/ai-providers).
     *
     * @var array<string, class-string>
     */
    protected array $aiAssistantConnectors = [
        'anthropic' => ClaudeConnector::class,
        'openai_compatible' => OpenAiConnector::class,
        'custom_http' => CustomHttpConnector::class,
        'manual' => ManualAiAssistantConnector::class,
    ];

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/integrations.php', 'integrations');

        $this->app->singleton(ChannelRegistry::class, function ($app) {
            $registry = new ChannelRegistry($app);
            foreach ((array) config('integrations.channels', []) as $code) {
                if (isset($this->channelConnectors[$code])) {
                    $registry->register($code, $this->channelConnectors[$code]);
                }
            }

            return $registry;
        });

        $this->app->singleton(PublisherRegistry::class, function ($app) {
            $r = new PublisherRegistry($app);
            $r->register('lazada', LazadaPublisher::class);
            $r->register('tiktok', TikTokPublisher::class);
            $r->register('shopee', ShopeePublisher::class);

            return $r;
        });

        $this->app->singleton(CarrierRegistry::class, function ($app) {
            $registry = new CarrierRegistry($app);
            // A1 fix — luôn register TẤT CẢ carrier có code class; env `INTEGRATIONS_CARRIERS` chỉ là
            // override cho test environment (vd test chạy GHN connector mà KHÔNG có thật env GHN). Trước
            // đây env trống ⇒ chỉ load 'manual' ⇒ user mở Settings/Carriers KHÔNG chọn được GHN.
            // Đơn vị vận chuyển muốn dùng vẫn phải nhập API token ở Account-level — không có rủi ro nếu
            // shop chưa cấu hình credentials.
            $envFilter = array_filter(array_map('trim', (array) config('integrations.carriers', [])));
            $codes = $envFilter !== []
                ? array_unique(array_merge(['manual'], $envFilter))
                : array_keys($this->carrierConnectors);
            foreach ($codes as $code) {
                if (isset($this->carrierConnectors[$code])) {
                    $registry->register($code, $this->carrierConnectors[$code]);
                }
            }

            return $registry;
        });

        // Payment gateways (Phase 6.4 / SPEC 0018).
        $this->app->singleton(PaymentRegistry::class, function ($app) {
            $registry = new PaymentRegistry($app);
            foreach ((array) config('integrations.payments.enabled', []) as $code) {
                $code = trim((string) $code);
                if ($code !== '' && isset($this->paymentConnectors[$code])) {
                    $registry->register($code, $this->paymentConnectors[$code]);
                }
            }

            return $registry;
        });

        // Hóa đơn điện tử (SPEC 0041).
        $this->app->singleton(EInvoiceRegistry::class, function ($app) {
            $registry = new EInvoiceRegistry($app);
            foreach ((array) config('integrations.einvoice.enabled', []) as $code) {
                $code = trim((string) $code);
                if ($code !== '' && isset($this->einvoiceConnectors[$code])) {
                    $registry->register($code, $this->einvoiceConnectors[$code]);
                }
            }

            return $registry;
        });

        $this->app->bind(MisaMeInvoiceConnector::class, fn () => new MisaMeInvoiceConnector(
            (array) config('integrations.einvoice.misa', [])
        ));

        // FacebookPageConnector cần config block (không auto-resolve được array) —
        // bind tường minh; verifier auto-resolve.
        $this->app->bind(FacebookPageConnector::class, function ($app) {
            return new FacebookPageConnector(
                (array) config('integrations.messaging_facebook_page', []),
                $app->make(FacebookSignatureVerifier::class),
            );
        });

        // ShopeeChatConnector cần config block + ShopeeClient/Verifier (Channels) — bind tường minh.
        $this->app->bind(ShopeeChatConnector::class, function ($app) {
            return new ShopeeChatConnector(
                (array) config('integrations.shopee', []),
                $app->make(ShopeeWebhookVerifier::class),
                $app->make(ShopeeClient::class),
            );
        });

        // ZaloOaConnector cần config block + ZaloSignatureVerifier + ZaloClient — bind tường minh.
        $this->app->bind(ZaloOaConnector::class, function ($app) {
            return new ZaloOaConnector(
                (array) config('integrations.messaging_zalo_oa', []),
                $app->make(ZaloSignatureVerifier::class),
                $app->make(ZaloClient::class),
            );
        });

        // Messaging (Phase 7.x đề xuất / SPEC-0024).
        // `manual` luôn nạp (test/dev); env `INTEGRATIONS_MESSAGING=facebook_page,tiktok_chat,...`
        // bật connector thật khi available.
        $this->app->singleton(MessagingRegistry::class, function ($app) {
            $registry = new MessagingRegistry($app);
            $envFilter = array_filter(array_map('trim', (array) config('integrations.messaging', [])));
            $codes = array_unique(array_merge(['manual'], $envFilter));
            foreach ($codes as $code) {
                if (isset($this->messagingConnectors[$code])) {
                    $registry->register($code, $this->messagingConnectors[$code]);
                }
            }

            return $registry;
        });

        // Ads (SPEC 2026-06-04 — Facebook Ads). Mirror MessagingRegistry: only providers
        // in `config('integrations.ads')` are registered (off by default ⇒ zero impact).
        $this->app->singleton(AdsRegistry::class, function ($app) {
            $registry = new AdsRegistry($app);
            foreach (array_filter(array_map('trim', (array) config('integrations.ads', []))) as $code) {
                if (isset($this->adsConnectors[$code])) {
                    $registry->register($code, $this->adsConnectors[$code]);
                }
            }

            return $registry;
        });

        $this->app->bind(FacebookAdsConnector::class, function () {
            return new FacebookAdsConnector((array) config('integrations.ads_facebook', []));
        });

        $this->app->bind(TikTokAdsConnector::class, function () {
            return new TikTokAdsConnector((array) config('integrations.ads_tiktok', []));
        });

        // AI Assistant (SPEC-0024). Register adapter (anthropic/openai_compatible/
        // manual); credentials đọc từ bảng `ai_providers`. Registry resolve theo
        // adapter của instance code + inject code vào connector.
        $this->app->singleton(AiAssistantRegistry::class, function ($app) {
            $registry = new AiAssistantRegistry($app);
            foreach ($this->aiAssistantConnectors as $adapter => $class) {
                $registry->register($adapter, $class);
            }

            return $registry;
        });

        // Connectors inject config — bind explicitly để DI biết tham số mảng.
        $this->app->bind(SePayConnector::class, fn () => new SePayConnector(
            (array) config('integrations.payments.sepay', [])
        ));
        $this->app->bind(VnPayConnector::class, fn () => new VnPayConnector(
            (array) config('integrations.payments.vnpay', [])
        ));
        $this->app->bind(MomoConnector::class, fn () => new MomoConnector(
            (array) config('integrations.payments.momo', [])
        ));

        // Visual search (2026-06-16) — trục Vector + Image Embedding (vendor-agnostic).
        // Tách RIÊNG Qdrant help-bot (Support); core không biết tên vendor.
        $this->app->singleton(VectorStoreRegistry::class, function ($app) {
            $registry = new VectorStoreRegistry($app);
            $registry->register('qdrant', QdrantStore::class);

            return $registry;
        });
        $this->app->bind(VectorStore::class, fn ($app) => $app->make(VectorStoreRegistry::class)->default());

        $this->app->singleton(ImageEmbedderRegistry::class, function ($app) {
            $registry = new ImageEmbedderRegistry($app);
            $registry->register('clip', ClipEmbedder::class);

            return $registry;
        });
        $this->app->bind(ImageEmbedder::class, fn ($app) => $app->make(ImageEmbedderRegistry::class)->default());

        // Pancake POS — báo cáo "bom hàng" cho đơn thủ công (SPEC 0038). Cấu hình GLOBAL
        // (system_setting `integrations.pancake.*`), fallback config/env. Provider fail-soft.
        $this->app->bind(
            CustomerBadReportProvider::class,
            fn () => new PancakeBadReportProvider((array) config('integrations.pancake', [])),
        );
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../../config/integrations.php' => config_path('integrations.php'),
        ], 'config');
    }
}
