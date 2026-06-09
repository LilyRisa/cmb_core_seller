---
title: "Run a GMV Max Campaign report"
doc_id: 1824721673497601
path: "API Reference / GMV Max / Run a GMV Max Campaign report"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Run a GMV Max Campaign report

Use this endpoint to run a report on GMV Max Campaigns.

> <span style="color:DodgerBlue">**Note**</span><br>
> By default, the response only includes undeleted campaigns.

## Rate limits
The rate limits for this endpoint vary based on the rate limit level of your developer app. Learn more about [rate limits](https://business-api.tiktok.com/portal/docs?id=1740029171730433).

```xtable
| Rate limit level {20%} | Query per second (QPS){25%} | Query per minute (QPM){25%} | Query per day (QPD）{30%} |
|--- |--- |--- |--- |
| Basic | 8 | 240 | 20,000 |
| Advanced | 12 | 360 | 30,000 |
| Premium | 20 | 600 | 50,000 |
| Ultimate | 20 | 600 | 50,000 |
```

## Before you start
Ensure you have the necessary permissions based on the type of GMV Max Campaign and [the level of metrics](https://business-api.tiktok.com/portal/docs?id=1824722485971009) you wish to access. Review the prerequisites for [Product GMV Max Campaigns](https://business-api.tiktok.com/portal/docs?id=1822009220448257#item-link-Prerequisites) or [LIVE GMV Max Campaigns](https://business-api.tiktok.com/portal/docs?id=1822009242546258#item-link-Prerequisites) to confirm your permissions.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/gmv_max/report/get/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1738373164380162).|
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| advertiser_id {Required} | string | Advertiser ID. |
| store_ids {Required} | string[] | A list of TikTok Shop IDs. <br><br>Max size: 1. <br><br>To obtain TikTok Shops that are available for GMV Max Campaigns, use [/gmv_max/store/list/](https://business-api.tiktok.com/portal/docs?id=1822001044479041) and confirm that the returned `is_gmv_max_available` is `true`. |
| start_date {Required} | string | Query start date (closed interval) in the format of `YYYY-MM-DD`. The date is based on the ad account time zone.<br><br>Example: `2025-01-01`. |
| end_date {Required} | string | Query end date (closed interval) in the format of `YYYY-MM-DD`. The date is based on the ad account time zone. <br><br>Example: `2025-02-01`. <br><br><ul><li>If you don't include `stat_time_day` or `stat_time_hour` in the value of the dimensions field, the time range defined by `start_date` and `end_date` can be up to <b>365</b> days.</li><li> If you include `stat_time_day` in the value of the dimensions field to query daily breakdown data, the time range defined by `start_date` and `end_date` can be up to <b>30</b> days.</li><li>If you include `stat_time_hour` in the value of the dimensions field to query hourly breakdown data, the time range defined by `start_date` and `end_date` can be up to <b>one</b> day.</li></ul> |
| metrics {Required} | string[] | Metrics to query.  <br><br>To learn about the different types of metrics that you can query for all GMV Max Campaigns, for Product GMV Max Campaigns, or for LIVE GMV Max Campaigns, see [Metrics in GMV Max Campaign reports](https://business-api.tiktok.com/portal/docs?id=1824722485971009). |
| enable_total_metrics | boolean | Whether to enable the total added-up data for your requested metrics. When `enable_total_metrics` is enabled, we will provide the aggregate data for all pages as you query different pages.<br><br>Supported values: `true`, `false`. <br>Default value: `false`. |
| dimensions {Required} | string[] | Dimension groupings. <br><br>Enum values:<ul><li>`advertiser_id`: Group by advertiser ID.</li><li>`campaign_id`: Group by campaign ID.</li><li>`stat_time_day`: Group by day.</li><li>`stat_time_hour`: Group by hour.</li><li>`item_group_id`: Group by SPU ID.</li><li>`item_id`: Group by post ID.</li><li>`room_id`: Group by room ID. </li><li>`duration`: Group by duration.</li></ul><br>For example, `&#91;"campaign_id", "stat_time_day"&#93;` indicates that both `campaign_id` and `stat_time_day` (days) are grouped. <br><br> The available dimension groupings vary based on the type of metrics that you want to query. To learn about the available dimension groupings for your desired metrics type, see [Metrics in GMV Max Campaign reports](https://business-api.tiktok.com/portal/docs?id=1824722485971009). |
| filtering | object | Filtering conditions.<br><br> To learn about the available filters for your desired metrics type, see [Metrics in GMV Max Campaign reports](https://business-api.tiktok.com/portal/docs?id=1824722485971009). |
#| gmv_max_promotion_types | string[] | Filter by the GMV Max Campaign type.<br><br>Enum values:<ul><li>`PRODUCT`: Product GMV Max Campaign.</li><li>`LIVE`: LIVE GMV Max Campaign.</li></ul>
#| campaign_ids | string[] | Filter by a list of GMV Max Campaign IDs. <br><br>Max size: 100. |
#| campaign_name | string | Filter by a GMV Max Campaign name. <br><br>Fuzzy match is supported. |
#| campaign_statuses | string[] | Filter by campaign statuses. <br><br>Enum values:<ul><li>`STATUS_DELIVERY_OK`: Active.</li><li>`STATUS_DISABLE`: Inactive. </li><li>`STATUS_DELETE`: Deleted.</li><li>`STATUS_NOT_DELIVERY`: Not delivering.</li><li>`STATUS_ALL`: All statuses.</li></ul> <br><p><span style="color:darkred"><b>Note</b></span>: By default, the response only includes undeleted campaigns.|
#| item_group_ids | string[] | Filter by a list of product SPU IDs. <br><br>Max size: 100. |
#| creative_types {-to-be-deprecated}| string[] | <ul><li>Required when you filter one SPU ID using `campaign_ids` and `item_group_ids`.</li><li>Not supported when you specify multiple IDs in the `campaign_ids` filter or `item_group_ids` filter or both, or specify multiple ID dimensions.</li></ul><br>Filter by creative types. <br><br>Enum values:<ul><li> `ADS_AND_ORGANIC`: Ads and organic. This type contains all creatives that are authorized and in use for ads. The total gross revenue from videos in this type includes sales from both ads and organic videos.</li><li>`ORGANIC`: Organic only. This type contains videos that are used in organic traffic only. The total gross revenue from videos in this type includes sales from organic videos only. </li><li>`REMOVED`: Removed. This type contains automatically selected videos that have been manually removed from your campaign.</li></ul><br> Max size: 1. |
#|creative_delivery_statuses|string[]|<ul><li>You can only use this filter in any of the following scenarios: <ul><li>When you specify multiple IDs in the `campaign_ids` filter or `item_group_ids` filter or both</li><li>When you specify one ID in the `campaign_ids` filter or `item_group_ids` filter or both</li><li>When you specify multiple ID dimensions</li></ul></li><li>This filter cannot be used together with `creative_types`.</li></ul><br>Filter by creative statuses.<br><br>Enum values: <ul><li>`IN_QUEUE`: In queue. Post is ready to be tested for its conversion-driving potential.</li><li>`LEARNING`: Learning. Post is being tested for its conversion-driving potential.</li><li>`DELIVERING`: Delivering. Post is being regularly promoted as part of your campaign.</li><li>`NOT_DELIVERYING`: Not delivering. Post didn't show strong conversion potential during testing and will not be regularly promoted as part of your campaign.</li><li>`AUTHORIZATION_NEEDED`: Authorization needed. This video hasn't been authorized for use in ads. You can authorize your own videos from your TikTok account or request authorization from other creators. This status will be updated here within 20 minutes of authorization.</li><li>`EXCLUDED`: Excluded. Post was manually removed and will not be regularly promoted as part of your campaign. You can add this post back to your campaign at any time.</li><li>`UNAVAILABLE`: Unavailable. Private videos, rejected videos, deleted videos, or videos from suspended TikTok accounts can't be used in ads. In some cases, you may observe cost from unavailable videos because videos that are rejected by TikTok’s content review process part-way through the campaign may still be delivered.</li><li>`REJECTED`: Rejected. The video has been rejected by the content moderation team. If you've appealed a rejection, the video status will be "rejected" until the appeal has been reviewed.</li><li>`NOT_ACTIVE`: Not active. This post has been deprioritized because it was uploaded more than 30 days ago and hasn't earned any gross revenue in the past 30 days.</li></ul> |
#| search_word | string | Filter by a search keyword, such as a video title, TikTok post ID, or TikTok account name. <br><br> Fuzzy match is supported. |
#| room_ids | string[] | Filter by a list of Livestream room IDs.<br><br> Max size: 100. |
| sort_field | string | Sorting field. <br><br>Not sorted by default. <br><br>We recommend using a sorting field to ensure the results are sorted in a logical order. To find the available fields, see <a href="#item-link-Supported sorting fields">Supported sorting fields</a>. |
| sort_type | string | Sorting order. <br><br>Enum values: `ASC`, `DESC`.<br> Default value: `DESC`. |
| page | integer | Current page number. <br><br>Value range: ≥1. <br>Default value: 1. |
| page_size | integer | Page size. <br><br>Value range: 1-1,000.<br> Default value: 10. |
```
### Example
```xcodeblock
(code curl http)
curl --location --request GET https://haapi.byteintl.net/open_api/v1.3/gmv_max/report/get/?advertiser_id={{advertiser_id}}&store_ids=["{{store_id}}"]&start_date=2025-07-03&end_date=2025-07-03&metrics=["creative_delivery_status","cost","orders","cost_per_order","gross_revenue","roi","product_impressions","product_clicks","product_click_rate","ad_click_rate","ad_conversion_rate","ad_video_view_rate_2s","ad_video_view_rate_6s","ad_video_view_rate_p25","ad_video_view_rate_p50","ad_video_view_rate_p75","ad_video_view_rate_p100"]&dimensions=["item_id"]&filtering={"campaign_ids":["{{campaign_id}}"],"item_group_ids":["{{item_group_id_1}}","{{item_group_id_2}}"]}&page=1&page_size=1000
--header 'X-Use-ppe: 1' \
--header 'Access-Token: {{Access-Token}}' \
--header 'X-Tt-Env: ppe_mapi_batch' \
--header 'Content-Type: application/json' \
--header 'X-Debug-RefreshConfig: 1' \
--header 'haapi-v2: 1'
(/code)
```

### Supported sorting fields
The following tables list the available sorting fields for Product GMV Max Campaigns and LIVE GMV Max Campaigns separately.

#### For Product GMV Max Campaigns
``` xtable
|Field{30%}|Description{70%}|
|---|---|
| cost | Cost |
| net_cost | Net cost |
| orders | Orders (SKU) |
| cost_per_order | Cost per order |
| gross_revenue | Gross revenue |
| roi | ROI |
| product_impressions | Product ad impressions |
| product_clicks | Product ad clicks |
| product_click_rate | Product ad click rate |
| ad_click_rate | Ad click rate |
| ad_conversion_rate | Ad conversion rate |
| ad_video_view_rate_2s | 2-second ad video view rate |
| ad_video_view_rate_6s | 6-second ad video view rate |
| ad_video_view_rate_p25 | 25% ad video view rate |
| ad_video_view_rate_p50 | 50% ad video view rate |
| ad_video_view_rate_p75 | 75% ad video view rate |
| ad_video_view_rate_p100 | 100% ad video view rate |
``` 


#### For LIVE GMV Max Campaigns

``` xtable
|Field{30%}|Description{70%}|
|---|---|
| cost | Cost |
| net_cost | Net cost |
| orders | Orders |
| cost_per_order | Cost per order |
| gross_revenue | Gross revenue |
| roi | ROI |
| live_views | LIVE views |
| cost_per_live_view | Cost per LIVE view |
| 10_second_live_views | 10-second LIVE views |
| cost_per_10_second_live_view | Cost per 10-second LIVE view |
| live_follows | LIVE follows |
``` 


### Example
To find comprehensive code examples for running GMV Max Campaign reports, see [Metrics in GMV Max Campaign reports](https://business-api.tiktok.com/portal/docs?id=1824722485971009).

## Response

``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data.|
#| total_metrics | object | Returned only when `enable_total_metrics` is set to `true` in the request. <br><br>The total added-up data for your requested metrics. |
##| &#123;metric_name&#125; | string | Returned metrics, which are determined by the `metrics` field specified in your request. |
#| list | object[] | Data list. |
##| dimensions | object | All requested dimension data. |
###| &#123;dimension_name&#125;  | string | Returned dimensions, which are determined by the `dimensions` field specified in your request. |
##| metrics | object | All requested metric data. |
###| &#123;metric_name&#125;  | string | Returned metrics, which are determined by the `metrics` field specified in your request. |
#| page_info | object | Pagination information. |
##| page | integer | Current page number. |
##| page_size| integer | Page size. |
##| total_number | integer | Total number of results. |
##| total_page| integer | Total pages of results. |
```

### Example
```xcodeblock 
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "list": [
            {
                "dimensions": {
                    "item_id": "{{item_id}}"
                },
                "metrics": {
                    "ad_click_rate": "16.67",
                    "ad_conversion_rate": "0.00",
                    "ad_video_view_rate_2s": "40.00",
                    "ad_video_view_rate_6s": "40.00",
                    "ad_video_view_rate_p100": "20.00",
                    "ad_video_view_rate_p25": "40.00",
                    "ad_video_view_rate_p50": "20.00",
                    "ad_video_view_rate_p75": "20.00",
                    "cost": "0.01",
                    "cost_per_order": "0.00",
                    "creative_delivery_status": "UNAVAILABLE",
                    "currency": "USD",
                    "gross_revenue": "0.00",
                    "orders": "0",
                    "product_click_rate": "16.67",
                    "product_clicks": "1",
                    "product_impressions": "6",
                    "roi": "0.00"
                }
            },
            {
                "dimensions": {
                    "item_id": "{{item_id}}"
                },
                "metrics": {
                    "ad_click_rate": "0.00",
                    "ad_conversion_rate": "0.00",
                    "ad_video_view_rate_2s": "0.00",
                    "ad_video_view_rate_6s": "0.00",
                    "ad_video_view_rate_p100": "0.00",
                    "ad_video_view_rate_p25": "0.00",
                    "ad_video_view_rate_p50": "0.00",
                    "ad_video_view_rate_p75": "0.00",
                    "cost": "0.00",
                    "cost_per_order": "0.00",
                    "creative_delivery_status": "UNAVAILABLE",
                    "currency": "USD",
                    "gross_revenue": "0.00",
                    "orders": "0",
                    "product_click_rate": "0.00",
                    "product_clicks": "0",
                    "product_impressions": "0",
                    "roi": "0.00"
                }
            }
        ],
        "page_info": {
            "page": 1,
            "page_size": 1000,
            "total_number": 246,
            "total_page": 1
        }
    }
}
(/code)
```


