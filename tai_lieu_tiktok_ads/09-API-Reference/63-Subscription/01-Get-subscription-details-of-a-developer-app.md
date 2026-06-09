---
title: "Get subscription details of a developer app"
doc_id: 1739093832125442
path: "API Reference / Subscription / Get subscription details of a developer app"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get subscription details of a developer app

Use this endpoint to get the subscription details of a developer app. 

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/subscription/get/|/v1.3/subscription/get/|
|Request parameter name|`object`|`subscribe_entity`|
|Request parameter data type|`app_id`: number|`app_id`: string|
|Response parameter name|`list`<br>`url`<br>`object`|`subscriptions`<br>`callback_url`<br>`subscribe_entity`|
|Response parameter data type |`app_id`: number<br>`advertiser_id`: number<br>`page_id`: number<br>`adgroup_id`: number<br>`ad_id`: number<br>`subscription_id`:number|`app_id`: string<br>`advertiser_id`: string<br>`page_id`: string<br>`adgroup_id`: string<br>`ad_id`: string<br>`subscription_id`:string|
|Response parameters deprecated in v1.3|  /  |`tcm_account_id` |
|New response parameters| / | `tto_tcm_account_id`<br>`lead_source`<br>`product_filters`<br>`notify_frequency`|
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/subscription/get/

**Method** GET

**Parameters**
```xtable
|Field|Type|Description|
|---|---|---|
|app_id{Required}|string| ID of your developer application. You can find the <strong>App ID</strong> by navigating to <strong>[My Apps](https://ads.tiktok.com/marketing_api/apps/)</strong> > <strong>App Detail</strong> > <strong>Basic Information</strong>.|
|secret {Required}|string| Secret of your developer application. You can find the <strong>Secret</strong> by navigating to <strong>[My Apps](https://ads.tiktok.com/marketing_api/apps/)</strong> > <strong>App Detail</strong> > <strong>Basic Information</strong>. |
|subscribe_entity |string| The object that you want to subscribe to.<br> Enum values:  <ul><li>`REPORT_DATA_CHANGE`: the data change in any of the reporting metrics `spend`, `impressions`, `clicks`, and `video_play_actions` for one or more ad accounts. </li><li>`AD_ACCOUNT_SUSPENSION`: the suspension statuses of ad accounts.</li><li>`LEAD`: leads.</li><li>`AD_GROUP`: the review status of an ad group.</li><li>`AD`: the review status of an ad.</li><li>`TCM_VIDEOS`: the linking of a TikTok video to a TTO campaign.</li><li>`CREATIVE_FATIGUE`: the fatigue status of an ad, ads within an ad group, or ads within an advertiser account.</li><li>`API_SERVICE_STATUS`: the status of API service.</li></ul>|
|page |number|The current page number.  Default value: `1`. Value range: ≥ 1. |
|page_size |number|The page size. Default value:`10`. Value range: 1-1000|
```

### Example
```xcodeblock
(code curl http)
curl --get -H "Access-Token:xxx" \
--data-urlencode "secret=SECRET" \
--data-urlencode "object=OBJECT" \
--data-urlencode "app_id=APP_ID" \
--data-urlencode "page_size=PAGE_SIZE" \
--data-urlencode "page=PAGE" \
https://business-api.tiktok.com/open_api/v1.3/subscription/get/
(/code)
```

