---
title: "Upgrade from Product Sales or Website Conversions to the Sales objective"
doc_id: 1834189985160193
path: "Marketing API / Campaign Management / Guides / Campaign / Advertising objective / Upgrade from Product Sales or Website Conversions to the Sales objective"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Upgrade from Product Sales or Website Conversions to the Sales objective

This article provides guidance and required field mappings to upgrade your [Website Conversions ads](https://business-api.tiktok.com/portal/docs?id=1775548501843970) or [Shopping Ads](https://business-api.tiktok.com/portal/docs?id=1741942179356738) to use the Sales objective structure.

# Upgrade overview
**Upgrading to the Sales objective does not affect your existing ad group or ad configurations, nor will it impact ad delivery results. This change only modifies the way your campaigns are displayed in [TikTok Ads Manager](https://ads.tiktok.com/i18n/login).**

To upgrade to the Sales objective, you need to add two new fields at the Campaign level:
- `virtual_objective_type` (set to `SALES`)
- `sales_destination`. The specific value depends on your current configuration (TikTok Shop, Website, or App).
 
# Steps
## Upgrade the Product Sales objective 
### Product source as TikTok Shop

``` xtable
| Level {20%}| Before upgrade{35%} | After upgrade (add fields){45%} |
|---|---|---|
| Campaign | <ul><li>`objective_type` is set to `PRODUCT_SALES`</li><li>`campaign_product_source` is set to `STORE`</li></ul> | <ul><li><b>`virtual_objective_type` is set to `SALES`</b></li><li><b>`sales_destination` is set to `TIKTOK_SHOP`</b></li><li>`objective_type` is set to `PRODUCT_SALES`</li><li>`campaign_product_source` is set to `STORE`</li></ul> |
``` 
### Product source as Catalog and optimization location as Website
``` xtable
| Level {20%}| Before upgrade{35%} | After upgrade (add fields){45%} |
|---|---|---|
| Campaign | <ul><li>`objective_type` is set to `PRODUCT_SALES`</li><li>`campaign_product_source` is set to `CATALOG`</li></ul> | <ul><li><b>`virtual_objective_type` is set to `SALES`</b></li><li><b>`sales_destination` is set to `WEBSITE`</b></li><li>`objective_type` is set to `PRODUCT_SALES`</li><li>`campaign_product_source` is set to `CATALOG`</li></ul> |
| Ad group | <ul><li>`promotion_type` is set to `WEBSITE`</li></ul> | <ul><li>`promotion_type` is set to `WEBSITE`</li></ul> |
```
### Product source as Catalog and optimization location as App
``` xtable
| Level {20%}| Before upgrade{35%} | After upgrade (add fields){45%} |
|---|---|---|
| Campaign | <ul><li>`objective_type` is set to `PRODUCT_SALES`</li><li>`campaign_product_source` is set to `CATALOG`</li></ul> | <ul><li><b>`virtual_objective_type` is set to `SALES`</b></li><li><b>`sales_destination` is set to `APP`</b></li><li>`objective_type` is set to `PRODUCT_SALES`</li><li>`campaign_product_source` is set to `CATALOG`</li></ul> |
| Ad group | <ul><li>`promotion_type` is set to `APP_ANDRIOD` or `APP_IOS`</li></ul> | <ul><li>`promotion_type` is set to `APP_ANDRIOD` or `APP_IOS`</li></ul> |
```
## Upgrade the Website Conversions objective 
``` xtable
| Level {20%}| Before upgrade{35%} | After upgrade (add fields){45%} |
|---|---|---|
| Campaign | <ul><li>`objective_type` is set to `WEB_CONVERSIONS`</li></ul> | <ul><li><b>`virtual_objective_type` is set to `SALES`</b></li><li><b>`sales_destination` is set to `WEBSITE`</b></li><li>`objective_type` is set to `WEB_CONVERSIONS`</li></ul> |
```

# Example: Upgrading a Website Conversions campaign to a Sales campaign

Before:
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/campaign/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "objective_type": "WEB_CONVERSIONS",
    "campaign_name": "{{campaign_name}}",
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}}
}'
(/code)
```

After:
```xcodeblock
(code http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/campaign/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    <span style="background-color: green;">"virtual_objective_type": "SALES",</span>
    <span style="background-color: green;">"sales_destination": "WEBSITE",</span>
    "objective_type": "WEB_CONVERSIONS",
    "campaign_name": "{{campaign_name}}",
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}}
}'
(/code)
```

