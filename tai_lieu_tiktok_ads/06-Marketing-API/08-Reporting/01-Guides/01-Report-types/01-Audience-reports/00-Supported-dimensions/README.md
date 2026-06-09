---
title: "Supported dimensions"
doc_id: 1751454103714818
path: "Marketing API / Reporting / Guides / Report types / Audience reports / Supported dimensions"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Supported dimensions

A dimension is an attribute to group your data by. Audience reports support the following dimensions. Please note that currently, we don't support all dimensions available on TikTok Ads Manager.


# Supported dimensions
```xtable
| Dimension Name {20%} | Description {80%} |
| ---| --- |
| advertiser_id | Group by Advertiser ID. <br><br><p><span style="color:darkred"><b>Note</b></span>: Reporting data for [Branded Mission](https://ads.tiktok.com/help/article/about-branded-mission) ads is exclusively accessible when you query the advertiser level data using the `advertiser_id` dimension. Queries using the `campaign_id`, `adgroup_id`, or `ad_id` dimension will not return Branded Mission ad data.|
| campaign_id | Group by Campaign ID.<br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>By default, the `campaign_status` filter is applied and defaults to `STATUS_NOT_DELETE` in synchronous audience reports. If you want to get breakdown data for all campaigns, including the deleted ones via synchronous audience reports, you need to set `campaign_status` to `STATUS_ALL`.</li><li>Reporting data for [Branded Mission](https://ads.tiktok.com/help/article/about-branded-mission) ads is exclusively accessible when you query the advertiser level data using the `advertiser_id` dimension. Queries using the `campaign_id`, `adgroup_id`, or `ad_id` dimension will not return Branded Mission ad data.</li></ul> |
| adgroup_id | Group by Ad Group ID. <br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>By default, the `adgroup_status` filter is applied and defaults to `STATUS_NOT_DELETE` in synchronous audience reports. If you want to get breakdown data for all ad groups, including the deleted ones via synchronous audience reports, you need to set `adgroup_status` to `STATUS_ALL`.</li><li>Reporting data for [Branded Mission](https://ads.tiktok.com/help/article/about-branded-mission) ads is exclusively accessible when you query the advertiser level data using the `advertiser_id` dimension. Queries using the `campaign_id`, `adgroup_id`, or `ad_id` dimension will not return Branded Mission ad data.</li></ul>|
| ad_id_v2 | Group by Ad ID.<br><br>The types of data you can get include:<ul><li>the ad level data for Manual Ads</li><li>the ad level data for Smart+ Campaigns</li><li>the <b>ad (asset group)</b> level data for Upgraded Smart+ Ads</li></ul><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>You cannot use it together with the dimension `ad_id`, the filter `ad_ids`, or the attribute metric `ad_id`.</li></ul> |
| ad_id | Group by Ad ID. <br><br>The types of data you can get include:<ul><li>the ad level data for Manual Ads</li><li>the ad level data for Smart+ Campaigns</li><li>the <b>creative</b> level data for Upgraded Smart+ Ads</li></ul><br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>By default, the `ad_status` filter is applied and defaults to `STATUS_NOT_DELETE` in synchronous audience reports. If you want to get breakdown data for all ads, including the deleted ones via synchronous audience reports, you need to set `ad_status` to `STATUS_ALL`.</li><li>Reporting data for [Branded Mission](https://ads.tiktok.com/help/article/about-branded-mission) ads is exclusively accessible when you query the advertiser level data using the `advertiser_id` dimension. Queries using the `campaign_id`, `adgroup_id`, or `ad_id` dimension will not return Branded Mission ad data.</li></ul>|
| stat_time_day | Group by day |
| stat_time_hour | Group by hour |
| gender | Group by gender |
| age | Group by age |
| country_code | Group by location code. <br>See [Supported metrics for a dimension in audience reports](https://ads.tiktok.com/marketing_api/docs?id=1762404685488130) to learn about the supported metrics for this dimension.<br><p><span style="color:darkred"><b>Note</b></span>: In synchronous audience reports, the value of `None` for `country_code` represents "Unknown". |
| province_id | Group by location ID. For available values, see [Appendix - Location IDs](https://ads.tiktok.com/marketing_api/docs?id=1739311040498689). <br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>If you include `province_id` in the `dimensions` field of your request, it is recommended to narrow down the query scope in order to avoid timeouts. For instance, you can use a limited number of IDs as a filter. </li><li>In synchronous audience reports, the value of  `-1` for `province_id` represents "Unknown".</li><ul> |
| dma_id | Group by Designated Market Area (DMA). <br>Only U.S. has DMAs.<br> For available DMAs, see [Appendix - Location IDs](https://ads.tiktok.com/marketing_api/docs?id=1739311040498689).<br><p><span style="color:darkred"><b>Note</b></span>: In synchronous audience reports, the value of `0` for `dma_id` represents "Unknown". |
| ac | Group by audience network |
| language | Group by audience language |
| platform | Grouped by operating system |
| interest_category | Group by first_level interest category |
| interest_category_tier2 | Group by second_level interest category |
| interest_category_tier3 | Group by third_level interest category |
| interest_category_tier4 | Group by fourth_level interest category |
| behavior_id | Group by behavior |
| placement | Group by placement<br><p><span style="color:darkred"><b>Note</b></span>: If you want to distinguish between the ads with Global App Bundle placement (`PLACEMENT_GLOBAL_APP_BUNDLE` ) and those with BuzzVideo/Babe placement (`PLACEMENT_TOPBUZZ`) via the attribute metric `placement_type`, you need to specify `placement` together with `adgroup_id` or `ad_id` in the `dimensions` field of the request. |
| ad_type | Group by ad type. See [Supported metrics for a dimension in audience reports - Supported metrics for `ad_type`](https://business-api.tiktok.com/portal/docs?id=1762404685488130-item-link-Supported%20metrics%20for%20ad_type) to learn about the supported metrics for this dimension.  |
| device_brand_id | Group by device brand. When this dimension is used, `lifetime` cannot be `true`. |
| contextual_tag | Group by contextual tags. `contextual_tag` only supports auction ads. You can use [/tool/contextual_tag/get/](https://ads.tiktok.com/marketing_api/docs?id=1747747118654465) to get a list of available contextual tags.   |
|hashtag|Group by hashtag. See [Supported metrics for a dimension in audience reports - Supported metrics for `hashtag`](https://business-api.tiktok.com/portal/docs?id=1762404685488130-item-link#Supported%20metrics%20for%20ad_type/hashtag/audience_tags/purchase_intention) to learn about the supported metrics for this dimension.|
|audience_tags|Group by audience tags. See [Supported metrics for a dimension in audience reports - Supported metrics for `audience_tags`](https://business-api.tiktok.com/portal/docs?id=1762404685488130#item-link-Supported%20metrics%20for%20ad_type/hashtag/audience_tags/purchase_intention) to learn about the supported metrics for this dimension.|
|additional_interests|Group by additional interests. |
|purchase_intention|Group by purchase intention. See [Supported metrics for a dimension in audience reports - Supported metrics for `purchase_intention`](https://business-api.tiktok.com/portal/docs?id=1762404685488130#item-link-Supported%20metrics%20for%20ad_type/hashtag/audience_tags/purchase_intention) to learn about the supported metrics for this dimension. <br><p><span style="color:darkred"><b>Note</b></span>: This dimension can only be used in async reports. |
| bucket_id | Group by RTA (Real-Time API) bucket ID. An RTA bucket ID is an integer that identifies the traffic segment group (bucket) for your data. Pangle divides traffic into these groups for testing and optimization, enabling you to adjust your real-time bidding strategies through RTA based on the user characteristics of these different buckets and evaluate different strategies on various traffic segments. <br><br>The supported metrics for this dimension are: <ul><li> `cost`</li><li>`impressions`</li><li>`clicks` </li><li>`real_time_conversion`</li></ul><br>The supported dimension grouping for this dimension is: `bucket_id` + (optional) `advertiser_id`/`campaign_id`+ (optional) `placement`/`country_code` + (optional) `stat_time_day`/`stat_time_hour`. <br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>This dimension can only be used in synchronous reports.</li><li>The `bucket_id` dimension is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</li></ul>|
```

