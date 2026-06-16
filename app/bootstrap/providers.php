<?php

use CMBcoreSeller\Integrations\IntegrationsServiceProvider;
use CMBcoreSeller\Modules\Accounting\AccountingServiceProvider;
use CMBcoreSeller\Modules\Admin\AdminServiceProvider;
use CMBcoreSeller\Modules\Billing\BillingServiceProvider;
use CMBcoreSeller\Modules\Channels\ChannelsServiceProvider;
use CMBcoreSeller\Modules\Customers\CustomersServiceProvider;
use CMBcoreSeller\Modules\Finance\FinanceServiceProvider;
use CMBcoreSeller\Modules\Fulfillment\FulfillmentServiceProvider;
use CMBcoreSeller\Modules\Inventory\InventoryServiceProvider;
use CMBcoreSeller\Modules\Marketing\MarketingServiceProvider;
use CMBcoreSeller\Modules\Messaging\MessagingServiceProvider;
use CMBcoreSeller\Modules\Notifications\NotificationsServiceProvider;
use CMBcoreSeller\Modules\Orders\OrdersServiceProvider;
use CMBcoreSeller\Modules\Procurement\ProcurementServiceProvider;
use CMBcoreSeller\Modules\Products\ProductsServiceProvider;
use CMBcoreSeller\Modules\Reports\ReportsServiceProvider;
use CMBcoreSeller\Modules\Settings\SettingsServiceProvider;
use CMBcoreSeller\Modules\Support\SupportServiceProvider;
use CMBcoreSeller\Modules\Tenancy\TenancyServiceProvider;
use CMBcoreSeller\Modules\VisualSearch\VisualSearchServiceProvider;
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
    AdminServiceProvider::class,
    NotificationsServiceProvider::class,
    // SPEC-0024 (Phase 7.x đề xuất) — Omnichannel Messaging foundation (S1).
    MessagingServiceProvider::class,
    // SPEC-0028 — Trợ lý trợ giúp sản phẩm (RAG help-bot) + yêu cầu CSKH.
    SupportServiceProvider::class,
    // SPEC 2026-06-16 — Visual training & tìm sản phẩm bằng ảnh (Qdrant + CLIP).
    VisualSearchServiceProvider::class,
    // SPEC 2026-06-04 — Facebook Ads near-real-time + AI optimization.
    MarketingServiceProvider::class,
];
