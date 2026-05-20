<?php

namespace CMBcoreSeller\Integrations;

use CMBcoreSeller\Integrations\Carriers\CarrierRegistry;
use CMBcoreSeller\Integrations\Carriers\Ghn\GhnConnector;
use CMBcoreSeller\Integrations\Carriers\Manual\ManualCarrierConnector;
use CMBcoreSeller\Integrations\Ai\AiAssistantRegistry;
use CMBcoreSeller\Integrations\Ai\Manual\ManualAiAssistantConnector;
use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Integrations\Channels\Lazada\LazadaConnector;
use CMBcoreSeller\Integrations\Channels\Manual\ManualConnector;
use CMBcoreSeller\Integrations\Channels\TikTok\TikTokConnector;
use CMBcoreSeller\Integrations\Messaging\Manual\ManualMessagingConnector;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Integrations\Payments\Momo\MomoConnector;
use CMBcoreSeller\Integrations\Payments\PaymentRegistry;
use CMBcoreSeller\Integrations\Payments\SePay\SePayConnector;
use CMBcoreSeller\Integrations\Payments\VnPay\VnPayConnector;
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
        // 'shopee' => \CMBcoreSeller\Integrations\Channels\Shopee\ShopeeConnector::class,
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
        // 'ghtk' => \CMBcoreSeller\Integrations\Carriers\Ghtk\GhtkConnector::class,
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
        'facebook_page' => \CMBcoreSeller\Integrations\Messaging\Facebook\FacebookPageConnector::class,  // S2
        // 'tiktok_chat'   => \CMBcoreSeller\Integrations\Messaging\TikTok\TikTokChatConnector::class,     // S4
        // 'shopee_chat'   => \CMBcoreSeller\Integrations\Messaging\Shopee\ShopeeChatConnector::class,    // S4
        // 'lazada_chat'   => \CMBcoreSeller\Integrations\Messaging\Lazada\LazadaChatConnector::class,    // S8 (best-effort)
    ];

    /**
     * AI assistant connectors (Phase 7.x đề xuất — SPEC-0024 / ADR-0018).
     * KHÁC các registry khác: enable/disable đọc từ DB `system_settings.ai_providers.<code>.is_active`
     * thay vì config — super-admin bật/tắt runtime mà không deploy.
     *
     * S1: chỉ có `manual` (deterministic, free). S6 sẽ thêm Claude/OpenAI/Gemini.
     *
     * @var array<string, class-string>
     */
    protected array $aiAssistantConnectors = [
        'manual' => ManualAiAssistantConnector::class,
        // Claude/OpenAI: capability đầy đủ; live HTTP call ném UnsupportedOperation
        // cho tới khi wire (S6.1). Đăng ký sẵn để super-admin cấu hình credentials
        // trong /admin/ai-providers; tenant chọn được khi is_active.
        'claude' => \CMBcoreSeller\Integrations\Ai\Claude\ClaudeConnector::class,
        'openai' => \CMBcoreSeller\Integrations\Ai\OpenAi\OpenAiConnector::class,
        // 'gemini' => \CMBcoreSeller\Integrations\Ai\Gemini\GeminiConnector::class,        // S6.1
        // 'local_llm' => \CMBcoreSeller\Integrations\Ai\LocalLlm\LocalLlmConnector::class,  // S6.1
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

        // FacebookPageConnector cần config block (không auto-resolve được array) —
        // bind tường minh; verifier auto-resolve.
        $this->app->bind(\CMBcoreSeller\Integrations\Messaging\Facebook\FacebookPageConnector::class, function ($app) {
            return new \CMBcoreSeller\Integrations\Messaging\Facebook\FacebookPageConnector(
                (array) config('integrations.messaging_facebook_page', []),
                $app->make(\CMBcoreSeller\Integrations\Messaging\Facebook\FacebookSignatureVerifier::class),
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

        // AI Assistant (Phase 7.x đề xuất / SPEC-0024). Activation đọc DB —
        // registry chỉ chứa class map; `for($code)` check `system_settings` trước
        // khi resolve.
        $this->app->singleton(AiAssistantRegistry::class, function ($app) {
            $registry = new AiAssistantRegistry($app);
            foreach ($this->aiAssistantConnectors as $code => $class) {
                $registry->register($code, $class);
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
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../../config/integrations.php' => config_path('integrations.php'),
        ], 'config');
    }
}
