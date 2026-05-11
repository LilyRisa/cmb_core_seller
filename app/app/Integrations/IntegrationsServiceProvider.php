<?php

namespace CMBcoreSeller\Integrations;

use CMBcoreSeller\Integrations\Carriers\CarrierRegistry;
use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Integrations\Channels\Manual\ManualConnector;
use CMBcoreSeller\Integrations\Channels\TikTok\TikTokConnector;
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
        // 'shopee' => \CMBcoreSeller\Integrations\Channels\Shopee\ShopeeConnector::class,
        // 'lazada' => \CMBcoreSeller\Integrations\Channels\Lazada\LazadaConnector::class,
    ];

    /**
     * Known carrier connectors. Loaded per config('integrations.carriers').
     *
     * @var array<string, class-string>
     */
    protected array $carrierConnectors = [
        // 'ghn'  => \CMBcoreSeller\Integrations\Carriers\Ghn\GhnConnector::class,
        // 'ghtk' => \CMBcoreSeller\Integrations\Carriers\Ghtk\GhtkConnector::class,
        // 'jt'   => \CMBcoreSeller\Integrations\Carriers\JtExpress\JtExpressConnector::class,
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
            foreach ((array) config('integrations.carriers', []) as $code) {
                if (isset($this->carrierConnectors[$code])) {
                    $registry->register($code, $this->carrierConnectors[$code]);
                }
            }

            return $registry;
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../../config/integrations.php' => config_path('integrations.php'),
        ], 'config');
    }
}