## Response
```xtable
|Field|Type|Description|
|---|---|---|
|code |number|The response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message |string|The response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The unique ID of the request.  |
|data |object|The returned data. |
#|subscriptions|object[]|Subscription information.  |
##|app_id |string| The App ID. |
##|subscription_id |string| The subscription ID |
##|subscribe_entity |string|  The object that you want to subscribe to.<br> Enum values:  <ul><li>`REPORT_DATA_CHANGE`: the data change in any of the reporting metrics `spend`, `impressions`, `clicks`, and `video_play_actions` for one or more ad accounts. </li><li>`AD_ACCOUNT_SUSPENSION`: the suspension statuses of ad accounts.</li><li>`LEAD`: leads.</li><li>`AD_GROUP`: the review status of an ad group.</li><li>`AD`: the review status of an ad.</li><li>`TCM_VIDEOS`: the linking of a TikTok video to a TTO campaign.</li><li>`CREATIVE_FATIGUE`: the fatigue status of an ad, ads within an ad group, or ads within an advertiser account.</li><li>`API_SERVICE_STATUS`: the status of API service.</li></ul>|
##|callback_url |string| The callback URL. |
##|subscription_detail |object| Details about the subscription.|
###|product_filters|string[]|The list of API products to subscribe to.<br><br>Enum values:<ul><li>`BUSINESS_CENTER_API`: Business Center API.</li><li>`CREATIVES_API`: Creatives API.</li><li>`CATALOG_API`: Catalog Management API.</li><li>`TIKTOK_STORE_API`: TikTok Store API.</li><li>`CAMPAIGN_API`: Campaign Management API.</li><li>`REPORTING_API`: Reporting API.</li><li>`AUDIENCE_API`: Audience API.</li><li>`STREAMING_API`: Streaming API.</li><li>`EVENTS_API`: Events API.</li><li>`ACCOUNTS_API`: Accounts API.</li><li>`MENTIONS_API`: Mentions API.</li><li>`TIKTOK_ONE_API`: TikTok One API.</li><li>`DISCOVERY_API`: Discovery API.</li><li>`SPARK_RECOMMEND_API`: Spark Ads Recommendation API.</li><li>`BUSINESS_MESSAGING_API`: Business Messaging API.</li></ul>|
###| bc_id | string | When `subscribe_entity` is `AD_ACCOUNT_SUSPENSION`, either `bc_id` or `advertiser_ids` is returned. <br><br>Business Center ID.<br>You are subscribed to the suspension statuses of all ad accounts owned by the Business Center. |
###| advertiser_ids | string[] |<ul><li>When `subscribe_entity` is `REPORT_DATA_CHANGE`, `advertiser_ids` is returned.</li><li> When `subscribe_entity` is `AD_ACCOUNT_SUSPENSION`, either `bc_id` or `advertiser_ids` is returned.</li></ul><br> A list of advertiser IDs. <br> <br><ul><li>When `subscribe_entity` is `REPORT_DATA_CHANGE`, you are subscribed to the data change in any of the reporting metrics `spend`, `impressions`, `clicks`, and `video_play_actions` for these ad accounts.</li><li>When `subscribe_entity` is `AD_ACCOUNT_SUSPENSION`, you are subscribed to the suspension statuses of these ad accounts, which can be owned by different Business Centers.</li></ul> |
###|notify_frequency |string|Returned when `subscribe_entity` is `REPORT_DATA_CHANGE`.<br><br>The frequency of the notification. <br><br>Enum values:  <ul><li>`5_MINUTE`: every five minutes.</li><li>`15_MINUTE`: every 15 minutes.</li><li>`30_MINUTE`: every 30 minutes.</li><li>`60_MINUTE`: every 60 minutes.</li></ul>|
###| lead_source | string | Returned only when `subscribe_entity` is `LEAD`. <br><br> The source of the leads to subscribe to.<br><br>Enum values:<ul><li>`INSTANT_FORM`: leads generated through Instant Forms.</li><li>`DIRECT_MESSAGE`: leads generated from direct messages of the associated Business Account.</li></ul>|
###|advertiser_id |string| The advertiser ID |
###|page_id |string| The ID of the Instant Form that you want to subscribe to.<br><br><p><span style="color:darkred"><b>Note</b></span>: When `lead_source` is `DIRECT_MESSAGE`, this field will be null.  |
###|adgroup_id |string|Ad group ID |
###|ad_id |string|Ad ID |
###|tto_tcm_account_id |string|Returned only when `subscribe_entity` is `TCM_VIDEOS`.<br><br>The ID of a TTO Creator Marketplace account.|
#|page_info |object | The page information. |
##|page |number | The current page number. |
##|page_size |number | The page size. |
##|total_number |number | The total number of records. |
##|total_page |number | The total number of pages. |
```

### Example
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {
        "page_info": {
            "total_number": 1,
            "page": 1,
            "page_size": 10,
            "total_page": 1
        },
        "subscriptions": [
            {
                "callback_url": "https://your.callback.url/",
                "subscription_detail": {
                    "advertiser_id": "123",
                    "page_id": null
                },
                "subscribe_entity": "LEAD",
                "subscription_id": "456",
                "app_id": "789"
            }
        ]
    },
    "request_id": "20210315104957010115153194073C1EB0"
}
(/code);
```


