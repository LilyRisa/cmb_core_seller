---
title: "Supported metrics"
doc_id: 1751617882108929
path: "Marketing API / Reporting / Guides / Report types / Playable ad reports / Supported metrics"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Supported metrics

You can use the following metrics in playable ad reports. Metrics that have no consumption will be returned as `-`. Please note that currently, we don't support all metrics available on TikTok Ads Manager.


## Attribute metrics
Attribute metrics, such as playable names and playable ids, are basic attributes of your playable ads. Attribute metrics are supported only when dimensions contain `playable_id`.
``` xtable
| Field {25%} | Data Type{10%}| Description{25%} | Detail {40%} |
|---|---|---|---|
| playable_id | string | Playable ID| Playable creative ID |
| playable_name | string | Playable Name | Playable creative name |
| playable_url | string | Playable URL | Playable creative URL |
| preview_url | string | Thumbnail | |
| create_time | datetime | Create time | |
| modify_time | datetime | Modify time | |
| playable_status | string | Status | |
| orientation | string | Display orientation | |
| country | string | Delivery country or location | |
| associated_adgroup_count | string | No. of Related Ad Groups ||
```
## Basic data metrics
Basic data metrics are data about spend and performance of your playable ads.
``` xtable
| Field {25%} | Data Type{10%}| Description{25%} | Detail {40%} |
|---|---|---|---|
| spend | string | Cost | Sum of your total ad spend. |
| cpm | string | CPM | Average amount you spent per 1,000 impressions. |
| impressions | string | Impressions | Number of times your ads were shown. |
| clicks | string | Clicks (destination) | Number of clicks from your ads to a specified destination. |
| ctr | string | CTR (destination) | Percentage of impressions that resulted in a destination click out of all impressions. |
| conversion | string | Conversions | Number of times your ad resulted in the optimization event you selected. |
| cost_per_conversion | string | Cost per conversion | Average amount spent on a conversion. |
| conversion_rate | string | Conversion rate (CVR, clicks) | Percentage of results you received out of all destination clicks on your ads. <br><br><p><span style="color:darkred"><b>Note</b></span>: Starting late October, 2023, the calculation logic for this metric will be updated to be impression-based (the same as `conversion_rate_v2`). To ensure a smooth API integration and avoid disruptions caused by the change in calculation logic, we recommend you switch to using the impression-based metric `conversion_rate_v2` as soon as possible.  |
| conversion_rate_v2 | string | Conversion rate (CVR) | Percentage of results you received out of all impressions on your ads. |
| currency | string | currency | The currency code, e. g. USD. Note that if you want to use `currency` as metrics, then the `dimensions` field in your request must include `adgroup_id`/`ad_id`/`campaign_id`/`advertiser_id`.  `currency`only works for playable synchronous reports. |
```

## In-App Event metrics (Unique)
In-App event metrics are data about in-app conversion events. 
``` xtable
| Field {25%} | Data Type{10%}| Description{25%} | Detail {40%} |
|---|---|---|---|
| app_install | string | App Install | The number of times your mobile app was installed that were recorded as app events and attributed to your ads. (The total count is calculated based on the time each ad impression occurred.) |
| cost_per_app_install| string | Cost per App Install | The average cost for each app install event. (The total count is calculated based on the time each ad impression occurred.) |
| app_install_rate| string | App Install Rate | App Install Rate. (The total count is calculated based on the time each ad impression occurred.)|
```
## Playable ads event metrics
Playable ads event metrics are data about how people interact with your playale ads.
``` xtable
| Field {25%} | Data Type{10%}| Description{25%} | Detail {40%} |
|---|---|---|---|
| playable_page_view_count | string | Playable page views | Number of times your playable page was shown. |
| playable_first_scene_view_count | string | Playable views of first scene | Number of views of your playable page that reach at least the first scene of your ad. |
| playable_second_scene_view_count | string | Playable views of second scene | Number of views of your playable page that reach at least the second scene of your ad. |
| playable_third_scene_view_count | string | Playable views of third scene | Number of views of your playable page that reach the third scene of your ad. |
| playable_cta_click | string | Playable download button clicks | Number of clicks on the download button on your playable page. |
```

