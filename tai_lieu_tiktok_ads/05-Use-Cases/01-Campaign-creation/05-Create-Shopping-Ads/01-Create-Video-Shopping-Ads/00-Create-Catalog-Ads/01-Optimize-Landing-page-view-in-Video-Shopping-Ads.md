---
title: "Optimize Landing page view in Video Shopping Ads"
doc_id: 1775099621140482
path: "Use Cases / Campaign creation / Create Shopping Ads / Create Video Shopping Ads / Create Catalog Ads / Optimize Landing page view in Video Shopping Ads"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Optimize Landing page view in Video Shopping Ads

This article walks you through the steps to use the optimization goal Landing page view in Video Shopping Ads.


## Prerequisites
- Using the optimization goal Landing page view in Shopping Ads is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.

## Steps

### 1. Create a campaign

Create a campaign by using [/campaign/create/](https://ads.tiktok.com/marketing_api/docs?id=1739318962329602). The parameter requirements listed in the section [Create Video Shopping Ads with products from catalogs-1. Create a campaign](https://ads.tiktok.com/marketing_api/docs?id=1750361698613249#item-link-1.%20Create%20a%20campaign) must be met.

#### Example

Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/campaign/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "objective_type": "PRODUCT_SALES",
    "campaign_product_source": "CATALOG",
    "budget_mode":"BUDGET_MODE_TOTAL",
    "budget": {{budget}},
    "campaign_name":"{{campaign_name}}"
}'
(/code)
```

Response
```xcodeblock
(code curl http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "budget_optimize_on": false,
        "modify_time": "{{modify_time}}",
        "operation_status": "ENABLE",
        "budget": {{budget}},
        "objective_type": "PRODUCT_SALES",
        "secondary_status": "CAMPAIGN_STATUS_ENABLE",
        "budget_mode": "BUDGET_MODE_TOTAL",
        "campaign_name": "{{campaign_name}}",
        "is_new_structure": true,
        "campaign_type": "REGULAR_CAMPAIGN",
        "campaign_app_profile_page_state": "UNSET",
        "advertiser_id": "{{advertiser_id}}",
        "deep_bid_type": null,
        "roas_bid": 0,
        "campaign_id": "{{campaign_id}}",
        "campaign_product_source": "CATALOG",
        "objective": "LANDING_PAGE",
        "is_smart_performance_campaign": false,
        "create_time": "{{create_time}}"
    }
}
(/code)
```

### 2. Create and manage catalogs

Follow the steps outlined in the section [Create Video Shopping Ads with products from catalogs-2. Create and manage catalogs](https://ads.tiktok.com/marketing_api/docs?id=1750361698613249#item-link-2.%20Create%20and%20manage%20catalogs).

### 3. Create an ad group

Create an ad group by using [/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114). Note that in addition to the requirements listed in the section [Create Video Shopping Ads with products from catalogs-3. Create an ad group](https://ads.tiktok.com/marketing_api/docs?id=1750361698613249#item-link-3.%20Create%20an%20ad%20group), the following requirements must be met at the same time.

<table>
<colgroup>
<col style="width: 20%">
<col style="width: 20%">
<col style="width: 20%">
<col style="width: 40%">
</colgroup>
<thead>
  <tr>
    <th>Setting</th>
    <th>Requirement</th>
    <th>Parameter</th>
    <th>How to configure the parameter</th>
  </tr>
</thead>
<tbody>
  <tr>
    <td>Promotion type <br> (Optimization location)</td>
    <td>Website</td>
	<td><code>promotion_type</code></td>
    <td><code>WEBSITE</code></td>
  </tr>
  <tr>
    <td>Pixel</td>
    <td>Not supported</td>
    <td><code>pixel_id</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Placement</td>
    <td><ul><li><strong>Automatic Placement</strong> or</li><li><strong>Select Placement</strong> with TikTok placement</li></ul></td>
    <td><ul><li><code>placement_type</code></li><li> <code>placements</code></li></ul></td>
    <td><ul><li>Set <code>placement_type</code> to <code>PLACEMENT_TYPE_AUTOMATIC</code></li><li>OR set <code>placement_type</code> to <code>PLACEMENT_TYPE_NORMAL</code>, and set <code>placements</code> to <code>["PLACEMENT_TIKTOK"]</code>.</li></ul></td>
  </tr>
  <tr>
    <td>Bid strategy & Delivery Type<br></td>
    <td><ul><li>If Bid Strategy is set to Cost Cap, you can set Delivery Type to Standard or Accelerated.</li><li>If Bid Strategy is set to Maximum Delivery, you can only set Delivery Type to Standard.</li></ul></td>
    <td> <ul><li><code>bid_type</code></li><li><code>pacing</code></li></ul></td>
    <td><ul><li>If you specify <code>bid_type</code> as <code>BID_TYPE_CUSTOM</code>, you can set <code>pacing</code> to <code>PACING_MODE_SMOOTH</code> or <code>PACING_MODE_FAST</code>.</li><li>If you specify <code>bid_type</code> as <code>BID_TYPE_NO_BID</code>, you can only set <code>pacing</code> to <code>PACING_MODE_SMOOTH</code>.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: The Bidding Strategy Cost Cap (<code>BID_TYPE_CUSTOM</code>) in this case is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</td>
  </tr>
  <tr>
    <td>Target cost per Landing page view<br></td>
    <td>Specified when Bid Strategy is set to Cost Cap.</td>
    <td><code>conversion_bid_price</code></td>
    <td>If you specify <code>bid_type</code> as <code>BID_TYPE_CUSTOM</code>, you need to pass this field.</td>
  </tr>
  <tr>
    <td rowspan="2">Optimization goal</td>
    <td rowspan="2">Landing page view<br></td>
    <td><code>optimization_goal</code></td>
    <td><code>TRAFFIC_LANDING_PAGE_VIEW</code></td>
  </tr>
  <tr>
    <td><code>optimization_event</code></td>
    <td><code>LANDING_PAGE_VIEW</code></td>
  </tr>
  <tr>
    <td>Billing event</td>
    <td>oCPM</td>
    <td><code>billing_event</code></td>
    <td><code>OCPM</code></td>
  </tr>
</tbody>
</table>

#### Example

Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/adgroup/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "adgroup_name": "{{adgroup_name}}",
    "product_source": "CATALOG",
    "catalog_id": "{{catalog_id}}",
    "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
    "promotion_type": "WEBSITE",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": [
        "PLACEMENT_TIKTOK"
    ],
    "video_download_disabled": true,
    "shopping_ads_retargeting_type": "OFF",
    "location_ids": ["{{location_id}}"],
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "optimization_goal": "TRAFFIC_LANDING_PAGE_VIEW",
    "optimization_event": "LANDING_PAGE_VIEW",
    "bid_type": "BID_TYPE_NO_BID",
    "billing_event": "OCPM",
    "pacing": "PACING_MODE_SMOOTH"
}'
(/code)
```