# Dimension grouping

## Available dimension categories
**ID dimensions**

* advertiser_id
* campaign_id
* adgroup_id
* ad_id

**Time dimensions**

* stat_time_day
* stat_time_hour

**Audience dimensions** 

* gender
* age
* country_code 
* ac 
* language
* platform
* interest_category
* placement
* contextual_tag (cannot be used together with other audience dimensions)
* province_id
* dma_id
* interest_category
* interest_category_tier2
* interest_category_tier3
* interest_category_tier4
* device_brand_id
* behavior_id
* ad_type
* hashtag
* audience_tags
* additional_interests
* purchase_intention
- bucket_id

>  <span style="color:DodgerBlue">**Note**</span><br>Querying UV metrics with audience dimensions is not supported in asynchronous reports. Please query UV metrics without audience dimensions or audience dimensions without UV metrics in asynchronous reports instead. <br><br>UV metrics include <code>"average_video_play_per_user"</code>, <code>"frequency"</code>, <code>"cost_per_1000_reached"</code>, <code>"real_time_result"</code>, <code>"real_time_result_rate"</code>, <code>"real_time_cost_per_result"</code>, <code>"result"</code>, <code>"result_rate"</code>, <code>"cost_per_result"</code>, <code>"reach"</code>, <code>"live_play_count"</code>.


