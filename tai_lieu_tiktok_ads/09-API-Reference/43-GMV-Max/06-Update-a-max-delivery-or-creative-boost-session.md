---
title: "Update a max delivery or creative boost session"
doc_id: 1835247009119233
path: "API Reference / GMV Max / Update a max delivery or creative boost session"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Update a max delivery or creative boost session

Use this endpoint to update a max delivery session for a specific product or a creative boost session for a specific video within a Product GMV Max Campaign.

><span style="color:DodgerBlue">**Note**</span><br> 
> You cannot update max delivery or creative boost sessions that have ended.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/campaign/gmv_max/session/update/

**Method** POST

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type.<br>Allowed format: `application/json`.  |
```

**Parameters**
### Parameters for updating a max delivery session
``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
|advertiser_id{Required}|string|Advertiser ID.|
| campaign_id  {Required} | string | The ID of the Product GMV Max Campaign.<br><br><ul><li>To create a Product GMV Max Campaign and obtain the campaign ID, use [/campaign/gmv_max/create/](https://business-api.tiktok.com/portal/docs?id=1822000988713089) and set `shopping_ads_type` to `PRODUCT`.</li><li>To filter existing Product GMV Max Campaigns within your ad account, use [/gmv_max/campaign/get/](https://business-api.tiktok.com/portal/docs?id=1826463372290177) and set `filtering` to `&#123;"gmv_max_promotion_types":["PRODUCT_GMV_MAX"]&#125;`.</li></ul>|
|store_id{Required}|string|The ID of the TikTok Shop.<br><br>To obtain the ID of the TikTok Shop that is associated with a GMV Max Campaign, use [/campaign/gmv_max/info/](https://business-api.tiktok.com/portal/docs?id=1822000968821762). |
|session_id{Required}|string|The ID of the max delivery session for a product in the Product GMV Max Campaign.<br><br>To obtain the ID list of active max delivery sessions within a Product GMV Max Campaign, use [/campaign/gmv_max/session/list/](https://business-api.tiktok.com/portal/docs?id=1835246996436162) and select sessions with `bid_type` as `NO_BID`.|
|session{Required}|object|Details about the max delivery mode for the campaign.|
#|budget|float|The daily max delivery budget.<br>Max delivery uses additional budget, separate from target ROI optimization budget, paced for more predictable ad spending. Your target ROI budget won't be used for products using max delivery while max delivery is active.<br><br>Value range: ≥ 10 (USD).|
#|schedule_type|string|Schedule type for max delivery.<br><br>Enum values:<ul><li>`SCHEDULE_FROM_NOW`: To enable the max delivery mode for the product continuously after the `schedule_start_time`, until the campaign scheduled end time.</li><li>`SCHEDULE_START_END`: To enable the max delivery mode for the product between the `schedule_start_time` and `schedule_end_time`.</li></ul>|
#|schedule_start_time|string|The start time for the max delivery mode, in the format of `YYYY-MM-DD HH:MM:SS` (UTC+0).<br><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>The start time cannot be earlier than the current time + 5 minutes.</li><li>The start time should be later than or equal to the campaign start time.</li><li>Your daily budget will be spent evenly throughout the day. If your max delivery duration is less than 24 hours in a given day, your full budget will still be spent.</li><li>Max delivery will be active between your set start and end times for your selected products. If your campaign is still active when max delivery ends, the optimization mode for those products will automatically change to target ROI.</li></ul>|
#|schedule_end_time {+Conditional}|string|<ul><li>Required when `schedule_type` is `SCHEDULE_START_END`.</li><li>Not supported when `schedule_type` is `SCHEDULE_FROM_NOW`.</li></ul><br>The end time for the max delivery mode, in the format of `YYYY-MM-DD HH:MM:SS` (UTC+0).<br><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>The end time cannot be earlier than the current time.</li><li>The end time should be earlier or equal to the campaign end time.</li></ul>|
```

#### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/campaign/gmv_max/session/update/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "store_id": "{{store_id}}",
    "campaign_id": "{{campaign_id}}",
    "session_id": "{{session_id}}",
    "session":
    {
        "schedule_start_time": "{{schedule_start_time}}"
    }
}'
(/code)
```

### Parameters for updating a creative boost session
``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| advertiser_id  {Required} | string | Advertiser ID. |
| campaign_id  {Required} | string | The ID of the Product GMV Max Campaign.<br><br><ul><li>To create a Product GMV Max Campaign and obtain the campaign ID, use [/campaign/gmv_max/create/](https://business-api.tiktok.com/portal/docs?id=1822000988713089) and set `shopping_ads_type` to `PRODUCT`.</li><li>To filter existing Product GMV Max Campaigns within your ad account, use [/gmv_max/campaign/get/](https://business-api.tiktok.com/portal/docs?id=1826463372290177) and set `filtering` to `&#123;"gmv_max_promotion_types":["PRODUCT_GMV_MAX"]&#125;`.</li></ul>|
| store_id  {Required} | string | The ID of the TikTok Shop.<br><br>To obtain the ID of the TikTok Shop that is associated with a GMV Max Campaign, use [/campaign/gmv_max/info/](https://business-api.tiktok.com/portal/docs?id=1822000968821762). |
|session_id{Required}|string|The ID of the creative boost session for a video in the Product GMV Max Campaign.<br><br>To obtain the ID list of active creative boost sessions within a Product GMV Max Campaign, use [/campaign/gmv_max/session/list/](https://business-api.tiktok.com/portal/docs?id=1835246996436162) and select sessions with `bid_type` as `CREATIVE_NO_BID`.|
| session  {Required} | object | Details about the creative boost settings for the campaign. |
#| budget | float | The daily creative boost budget.<br>Creative boost utilizes budget, separate from campaign budget, paced throughout your schedule for more predictable ad spending.<br><br>Creative boost budget isn't included in calculations for your campaign's ROI protection. <br><br>Value range: ≥ 10 (USD). |
#| schedule_type| string | Schedule type for creative boost.<br><br>Enum values: <ul><li>`SCHEDULE_FROM_NOW`: To enable creative boost for the product continuously after the current time, until the campaign scheduled end time.</li><li>`SCHEDULE_START_END`: To enable creative boost for the product between the current time and `schedule_end_time`.</li></ul>Default value: `SCHEDULE_FROM_NOW`. |
#| schedule_end_time{+Conditional} | string | <ul><li> Required when `schedule_type` is `SCHEDULE_START_END`.</li><li>Not supported when `schedule_type` is `SCHEDULE_FROM_NOW`.</li></ul><br>The end time for creative boost, in the format of `YYYY-MM-DD HH:MM:SS` (UTC+0).<br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>The end time cannot be earlier than the current time.</li><li>The end time should be earlier or equal to the campaign end time.</li></ul></p> |
```


#### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/campaign/gmv_max/session/update/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "store_id": "{{store_id}}",
    "campaign_id": "{{campaign_id}}",
    "session_id": "{{session_id}}",
    "session":
    {
        "budget": {{budget}}
    }
}'
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
```

### Example
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {}
}
(/code)
```
