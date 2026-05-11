<?php

return [
    CMBcoreSeller\Providers\AppServiceProvider::class,

    // Integration layer (channel & carrier registries + connectors).
    CMBcoreSeller\Integrations\IntegrationsServiceProvider::class,

    // Domain modules — see docs/01-architecture/modules.md.
    CMBcoreSeller\Modules\Tenancy\TenancyServiceProvider::class,
    CMBcoreSeller\Modules\Channels\ChannelsServiceProvider::class,
    CMBcoreSeller\Modules\Orders\OrdersServiceProvider::class,
    CMBcoreSeller\Modules\Inventory\InventoryServiceProvider::class,
    CMBcoreSeller\Modules\Products\ProductsServiceProvider::class,
    CMBcoreSeller\Modules\Fulfillment\FulfillmentServiceProvider::class,
    CMBcoreSeller\Modules\Procurement\ProcurementServiceProvider::class,
    CMBcoreSeller\Modules\Finance\FinanceServiceProvider::class,
    CMBcoreSeller\Modules\Reports\ReportsServiceProvider::class,
    CMBcoreSeller\Modules\Billing\BillingServiceProvider::class,
    CMBcoreSeller\Modules\Settings\SettingsServiceProvider::class,
];
