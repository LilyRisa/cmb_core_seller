<?php

namespace CMBcoreSeller\Integrations;

use CMBcoreSeller\Integrations\Carriers\CarrierRegistry;
use CMBcoreSeller\Integrations\Carriers\Ghn\GhnConnector;
use CMBcoreSeller\Integrations\Carriers\Manual\ManualCarrierConnector;
use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Integrations\Channels\Lazada\LazadaConnector;
use CMBcoreSeller\Integrations\Channels\Manual\ManualConnector;
use CMBcoreSeller\Integrations\Channels\TikTok\TikTokConnector;
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
