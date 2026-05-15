<?php

use CMBcoreSeller\Integrations\IntegrationsServiceProvider;
use CMBcoreSeller\Modules\Accounting\AccountingServiceProvider;
use CMBcoreSeller\Modules\Billing\BillingServiceProvider;
use CMBcoreSeller\Modules\Channels\ChannelsServiceProvider;
use CMBcoreSeller\Modules\Customers\CustomersServiceProvider;
use CMBcoreSeller\Modules\Finance\FinanceServiceProvider;
use CMBcoreSeller\Modules\Fulfillment\FulfillmentServiceProvider;
use CMBcoreSeller\Modules\Inventory\InventoryServiceProvider;
use CMBcoreSeller\Modules\Orders\OrdersServiceProvider;
use CMBcoreSeller\Modules\Procurement\ProcurementServiceProvider;
use CMBcoreSeller\Modules\Products\ProductsServiceProvider;
use CMBcoreSeller\Modules\Reports\ReportsServiceProvider;
use CMBcoreSeller\Modules\Settings\SettingsServiceProvider;
use CMBcoreSeller\Modules\Tenancy\TenancyServiceProvider;
use CMBcoreSeller\Providers\AppServiceProvider;
use CMBcoreSeller\Providers\HorizonServiceProvider;

return [
    AppServiceProvider::class,
    HorizonServiceProvider::class,

    // Integration layer (channel & carrier registries + connectors).
    IntegrationsServiceProvider::class,

    // Domain modules — see docs/01-architecture/modules.md.
    TenancyServiceProvider::class,
    ChannelsServiceProvider::class,
    OrdersServiceProvider::class,
    CustomersServiceProvider::class,
    InventoryServiceProvider::class,
    ProductsServiceProvider::class,
    FulfillmentServiceProvider::class,
    ProcurementServiceProvider::class,
    FinanceServiceProvider::class,
    ReportsServiceProvider::class,
    BillingServiceProvider::class,
    AccountingServiceProvider::class,
    SettingsServiceProvider::class,
];
