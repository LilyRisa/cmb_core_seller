---
title: "Get max delivery or creative boost sessions within a campaign"
doc_id: 1835246996436162
path: "API Reference / GMV Max / Get max delivery or creative boost sessions within a campaign"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get max delivery or creative boost sessions within a campaign

Use this endpoint to retrieve all active max delivery sessions for products or creative boost sessions for videos within a Product GMV Max Campaign.

Sessions that have ended cannot be accessed via `/campaign/gmv_max/session/list/`. To access details of those sessions, use [/campaign/gmv_max/session/get/](https://business-api.tiktok.com/portal/docs?id=1835247031331842).


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/campaign/gmv_max/session/list/

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
| advertiser_id  {Required} | string | Advertiser ID. |
| campaign_id  {Required} | string | The ID of the Product GMV Max Campaign.<br><br><ul><li>To create a Product GMV Max Campaign and obtain the campaign ID, use [/campaign/gmv_max/create/](https://business-api.tiktok.com/portal/docs?id=1822000988713089) and set `shopping_ads_type` to `PRODUCT`.</li><li>To filter existing Product GMV Max Campaigns within your ad account, use [/gmv_max/campaign/get/](https://business-api.tiktok.com/portal/docs?id=1826463372290177) and set `filtering` to `&#123;"gmv_max_promotion_types":["PRODUCT_GMV_MAX"]&#125;`.</li></ul>|
```

### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/campaign/gmv_max/session/list/?advertiser_id={{advertiser_id}}&campaign_id={{campaign_id}}' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```
## Response

``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://business-api.tiktok.com/portal/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://business-api.tiktok.com/portal/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data.|
#|session_list|object[]|The list of max delivery sessions for products or creative boost sessions for videos within the Product GMV Max Campaign.|
##|campaign_id|string|The ID of the Product GMV Max Campaign.|
##| bid_type|string|Session type.<br><br>Enum values: <ul><li>`NO_BID`: max delivery session.</li><li>`CREATIVE_NO_BID`: creative boost session.</li></ul>|
##| session_id|string|The ID of the max delivery session for the product or creative boost session for the video in the Product GMV Max Campaign.<br><br><ul><li>When `bid_type` is `NO_BID`, this field represents the ID of a max delivery session.</li><li>When `bid_type` is `CREATIVE_NO_BID`, this field represents the ID of a creative boost session.</li></ul> |
##| budget|float|The daily max delivery budget or daily creative boost budget.<br><br><ul><li>Max delivery uses additional budget, separate from target ROI optimization budget, paced for more predictable ad spending. Your target ROI budget won’t be used for products using max delivery while max delivery is active.</li><li>Creative Boost is a functionality within Product GMV Max that allows sellers to manually promote specific videos by allocating extra daily budget.</li></ul> |
##|product_list|object[]|Details about the specific products.|
###|spu_id|string|The Product SPU (standard product unit) ID.|
##|schedule_type|string|Schedule type for max delivery or creative boost.<br><br>Enum values:<br><ul><li>`SCHEDULE_FROM_NOW`: To enable the max delivery or creative boost mode for the product continuously after the `schedule_start_time`, until the campaign scheduled end time.</li><li>`SCHEDULE_START_END`: To enable the max delivery or creative boost mode for the product between the `schedule_start_time` and `schedule_end_time`.</li></ul>|
##|schedule_start_time|string|The start time for the max delivery or creative boost mode, in the format of `YYYY-MM-DD HH:MM:SS` (UTC+0).|
##|schedule_end_time|string|The end time for the max delivery or creative boost mode, in the format of `YYYY-MM-DD HH:MM:SS` (UTC+0).|
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
        "session_list": [
            {
                "bid_type": "NO_BID",
                "budget": {{budget}},
                "campaign_id": "{{campaign_id}}",
                "product_list": [
                    {
                        "spu_id": "{{spu_id}}"
                    }
                ],
                "schedule_end_time": "{{schedule_end_time}}",
                "schedule_start_time": "{{schedule_start_time}}",
                "schedule_type": "SCHEDULE_FROM_NOW",
                "session_id": "{{session_id}}"
            }
        ]
    }
}
(/code)
```
