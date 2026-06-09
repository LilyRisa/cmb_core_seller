---
title: "Get Creative Fatigue Detection results"
doc_id: 1767568466842626
path: "API Reference / Creative Tools / Get Creative Fatigue Detection results"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get Creative Fatigue Detection results

Use this endpoint to detect whether Creative Fatigue occurred for an ad within a specified time range in the past. 

Optionally, if you want to subscribe to the fatigue status of an ad, all ads within an ad group, or all ads within an advertiser account via webhooks, see the section [Webhook subscription-Subscribe to the fatigue status of your ad](https://ads.tiktok.com/marketing_api/docs?id=1738964126095362#item-link-Subscribe%20to%20the%20fatigue%20status%20of%20your%20ad).

To find out a detailed introduction of the Creative Fatigue Detection feature, see [here](https://ads.tiktok.com/marketing_api/docs?id=1767568085608450). 

><span style="color:DodgerBlue">**Note**</span><br> 
> Creative Fatigue Detection is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative. 


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/creative_fatigue/get/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| advertiser_id {Required} | string | Advertiser ID. |
| ad_id {Required} | string | The ID of the ad that you want to get Creative Fatigue detection results for. |
| filtering {Required} | object | Filtering conditions. |
#| start_date {Required} | string | Query start date (closed interval), in the format of `YYYY-MM-DD` (advertiser account time zone). <br>You can only specify a date within the last 60 days. |
#| end_date {Required} | string | Query end date (open interval), in the format of `YYYY-MM-DD` (advertiser account time zone). <br>You can only specify a date within the last 60 days. |
| page | number | Current page number. <br>Default value: 1. <br>Value range: ≥ 1. |
| page_size | number | Page size. <br> Default value: 10.<br>Value range: [1, 500]. |
```
### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/creative_fatigue/get/' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "ad_id": "{{ad_id}}",
    "filtering": {
        "start_date": "2023-05-22",
        "end_date": "2023-05-24"
    },
    "page": 1,
    "page_size": 500
}'
(/code)
```
## Response

``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|-|-|-|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data.|
#| list | object[] | Information about the Creative Fatigue detection results. |
##| adgroup_id | string | ID of the ad group that the ad (`ad_id`) belongs to. |
##| ad_id | string | ID of the ad. |
##| date | string | The date for which the values of the `metrics` are retrieved. |
##| metrics | object | Information about data of the metrics on the date (`date`). |
###| has_fatigue | boolean | Whether Creative Fatigue is detected. |
###| fatigue_index | number | The "Fatigue Index" is a comprehensive metric that considers day-over-day changes in CPA, cost, the new users reach rate, and other performance metrics. A higher index value indicates a greater degree of fatigue.|
###| dnu | number | DNU. The number of daily new users that the ad attracts. |
###| dnu_ratio | number | DNU ratio. <br>This metric is calculated by dividing the number of daily new users attracted by the ad on a specific date by the maximum number of daily new users that the ad attracted in the last 60 days. |
###| spend | number | Total cost, which is the estimated total amount of money you've spent on the ad, in the default currency for the ad account. |
###| cost_per_conversion | number | This metric returns actual value when the ad is within a non-iOS 14 Dedicated Campaign, and returns `0.0` when the ad is within an iOS 14 Dedicated Campaign. You can check whether a campaign is an iOS 14 Dedicated Campaign via the value of `campaign_type` returned by [/campaign/get/](https://ads.tiktok.com/marketing_api/docs?id=1739315828649986).<br><br>CPA, which is the average amount of money spent on a conversion from the ad, in the default currency for the ad account. The total count is calculated based on the time each ad impression occurred. |
###| skan_cost_per_conversion | number | This metric returns actual value when the ad is within an iOS 14 Dedicated Campaign, and returns `0.0` when the ad is within a non-iOS 14 Dedicated Campaign. You can check whether a campaign is an iOS 14 Dedicated Campaign via the value of `campaign_type` returned by [/campaign/get/](https://ads.tiktok.com/marketing_api/docs?id=1739315828649986). <br><br>CPA (SKAN), the average amount of money spent on a conversion from the ad, in the default currency for the ad account. The total count is based on when the events are returned through the SKAdNetwork API. There may be a delay between the actual conversion time and when the conversion is reported. |
#| page_info | object | Pagination information. |
##| page | number | Current page number. |
##| page_size | number | Current page size. |
##| total_number | number | Total number of results. |
##| total_page | number | Total number of pages. |
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
        "page_info": {
            "page_size": 500,
            "total_page": 1,
            "total_number": 2,
            "page": 1
        },
        "list": [
            {
                "adgroup_id": "{{adgroup_id}}",
                "date": "2023-05-22",
                "metrics": {
                    "dnu": 26832,
                    "has_fatigue": false,
                    "skan_cost_per_conversion": 0.0,
                    "cost_per_conversion": 11.39,
                    "fatigue_index": 0.43000000000000005,
                    "dnu_ratio": 0.57,
                    "spend": 125.24
               },
                "ad_id": "{{ad_id}}"
            },
            {
                "adgroup_id": "{{adgroup_id}}",
                "date": "2023-05-23",
                "metrics": {
                    "dnu": 32741,
                    "has_fatigue": false,
                    "skan_cost_per_conversion": 0.0,
                    "cost_per_conversion": 13.65,
                    "fatigue_index": 0.30000000000000004,
                    "dnu_ratio": 0.7,
                    "spend": 136.51
                }
                "ad_id": "{{ad_id}}"
            }
        ]
    }
}
(/code)
```
