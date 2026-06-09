---
title: "Data latency for reports"
doc_id: 1738864894606337
path: "Marketing API / Reporting / Guides / Data latency for reports"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Data latency for reports

There might be a latency for data fields in our reports. Be aware of this when creating a report via Reporting APIs. How long the latency will last depends on the data type you want to run a report on and the exact data fields. 

## Two data sources

Data latency arises from the two types of data (Real-time and Offline data) on TikTok Ads Manager, and their refreshing mechanism. 

Real-time Data are refreshed actively to ensure timely data release. Also, Realtime Data will be adjusted the following day to align with Offline Data.

Offline Data are refreshed less actively compared with Real-time Data. When Offline Data are refreshed and released, the necessary processing  (such as data deduplication) has been finished, and no more modifications are needed. The value for field `reach` is estimated and all other Offline Data are accurate.

> <span style="color:DodgerBlue">**Note**</span><br>
>   <ul><li>Reporting API data is intended solely for reporting purposes. If you would like to implement a use case such as Automated Rules, check the [Automated Rules API](https://business-api.tiktok.com/portal/docs?id=1738763978290178) first.</li><li>For customized use cases or Automated Rules, [contact TikTok API for Business team](https://ads.tiktok.com/athena/requester/boards?identify_key=b25033ef7782d41050cbac74f751d547e9d8ad35a39a541a0f0efbbd51fe722f) for better guidance and technical support.</li></ul>


## Data latency levels

Data latencies on the TikTok Ads Manager platform fall into three categories: day-level, hour-level, and minute-level.

### Day-level data latency

The following type of data is released as Offline Data, and they usually have a latency of 48 hours:

<strong>In Business Center reports</strong>:
- The data of the metric `tax_spend`.


### Hour-level data latency

The following types of data are released as Offline Data, and they usually have a latency of 16 hours:

<strong>In basic reports</strong>:
- Unique visitor (UV) metric data, and the data of `voucher_spend` and `cash_spend` when you use any dimension.
- All data when you use any of the following dimensions:
  - The Targeting dimension `ad_type`
  - The Asset dimensions `custom_event_type` , `event_source_id`, `page_id`, `component_name`, and `post_id`
  - The Search dimension `search_terms`

> <span style="color:DodgerBlue">**Note**</span><br>
>UV metrics include:<ul><li>`reach`, `cost_per_1000_reached`, </li><li>`frequency`, and</li><li>metrics related to results when `optimization_goal`=`REACH` :<ul><li>`result`, `cost_per_result`, `result_rate`, </li><li>`real_time_result`, `real_time_cost_per_result`, `real_time_result_rate`, </li><li>`secondary_goal_result`, `cost_per_secondary_goal_result`, `secondary_goal_result_rate`,</li><li>`skan_result`, `skan_cost_per_result`, `skan_result_rate`.</li></ul></li></ul>

<strong>In audience reports</strong>:
  
- Unique visitor (UV) metric data when you use any dimension.
- The metric data when you use any of the following Targeting dimensions:
  - `platform`
  - `device_brand_id`
  - `ad_type`
  - `gender`
  - `province_id`
  - `dma_id`
  - `age`
  - `language`
  - `interest_category`
  - `interest_category_tier2`
  - `interest_category_tier3`
  - `interest_category_tier4`
  - `interest_category`
  - `hashtag`
  - `audience_tags`
  - `additional_interests`
  - `ac`
  - `contextual_tag`

> <span style="color:DodgerBlue">**Note**</span><br>
> When you use the Targeting dimension `behavior_id` or `purchase_intention`, the latency of your metric data is still hour-level but will be 24 hours.

<strong>In  playable ad reports</strong>:

- All data.

<strong>In DSA reports</strong>:

- All data.

<strong>In Business Center reports</strong>:
- The data of the metrics `cash_spend`, `voucher_spend`, and `cashback_coupon_spend`.

### Minute-level data latency

The following type of data is released as Real-time Data, and usually has a latency of 30 minutes:

> <span style="color:DodgerBlue">**Note**</span><br>
> In extreme cases, the latency of Real-time Data may reach or exceed two hours.

<strong>In basic reports</strong>:
- The metric data excluding the data of `voucher_spend` and `cash_spend` and excluding UV metric data when you don't use the Targeting dimension `ad_type`, the Asset dimensions `custom_event_type`, `event_source_id`, `page_id`, `component_name`, and `post_id`, and the Search dimension `search_terms`.

<strong>In audience reports:</strong>
- The metric data excluding the data of UV metrics when you use the Targeting dimension `country_code` or `placement`.

<strong>In Business Center reports</strong>:
- The metric data excluding the data of `cash_spend`, `voucher_spend`, `cashback_coupon_spend`, and `tax_spend`.

<strong>In GMV max ads reports</strong>:
- All metric data.

> <span style="color:DodgerBlue">**Note**</span><br> Real-time Data are subject to minor modifications. A data fix script is executed every day at 12:00 (UTC+0), so you are recommended to pull data after 12:00 (UTC+0).


## Reference table for data latency

To quickly see the expected latency for the data type you want to run a certain type of report on, check the reference table below.

```xtable
|Report type{20%}|Data category{60%}|Latency{20%}|
|---|---|---|
|Basic report|When you use any dimension：<ul><li> Data of the UV metrics (listed below): <ul><li>`reach`, `cost_per_1000_reached`, </li><li>`frequency`, and</li><li>metrics related to results when `optimization_goal`=`REACH` :<ul><li>`result`, `cost_per_result`, `result_rate`, </li><li>`real_time_result`, `real_time_cost_per_result`, `real_time_result_rate`, </li><li>`secondary_goal_result`, `cost_per_secondary_goal_result`, `secondary_goal_result_rate`,</li><li>`skan_result`, `skan_cost_per_result`, `skan_result_rate`.</li></ul></li></ul></li><li> Data of `voucher_spend` & `cash_spend`.</li></ul>|16 hours|
|Basic report|All data when you use any of the following dimensions:<ul><li>The Targeting dimension `ad_type`</li><li>The Asset dimensions `custom_event_type`, `event_source_id`, `page_id`, `component_name`, and `post_id`</li><li>The Search dimension `search_terms`</li></ul> |16 hours|
|Basic report| When you <strong>don’t</strong>  use the Targeting dimension `ad_type`, or the Asset dimensions `custom_event_type`, `event_source_id`, `page_id`, `component_name`, and `post_id`, or the Search dimension `search_terms` in `dimensions`: <ul><li>The metric data excluding the data of UV metrics, `voucher_spend`, and `cash_spend`. </li></ul>|30 minutes|
|Audience report|The metric data when you use the Targeting dimension `behavior_id` or `purchase_intention`.|24 hours|
|Audience report| <ul><li>Unique visitor (UV) metric data when you use any dimension.</li><li>The metric data when you use any of the following Targeting dimensions: <ul><li>`platform`</li><li>`device_brand_id`</li><li>`ad_type`</li><li>`gender`</li><li>`province_id`</li><li>`dma_id`</li><li>`age`</li><li>`language`</li><li>`interest_category`</li><li>`interest_category_tier2`</li><li>`interest_category_tier3`</li><li>`interest_category_tier4`</li><li>`interest_category`</li><li>`hashtag`</li><li>`audience_tags`</li><li>`additional_interests`</li><li>`ac`</li><li>`contextual_tag`</li></ul></li></ul> |16 hours|
|Audience report| Non-UV metric data when you use the targeting dimension `country_code` or `placement`.|30 minutes|
|Playable ad report| All data.|16 hours|
|DSA report| All data.|16 hours|
| Business Center report | Data of the metric `tax_spend`. | 48 hours |
| Business Center report | Data of the metrics `cash_spend`, `voucher_spend`, and `cashback_coupon_spend`. | 16 hours |
| Business Center report | The metric data excluding the data of `cash_spend`, `voucher_spend`, `cashback_coupon_spend`, and `tax_spend`. | 30 minutes |
| GMV max ads report | All data. | 30 minutes |
```

