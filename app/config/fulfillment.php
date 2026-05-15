<?php

return [

    /*
    |--------------------------------------------------------------------------
    | When to deduct stock for an order
    |--------------------------------------------------------------------------
    | 'shipped' (default): stock leaves on handover / scan-to-pack (order -> shipped).
    | 'created': stock leaves the moment a shipment is created.
    | (v1 only implements 'shipped' — the actual deduction is driven by the order
    | status via OrderUpserted → ApplyOrderInventoryEffects; see SPEC 0003.)
    */
    'deduct_on' => env('FULFILLMENT_DEDUCT_ON', 'shipped'),

    /*
    | Default parcel weight (grams) when SKU weights are unknown.
    */
    'default_weight_grams' => (int) env('FULFILLMENT_DEFAULT_WEIGHT_GRAMS', 500),

    /*
    | Gotenberg (HTML→PDF, merge PDFs) base URL. Service `gotenberg` in the Docker stack.
    */
    'gotenberg_url' => env('GOTENBERG_URL', 'http://localhost:3000'),

    /*
    | GHN API base URL (override for sandbox if needed).
    */
    'ghn_base_url' => env('GHN_BASE_URL', 'https://online-gateway.ghn.vn'),

    /*
    | (SPEC 0021) Optional fallback GHN token cho master-data (provinces/districts/wards) khi
    | tenant chưa cấu hình GHN account. Master-data global — token nào hợp lệ cũng dùng được.
    */
    'ghn_bootstrap_token' => env('GHN_BOOTSTRAP_TOKEN'),

    /*
    | In ấn — khổ phiếu mặc định (mỗi đơn 1 trang). Tenant có thể đặt riêng ở tenant.settings.print.label_size.
    | Giá trị: A4 | A5 | A6 | 100x150mm | 80mm  (xem PrintTemplates::paperRule). SPEC 0006 §4.4 / 0013.
    */
    'print' => [
        'label_paper_size' => env('PRINT_LABEL_SIZE', 'A6'),
    ],

];