## Dimension grouping rules
The grouping can be **one** audience dimension (**required**), **one** ID dimension (optional), and **one** time dimension (optional). You cannot have more than one dimension of the same type in one request. The only exception is `age` and `gender`, and you can have both `age` and `gender` dimensions in one request.

- Special scenarios: 
	- If `advertiser_ids` is specified via [/report/task/create/](https://ads.tiktok.com/marketing_api/docs?id=1740302766489602) (POST method), the grouping can be **one** audience dimension (**required**), **one or two** ID dimensions (optional), and **one** time dimension (optional). When you pass in two ID dimensions at the same time, one of them must be `advertiser_id`. For example, `["advertiser_id", "campaign_id", "stat_time_day", "gender"]`.
    - If you want to use location as your grouping filter, you can specify any of the following dimension groupings in the `dimensions` field of the request:
        - `country_code`/`province_id` only
        - `country_code`/`province_id` + **one** ID dimension
        - `country_code`/`province_id` + **one** ID dimension + `stat_time_day`
        - `country_code`/`province_id` + `stat_time_day`
        - `country_code` + `bucket_id` + (optional) `advertiser_id`/`campaign_id` + (optional)  `stat_time_day` / `stat_time_hour`
    - The supported dimension grouping for the dimension `bucket_id` is: `bucket_id`+  (optional) `advertiser_id`/`campaign_id`+  (optional) `placement`/`country_code` +  (optional)  `stat_time_day`/ `stat_time_hour`
	

**Additional dimension grouping rules**

* The following dimensions cannot be used with any time dimension. 
  * interest_category
  * interest_category_tier2
  * interest_category_tier3
  * interest_category_tier4
  * device_brand_id

* In synchronous audience reports, `behavior_id` can be used with `stat_time_day` to retrieve the reporting data of up to 5,000 ads, although in all other synchronous reports you can retrieve the reporting data of up to 20,000 ads.
* Age and gender can exist simultaneously in the audience dimension. For example: `["stat_time_day", "age", "gender"]`.
* `ad_type` cannot be used with `advertiser_id` in the dimension grouping. 


