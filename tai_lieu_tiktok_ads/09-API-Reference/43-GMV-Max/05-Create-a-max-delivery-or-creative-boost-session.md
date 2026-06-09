---
title: "Create a max delivery or creative boost session"
doc_id: 1835246967275522
path: "API Reference / GMV Max / Create a max delivery or creative boost session"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create a max delivery or creative boost session

Use this endpoint to create a max delivery session for a specific product or a creative boost session for a specific video in a Product GMV Max Campaign.

- Max delivery for GMV Max uses an additional budget and maximizes gross revenue for selected products in your campaign over a selected period of time. Learn more [about Max delivery optimization for GMV Max](https://ads.tiktok.com/help/article/about-max-delivery-optimization-for-gmv-max). 
- Creative Boost is a functionality within Product GMV Max that allows sellers to manually promote specific videos by allocating extra daily budget. 


## Before you start
### Prerequisites for creating a max delivery session
- Ensure that the current optimization mode of the campaign is Target ROI. Use [/gmv_max/report/get/](https://business-api.tiktok.com/portal/docs?id=1824721673497601) to retrieve campaign details and verify that the `bid_type` metric is `CUSTOM`. Learn about [available GMV Max Campaign report metrics](https://business-api.tiktok.com/portal/docs?id=1824722485971009).
- To obtain a valid `campaign_id`, you can use either of the following methods:
  - Create a new Product GMV Max Campaign using [/campaign/gmv_max/create/](https://business-api.tiktok.com/portal/docs?id=1822000988713089) with `shopping_ads_type` set to `PRODUCT`.
  - Filter existing Product GMV Max Campaigns within your ad account using [/gmv_max/campaign/get/](https://business-api.tiktok.com/portal/docs?id=1826463372290177) with `filtering` set to `{"gmv_max_promotion_types":["PRODUCT_GMV_MAX"]}`.

### Prerequisites for creating a creative boost session
- Ensure that the campaign is in active status. 
	- To filter active Product GMV Max Campaigns within your ad account, use [/gmv_max/campaign/get/](https://business-api.tiktok.com/portal/docs?id=1826463372290177) and set `filtering` to `{"gmv_max_promotion_types":["PRODUCT_GMV_MAX"], "primary_status":"STATUS_DELIVERY_OK"}`.
- Ensure that for the product associated with the video that you want to enable creative boost for, no max delivery session is currently set.
    - To confirm this, use [/campaign/gmv_max/session/list/](https://business-api.tiktok.com/portal/docs?id=1835246996436162) and check the `spu_id` associated with any sessions where `bid_type` is `NO_BID`.
 - Ensure that creative boost is enabled for fewer than 100 videos. Within one Product GMV Max Campaign, you can enable creative boost for at most 100 videos.
    - To confirm this, use [/campaign/gmv_max/session/list/](https://business-api.tiktok.com/portal/docs?id=1835246996436162) and check the number of sessions with `bid_type` as `CREATIVE_NO_BID` and a unique `item_id`.
- Ensure that the creative boost session you want to create for a video does not overlap with any existing creative boost sessions. At any given time, only one active creative boost session is allowed per video. 
    - To confirm this, use [/campaign/gmv_max/session/list/](https://business-api.tiktok.com/portal/docs?id=1835246996436162) and check the `schedule_start_time` and `schedule_end_time` for sessions where `bid_type` is `CREATIVE_NO_BID`.
- Ensure that the video you want to enable creative boost for does not need authorization and is not excluded, rejected, or unavailable.
    - To confirm this, use [/gmv_max/report/get/](https://business-api.tiktok.com/portal/docs?id=1824721673497601) and [retrieve the creative-level data](https://business-api.tiktok.com/portal/docs?id=1824722485971009#item-link-For%20creatives%20with%20statuses), including the metric `creative_delivery_status`. The `creative_delivery_status` of the video cannot be `AUTHORIZATION_NEEDED`, `EXCLUDED`, `REJECTED`, or `UNAVAILABLE`.
	
## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/campaign/gmv_max/session/create/

**Method** POST

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type.<br>Allowed format: `application/json`.  |
```

**Parameters**

### Parameters for creating a max delivery session
``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| advertiser_id  {Required} | string | Advertiser ID. |
| campaign_id  {Required} | string | The ID of the Product GMV Max Campaign.<br><br><ul><li>To create a Product GMV Max Campaign and obtain the campaign ID, use [/campaign/gmv_max/create/](https://business-api.tiktok.com/portal/docs?id=1822000988713089) and set `shopping_ads_type` to `PRODUCT`.</li><li>To filter existing Product GMV Max Campaigns within your ad account, use [/gmv_max/campaign/get/](https://business-api.tiktok.com/portal/docs?id=1826463372290177) and set `filtering` to `&#123;"gmv_max_promotion_types":["PRODUCT_GMV_MAX"]&#125;`.</li></ul>|
| store_id  {Required} | string | The ID of the TikTok Shop.<br><br>To obtain the ID of the TikTok Shop that is associated with a GMV Max Campaign, use [/campaign/gmv_max/info/](https://business-api.tiktok.com/portal/docs?id=1822000968821762). |
| session  {Required} | object | Details about the max delivery mode for the campaign. |
#| bid_type{Required} | string | The session type.<br><br>Enum values: <ul><li>`NO_BID`: max delivery session. Learn about <a href="#item-link-Prerequisites for creating a max delivery session">the prerequisites for creating a max delivery session</a>.</li></ul>Default value: `NO_BID`. <br><br><p><span style="color:darkred"><b>Note</b></span>: To create a creative boost session, see <a href="#item-link-Parameters for creating a creative boost session">Parameters for creating a creative boost session</a> and set `bid_type` to `CREATIVE_NO_BID`.</p> |
#| product_list {Required}  | object[] | Details about the specific products.<br><br>Max size: 1. |
##| spu_id {Required}  | string | The Product SPU (standard product unit) ID.<br><br>To obtain the list of SPU IDs for products within a TikTok Shop, use [/store/product/get/](https://business-api.tiktok.com/portal/docs?id=1793482248880130) and set `ad_creation_eligible` to `GMV_MAX` and select `item_group_id` values where `status` is `AVAILABLE`. |
#| budget  {Required} | float | The daily max delivery budget.<br>Max delivery uses additional budget, separate from target ROI optimization budget, paced for more predictable ad spending. Your target ROI budget won’t be used for products using max delivery while max delivery is active.<br><br>Value range: ≥ 10 (USD). |
#| schedule_type | string | Schedule type for max delivery.<br><br>Enum values:<ul><li>`SCHEDULE_FROM_NOW`: To enable the max delivery mode for the product continuously after the `schedule_start_time`, until the campaign scheduled end time.</li><li>`SCHEDULE_START_END`: To enable the max delivery mode for the product between the `schedule_start_time` and `schedule_end_time`.</li></ul><br>Default value: `SCHEDULE_FROM_NOW`. |
#| schedule_start_time | string | The start time for the max delivery mode, in the format of `YYYY-MM-DD HH:MM:SS` (UTC+0).<br><br>If not specified, this field will default to the current time.<br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>The start time cannot be earlier than the current time.</li><li>The start time should be later than or equal to the campaign start time.</li><li>Your daily budget will be spent evenly throughout the day. If your max delivery duration is less than 24 hours in a given day, your full budget will still be spent.</li><li>Max delivery will be active between your set start and end times for your selected products. If your campaign is still active when max delivery ends, the optimization mode for those products will automatically change to target ROI.</li></ul> |
#| schedule_end_time  {+Conditional} | string | <ul><li>Required when `schedule_type` is `SCHEDULE_START_END`.</li><li> Not supported when `schedule_type` is `SCHEDULE_FROM_NOW`.</li></ul><br>The end time for the max delivery mode, in the format of `YYYY-MM-DD HH:MM:SS` (UTC+0).<br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>The end time cannot be earlier than the current time.</li><li>The end time should be earlier or equal to the campaign end time.</li></ul> |
```


#### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/campaign/gmv_max/session/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "store_id": "{{store_id}}",
    "campaign_id": "{{campaign_id}}",
    "session":{
		"bid_type":"NO_BID",
        "product_list":[
            {
                "spu_id": "{{spu_id}}"
            }
        ],
        "budget": {{budget}},
        "schedule_start_time": "{{schedule_start_time}}"
    }
}'
(/code)
```

### Parameters for creating a creative boost session
``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| advertiser_id  {Required} | string | Advertiser ID. |
| campaign_id  {Required} | string | The ID of the Product GMV Max Campaign.<br><br><ul><li>To create a Product GMV Max Campaign and obtain the campaign ID, use [/campaign/gmv_max/create/](https://business-api.tiktok.com/portal/docs?id=1822000988713089) and set `shopping_ads_type` to `PRODUCT`.</li><li>To filter existing Product GMV Max Campaigns within your ad account, use [/gmv_max/campaign/get/](https://business-api.tiktok.com/portal/docs?id=1826463372290177) and set `filtering` to `&#123;"gmv_max_promotion_types":["PRODUCT_GMV_MAX"]&#125;`.</li></ul>|
| store_id  {Required} | string | The ID of the TikTok Shop.<br><br>To obtain the ID of the TikTok Shop that is associated with a GMV Max Campaign, use [/campaign/gmv_max/info/](https://business-api.tiktok.com/portal/docs?id=1822000968821762). |
| session  {Required} | object | Details about the creative boost settings for the campaign. |
#| bid_type{Required} | string | Session type.<br><br>Enum values: <ul><li>`CREATIVE_NO_BID`: creative boost session. Learn about <a href="#item-link-Prerequisites for creating a creative boost session">the prerequisites for creating a creative boost session</a>.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: To create a max delivery session, see <a href="#item-link-Parameters for creating a max delivery session">Parameters for creating a max delivery session</a> and set `bid_type` to `NO_BID` or leave `bid_type` unspecified.</p> |
#| product_list {Required}  | object[] | Details about the specific products.<br><br>Max size: 1. |
##| spu_id {Required}  | string | The Product SPU (standard product unit) ID.<br><br>To obtain the list of SPU IDs for products within a TikTok Shop, use [/store/product/get/](https://business-api.tiktok.com/portal/docs?id=1793482248880130) and set `ad_creation_eligible` to `GMV_MAX` and select `item_group_id` values where `status` is `AVAILABLE`. |
#| item_id{Required} | string | The ID of the video to enable creative boost for.<br><br>To obtain the list of TikTok videos within an existing GMV Max Campaign, use [/gmv_max/report/get/](https://business-api.tiktok.com/portal/docs?id=1824721673497601) and [retrieve the creative-level data](https://business-api.tiktok.com/portal/docs?id=1824722485971009#item-link-For%20creatives%20with%20statuses). You can get the TikTok video ID through the metric `item_id`. Ensure that the `creative_delivery_status` of the video is not `AUTHORIZATION_NEEDED`, `EXCLUDED`, `REJECTED`, or `UNAVAILABLE`. |
#| budget{Required} | float | The daily creative boost budget.<br>Creative boost utilizes budget, separate from campaign budget, paced throughout your schedule for more predictable ad spending.<br><br>Creative boost budget isn't included in calculations for your campaign's ROI protection. <br><br>Value range: ≥ 10 (USD). |
#| schedule_type{Required} | string | Schedule type for creative boost.<br><br>Enum values: <ul><li>`SCHEDULE_FROM_NOW`: To enable creative boost for the product continuously after the current time, until the campaign scheduled end time.</li><li>`SCHEDULE_START_END`: To enable creative boost for the product between the current time and `schedule_end_time`.</li></ul>Default value: `SCHEDULE_FROM_NOW`. |
#| schedule_end_time{+Conditional} | string | <ul><li> Required when `schedule_type` is `SCHEDULE_START_END`.</li><li>Not supported when `schedule_type` is `SCHEDULE_FROM_NOW`.</li></ul><br>The end time for creative boost, in the format of `YYYY-MM-DD HH:MM:SS` (UTC+0).<br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>The end time cannot be earlier than the current time.</li><li>The end time should be earlier or equal to the campaign end time.</li></ul></p> |
```


#### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/campaign/gmv_max/session/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "store_id": "{{store_id}}",
    "campaign_id": "{{campaign_id}}",
    "session":{
        "bid_type":"CREATIVE_NO_BID",
        "product_list":[
            {
                "spu_id": "{{spu_id}}"
            }
        ],
        "item_id":"{{item_id}}",
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
#|session_id|string|The ID of the max delivery session for the product or of the creative boost session for the video in the Product GMV Max Campaign.|
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
        "session_id": "{{session_id}}"
    }
}
(/code)
```
