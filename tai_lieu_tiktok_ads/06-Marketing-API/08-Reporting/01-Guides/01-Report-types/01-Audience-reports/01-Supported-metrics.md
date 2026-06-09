---
title: "Supported metrics"
doc_id: 1751454162042882
path: "Marketing API / Reporting / Guides / Report types / Audience reports / Supported metrics"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Supported metrics

You can use the following metrics in audience reports. Metrics that have no consumption will be returned as `-`. Please note that currently, we don't support all metrics available on TikTok Ads Manager.


# Comparing v1.2 and v1.3 
 The following table outlines the differences **between v1.2 and v1.3 audience report metrics**. 
```xtable
|Changes {30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|New Metric type | / |<strong>Video play metrics</strong><br><strong>Engagement metrics</strong><br><strong>Attribution metrics</strong><br><strong>Page event metrics</strong>|
| Attribute Metric name | `placement` |  `placement_type`|
| Attribute Metric data type | `campaign_id`: number<br> `adgroup_id`: number| `campaign_id`: string <br> `adgroup_id`: string|
|New Attribute metrics | / |`campaign_automation_type`<br>`ad_id_v2` |
| New basic data metrics | /  |`reach`<br> `cost_per_1000_reached`<br>`frequency`<br>`cost`  |
```


# Attribute metrics
Attribute metrics, such as ad group names and promotion types, are basic attributes of your campaigns, ad groups or ads. Attribute metrics are valid only when an ID type of dimension is used. 
```xtable
| Field{25%}| Data Type{10%} | Description{35%} | Detail{30%}|
|---|---|---|---|
| advertiser_name | string | Account name | Supported at Advertiser, Campaign, Ad Group, and Ad level. |
| advertiser_id | string | Account ID | Supported at Advertiser, Campaign, Ad Group, and Ad level. |
| campaign_automation_type | string | Campaign automation type.<br><br>Enum values:<ul><li>`MANUAL`: Manual Campaigns.</li><li>`SMART_PLUS`: Smart+ Campaigns.</li><li>`UPGRADED_SMART_PLUS`: Upgraded Smart+ Campaigns. To learn more about Upgraded Smart+ Campaigns, see [Upgraded Smart+ Campaign](https://business-api.tiktok.com/portal/docs?id=1853452461203458).<ul><li>When `campaign_automation_type` is `UPGRADED_SMART_PLUS`, the returned `ad_id_v2` is the ID of the Upgraded Smart+ Ad.</li></ul></li><li>`UPGRADED_SMART_PLUS_CREATIVE`: Upgraded Smart+ Campaigns. To learn more about Upgraded Smart+ Campaigns, see [Upgraded Smart+ Campaign](https://business-api.tiktok.com/portal/docs?id=1853452461203458).<ul><li>When `campaign_automation_type` is `UPGRADED_SMART_PLUS_CREATIVE`, the returned `ad_id` is the ID of a creative in the Upgraded Smart+ Ad.</li></ul></li></ul> |Supported at Campaign, Ad Group and Ad levels.|
| campaign_name | string | Campaign name | Supported at Campaign, Ad Group and Ad levels. |
| campaign_id | string | Campaign ID | Supported at Ad Group and Ad levels. |
| objective_type | string | Advertising objective| Supported at Campaign, Ad Group and Ad level. |
| split_test | string | Split test status | Supported at Campaign, Ad Group and Ad level. |
| campaign_budget | string | Campaign budget | Supported at Campaign, Ad Group and Ad level. |
| campaign_dedicate_type | string | Campaign type | iOS 14 Dedicated Campaign or regular campaign. Supported at Campaign, Ad Group and Ad level. |
| app_promotion_type  | string | App promotion type | Supported at Campaign, Ad Group, and Ad level. |
| adgroup_name | string | Ad group name | Supported at Ad Group and Ad levels. |
| adgroup_id | string | Ad group ID | Supported at Ad level. |
| placement_type | string | Placement type | Supported at Ad Group and Ad levels. |
| promotion_type | string | Promotion type | It can be app, website, or others. Supported at Adgroup and Ad levels in both synchronous and asynchronous reports. |
| opt_status | string | Automated creative optimization | Supported at Ad Group and Ad level. |
| adgroup_download_url | string | Download URL/Website URL | Supported at Ad Group and Ad level. |
| profile_image | string | Profile image | Supported at Ad Group and Ad level. |
| dpa_target_audience_type | string | Target audience type for DPA | The Audience that DPA products target. Supported at Ad group or Ad levels in both synchronous and asynchronous reports. |
| budget | string | Ad group budget | Supported at Ad Group and Ad level. |
| smart_target | string | Optimization goal | Supported at Ad Group and Ad level. |
| pricing_category {-To be deprecated} | string | Billing Event | Supported at Ad Group and Ad level. <br> If you want to retrieve the billing event of your ads, use the new metric `billing_event`. |
| billing_event | string | Billing Event | Supported at Ad Group and Ad level. <br> Example: `"Clicks"`, `"Impression"`. |
| bid_strategy | string | Bid strategy | Supported at Ad Group and Ad level. |
| bid | string | Bid | Supported at Ad Group and Ad level. |
| bid_secondary_goal | string | Bid for secondary goal | Supported at Ad Group and Ad level. |
| aeo_type | string | App Event Optimization Type | Supported at Ad Group and Ad level. (Already Supported at adgoup, will add in ad level) |
| ad_name | string | Ad name | Supported at Ad level. |
| ad_id_v2 | string | The types of data you can get include:<ul><li>the ad IDs of Manual Ads.</li><li>the ad IDs of Smart+ Campaigns.</li><li>the ad IDs of Upgraded Smart+ Ads.</li></ul><br><ul><li>You cannot use it together with the dimension `ad_id`, the filter `ad_ids`, or the attribute metric `ad_id`.</li></ul> | Supported at Ad level. |
| ad_id | string | Ad ID | Supported at Ad level. |
| ad_text | string | Ad title | Available in Ad level. |
| call_to_action | string | Call to action | Supported at Ad level. |
| ad_profile_image | string | Profile image (Ad level) | Supported at Ad level. |
| ad_url | string | URL (Ad level) | Supported at Ad level. |
| tt_app_id | string | TikTok App ID | TikTok App ID, the App ID you used when creating an Ad Group. Supported at Ad Group and Ad levels. Returned if the promotion type of one Ad Group is App. |
| tt_app_name | string | TikTok App Name | The name of your TikTok App. Supported at Ad Group and Ad levels. Returned if the promotion type of one Ad Group is App. |
| mobile_app_id | string | Mobile App ID | <p>Mobile App ID.</p><p>Examples are, App Store: https://apps.apple.com/us/app/angry-birds/id<b>343200656</b>; Google Play：https://play.google.com/store/apps/details?id=<b>com.rovio.angrybirds</b>.</p>Supported at Ad Group and Ad levels. Returned if the promotion type of one Ad Group is App. |
| device_brand_name |string | Device brand name | Available when the `device_brand_id` dimension is used. |
|behavior_name|string| Behavior name|Available when the `behavior_id` dimension is used. |
|action_category|string|Action / behavior category |Available only when the `behavior_id` dimension is used. This metric does not support asynchronous reports.|
|action_scene| string| Action scene. Enum values: `VIDEO_RELATED`, `CREATOR_RELATED`. |Available only when the `behavior_id` dimension is used. This metric does not support asynchronous reports.|
|user_action|string|User action |For the `VIDEO_RELATED` scene, available values include `WATCHED_TO_END`, `LIKED`, `COMMENTED`, and `SHARED`. For the `CREATOR_RELATED` scene, available values are `FOLLOWING` and `VIEW_HOMEPAGE`. |
|action_period|string|Number of days for the action. Enum values: `7`, `15`. | This metric does not support asynchronous reports.|
|rf_campaign_type| string |Campaign type. Enum values: `0` (Reach & Frequency campaign), `1` (TikTok Pulse campaign). |Supported at Campaign, Ad Group and Ad levels in both synchronous and asynchronous reports.|
|delivery_type|string|Delivery type.|Supported at Ad Group and Ad levels.|
|adgroup_display_name|string|Ad group display name.|Supported at Ad Group and Ad levels.|
|adgroup_schedule|string|Ad group schedule. Example: "2024-01-09-Ongoing".|Supported at Ad Group and Ad levels.|
|optimization_preference|string|Optimization preference. Example: "Optimize conversion at learning phase".|Supported at Ad Group and Ad levels.|
|image_info|string|Image information. Example: `https://p16-sign-sg.tiktokcdn.com/tos-alisg-p-0051c001-sg/o4PDmEEDgzoBf7VFfmlNBBAtE7p2uqI4TduOYQ~tplv-noop.image?x-expires=1707528999&x-signature=91iFceqlBWKfwNuOJjyaijC5qU0%3D`|Supported at Ad level.|
|ad_display_name|string|Ad display name.|Supported at Ad level.|
|identity_type|string|Identity type. Example: "TikTok account (Spark Ads)".|Supported at Ad level.|
| currency | string | currency | The currency code, e. g. USD. Note that if you want to use `currency` as metrics, then the `dimensions` field in your request must include `adgroup_id`/`ad_id`/`campaign_id`/`advertiser_id`. 
|is_aco|boolean|Whether the ad is an automated ad or a Smart Creative ad. Set to `True` for an automated ad or Smart Creative ad.|Supported at AUCTION_ADGROUP level.|
|is_smart_creative|boolean|Whether the ad is a Smart Creative ad. |Supported at AUCTION_AD level.|
```
# Basic data metrics
 Basic data metrics are data about spend and performance of your ads. 
```xtable
| Field{25%}| Data Type{10%} | Description{25%} | Detail{40%}|
|---|---|---|---|
| spend | string | Cost | Sum of your total ad spend, calculated in the currency of your ad account.  |
| cost | string | Cost | Sum of your total ad spend, calculated in USD. <br><br><p><span style="color:darkred"><b>Note</b></span>: This metric can only be used together with the `bucket_id` dimension. |
| cpc | string | CPC (destination) | Average cost of each click to a specified destination. |
| cpm | string | CPM | Average amount you spent per 1,000 impressions. |
| impressions | string | Impressions | Number of times your ads were shown. |
| gross_impressions | string | Gross Impressions (Includes Invalid Impressions) | The number of times an ad was seen on screen, including invalid impressions. |
| clicks | string | Clicks (destination) | Number of clicks from your ads to a specified destination. |
| ctr | string | CTR (destination) | Percentage of impressions that resulted in a destination click out of all impressions. |
| reach | string | Reach | Number of unique users who saw your ads at least once.<br><br><p><span style="color:darkred"><b>Note</b></span>: If you want to use this metric, you need to specify any of the following audience dimension groupings along with one optional ID dimension and one optional time dimension in the `dimensions` field of the request: <ul><li>`age`</li><li>`gender`</li><li> `age` + `gender`</li><li>`country_code`</li><li>`province_id`</li><li>`language`</li></ul>|
| cost_per_1000_reached | string | Cost per 1,000 people reached | Average cost to reach 1,000 unique users. <br><br><p><span style="color:darkred"><b>Note</b></span>: If you want to use this metric, you need to specify any of the following audience dimension groupings along with one optional ID dimension and one optional time dimension in the `dimensions` field of the request: <ul><li>`age`</li><li>`gender`</li><li> `age` + `gender`</li><li>`country_code`</li><li>`province_id`</li><li>`language`</li></ul>|
| frequency | string | Frequency | The average number of times each user saw your ad over a given time period.<br><br><p><span style="color:darkred"><b>Note</b></span>: If you want to use this metric, you need to specify any of the following audience dimension groupings along with one optional ID dimension and one optional time dimension in the `dimensions` field of the request: <ul><li>`age`</li><li>`gender`</li><li> `age` + `gender`</li><li>`country_code`</li><li>`province_id`</li><li>`language`</li></ul>|
| conversion | string | Conversions | Number of times your ad resulted in the optimization event you selected. |
| cost_per_conversion | string | Cost per conversion | Average amount spent on a conversion. |
| conversion_rate | string | Conversion rate (CVR, clicks) | Percentage of results you received out of all destination clicks on your ads.<br><br><p><span style="color:darkred"><b>Note</b></span>: Starting late October, 2023, the calculation logic for this metric will be updated to be impression-based (the same as `conversion_rate_v2`). To ensure a smooth API integration and avoid disruptions caused by the change in calculation logic, we recommend you switch to using the impression-based metric `conversion_rate_v2` as soon as possible.  |
| conversion_rate_v2 | string | Conversion rate (CVR) | Percentage of results you received out of all impressions on your ads. |
| real_time_conversion | string | Real-time conversions | Number of times your ad resulted in the optimization event you selected. |
| real_time_cost_per_conversion | string | Real-time cost per conversion | Average amount spent on a conversion. |
| real_time_conversion_rate | string | Real-time conversion rate (CVR, clicks) | Percentage of conversions you received out of all destination clicks on your ads. <br> <br><p><span style="color:darkred"><b>Note</b></span>: Starting late October, 2023, the calculation logic for this metric will be updated to be impression-based (the same as `real_time_conversion_rate_v2`). To ensure a smooth API integration and avoid disruptions caused by the change in calculation logic, we recommend you switch to using the impression-based metric `real_time_conversion_rate_v2` as soon as possible. |
| real_time_conversion_rate_v2 | string | Real-time conversion rate (CVR) | Percentage of conversions you received out of all impressions on your ads. |
| result | string | Results | Number of times your ad resulted in an intended outcome based on your campaign objective and optimization goal. |
| cost_per_result | string | Cost per result | Average cost per each result from your ads. |
| result_rate | string | Result rate | Percentage of results that happened out of all impressions on your ads. |
| real_time_result | string | Real-time results | Number of times your ad resulted in an intended outcome based on your campaign objective and optimization goal. |
| real_time_cost_per_result | string | Real-time cost per result | Average cost per each result from your ads. |
| real_time_result_rate | string | Real-time result rate | Percentage of results that happened out of all impressions on your ads. |
```

# Video play metrics
Video play metrics, such as 2-Second Video Views and Video Views at 50%, are data about video view performance. 
```xtable
| Field{25%}| Data Type{10%} | Description{25%} | Detail{40%}|
|---|---|---|---|
| video_play_actions | string | Video views | Number of times your video started to play. For each video impression, plays are counted separately and replays are excluded. |
| video_watched_2s | string | 2-second video views | Number of times your video was played for at least 2 seconds. For each video impression, plays are counted separately and replays are excluded. |
| video_watched_6s | string | 6-second video views | Number of times your video was played for at least 6 seconds. For each video impression, plays are counted separately and replays are excluded. |
| engaged_view | string | 6-second focused views | Number of times your video was played for at least 6 seconds, played in full if it is less than 6 seconds, or received at least 1 engagement within the first 6 seconds. |
| engaged_view_15s | string | 15-second focused views | Number of times your video was played for at least 15 seconds, played in full if it is less than 15 seconds, or received at least 1 engagement within the first 15 seconds. |
| video_views_p25 | string | Video views at 25% | Number of times your video was played at least 25% of its length. For each impression, views are counted separately and replays are excluded. |
| video_views_p50 | string | Video views at 50% | Number of times your video was played at least 50% of its length. For each impression, views are counted separately and replays are excluded. |
| video_views_p75 | string | Video views at 75% | Number of times your video was played at least 75% of its length. For each impression, views are counted separately and replays are excluded. |
| video_views_p100 | string | Video views at 100% | Number of times your video was played 100% of its length. For each impression, views are counted separately and replays are excluded. |
| average_video_play | string | Average play time per video view | Average time your video was played per single video view, including any time spent replaying the video. |
| average_video_play_per_user | string | Average play time per user | Average time your video was played per user, including any time spent replaying the video.<br><br><p><span style="color:darkred"><b>Note</b></span>: This metric is supported only when `placement` is specified in the `dimensions` field of the request. |
```
# Engagement metrics
 Engagement metrics, such as the number of likes and comments, are data about how people interact with your ads. 
 
```xtable
| Field{25%}| Data Type{10%} | Description{25%} | Detail{40%}|
|---|---|---|---|
| follows | string | Paid follows | Number of follows to the associated profile during the ad impression. |
| likes | string | Paid likes | Number of times your ad was liked during the ad impression. |
| comments | string | Paid comments | Number of comments sent on your ad during the ad impression. |
| shares | string | Paid shares | Number of shares of your ad during the ad impression. |
| profile_visits | string | Paid profile visits | Number of visits to the associated profile during the ad impression. |
| profile_visits_rate | string | Paid profile visit rate | Percentage of profile visits from ads out of all impressions on your ads. |
| clicks_on_music_disc | string | Sound clicks | Number of clicks on the sound associated with your ad during the ad impression. |
| ix_page_duration_avg | string | Instant experience average view time | Average time your Instant Experience was shown. <br><br><p><span style="color:darkred"><b>Note</b></span>: This metric is supported only when `placement` is specified in the `dimensions` field of the request. |
| ix_page_viewrate_avg | string | Instant experience average view percentage | Average percentage of content on your Instant Experience that was viewed. <br><br><p><span style="color:darkred"><b>Note</b></span>: This metric is supported only when `placement` is specified in the `dimensions` field of the request. |
```

# Attribution metrics
Attribution metrics are data about how events are attributed.

> <span style="color:DodgerBlue">**Note**</span><br>
> The only [audience dimensions](https://business-api.tiktok.com/portal/docs?id=1751454103714818) supported for Attribution metrics in audience reports are `placement` and `ad_type`.

```xtable
| Field{25%}| Data Type{10%} | Description{25%} | Detail{40%}|
| --- | --- | --- | --- |
| vta_complete_payment | string | VTA complete payment | Complete payment events attributed to someone only viewing your ad. |
| cost_per_vta_payments_completed | string | Cost per VTA complete payment | Average cost of each complete payment event attributed to someone only viewing your ad. |
| vta_complete_payment_value | string | Total VTA complete payment value | Total value of complete payment events attributed to someone only viewing your ad. |
| vta_complete_payment_roas | string | VTA complete payment ROAS | Return on ad spend (ROAS) from complete payment events attributed to someone only viewing your ad. |
```

# Page event metrics
Page Event Metrics are data about website conversion events.

```xtable
| Field{25%}| Data Type{10%} | Description{25%} | Detail{40%}|
|---|---|---|---|
| Landing Page View | | | |
#| total_landing_page_view | string | Total Landing Page View | The number of landing page view events from your ads. |
#| cost_per_landing_page_view | string | Cost per Landing Page View | The average cost of each landing page view event. |
#| landing_page_view_rate | string | Landing Page View Rate | The percentage of click events that are landing page view events. |
```