Response
```xcodeblock
(code curl http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "pixel_id": null,
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "gender": "GENDER_UNLIMITED",
        "is_hfss": false,
        "adgroup_id": "{{adgroup_id}}",
        "category_id": "0",
        "schedule_start_time": "{{schedule_start_time}}",
        "purchased_impression": null,
        "excluded_audience_ids": [],
        "isp_ids": [],
        "billing_event": "OCPM",
        "brand_safety_partner": null,
        "skip_learning_phase": true,
        "audience_ids": [],
        "secondary_optimization_event": null,
        "device_price_ranges": null,
        "frequency": null,
        "adgroup_name": "{{adgroup_name}}",
        "advertiser_id": "{{advertiser_id}}",
        "optimization_event": "LANDING_PAGE_VIEW",
        "interest_category_ids": [],
        "shopping_ads_type": "VIDEO",
        "scheduled_budget": 0,
        "statistic_type": null,
        "feed_type": null,
        "schedule_type": "SCHEDULE_START_END",
        "budget": 0,
        "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
        "category_exclusion_ids": [],
        "adgroup_app_profile_page_state": null,
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "schedule_end_time": "{{schedule_end_time}}",
        "ios14_quota_type": "UNOCCUPIED",
        "inventory_filter_enabled": false,
        "actions": [],
        "app_download_url": null,
        "share_disabled": false,
        "product_source": "CATALOG",
        "purchased_reach": null,
        "create_time": "{{create_time}}",
        "operating_systems": [],
        "pacing": "PACING_MODE_SMOOTH",
        "budget_mode": "BUDGET_MODE_INFINITE",
        "rf_purchased_type": null,
        "search_result_enabled": false,
        "creative_material_mode": "CATALOG_SALES",
        "shopping_ads_retargeting_type": "OFF",
        "auto_targeting_enabled": false,
        "frequency_schedule": null,
        "keywords": null,
        "deep_cpa_bid": 0,
        "campaign_name": "{{campaign_name}}",
        "interest_keyword_ids": [],
        "languages": [],
        "conversion_window": null,
        "video_download_disabled": true,
        "bid_type": "BID_TYPE_NO_BID",
        "schedule_infos": null,
        "delivery_mode": null,
        "catalog_id": "{{catalog_id}}",
        "bid_price": 0,
        "promotion_type": "WEBSITE",
        "modify_time": "{{modify_time}}",
        "next_day_retention": null,
        "operation_status": "ENABLE",
        "comment_disabled": false,
        "conversion_bid_price": 0,
        "rf_estimated_cpr": null,
        "location_ids": [
            "{{location_id}}"
        ],
        "brand_safety_type": "NO_BRAND_SAFETY",
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "rf_estimated_frequency": null,
        "network_types": [],
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "age_groups": null,
        "app_id": null,
        "is_new_structure": true,
        "campaign_id": "{{campaign_id}}",
        "app_type": null,
        "optimization_goal": "TRAFFIC_LANDING_PAGE_VIEW",
        "bid_display_mode": "CPMV",
        "deep_bid_type": null
    }
}
(/code)
```
### 4. Create an ad
Create an ad by using [/ad/create/](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354). Note that in addition to the requirements listed in the section [Create Video Shopping Ads with products from catalogs-4. Create an ad](https://ads.tiktok.com/marketing_api/docs?id=1750361698613249#item-link-4.%20Create%20an%20ad), the following requirements must be met.

```xtable
| Setting {20%}| Requirement {20%}| Parameter{30%} | How to configure the parameter {30%}|
|---|---|---|---|
| Dynamic destination | Disabled | `dynamic_destination` | `UNSET` or not passed |
```

