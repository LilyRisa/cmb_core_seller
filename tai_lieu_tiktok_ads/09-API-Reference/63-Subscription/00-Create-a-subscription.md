---
title: "Create a subscription"
doc_id: 1739092028876801
path: "API Reference / Subscription / Create a subscription"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create a subscription

Use this endpoint to subscribe to reporting metric data change, ad account suspension status, leads, and more. 

Depending on the subscription type you enable, you will receive notifications in one of the following cases:
- The data of the reporting metrics `spend`, `impressions`, `clicks`, or `video_play_actions` for one or more ad accounts has changed.
- One or more ad accounts are suspended or unsuspended.
- A new lead is created.
- Your ad is rejected during ad review.
- Your ad or ad group starts to experience fatigue.
- A TikTok video is linked to a TTO campaign.
- An incident involving one or more API products occurs, is updated, or is resolved.
<br>See [Subscribe to ad account Webhook events via Subscription API](https://business-api.tiktok.com/portal/docs?id=1810521739537409) for more details. 

When you subscribe to leads that are generated from a lead generation ad (instant form), please note that subscriptions are based on Subscription IDs (`subscription_id`), not page IDs (`page_id`).  A page can be included in multiple subscriptions. 

> <span style="color: dodgerblue">**Note**</span><br>
> - Subscribing to the reporting metric data changes of ad accounts is currently an allowlist-only feature. If you would like to access it, apply for the allowlisting using the [Allowlist Management](https://business-api.tiktok.com/portal/blog/detail/7102266224578396162) page.
> - Subscribing to the suspension statuses of ad accounts is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.
> - Subscriptions are independent. If you have two subscriptions to the same page, cancelling one subscription will not affect the other subscription.
> - If the <code>access_token</code> used to configure the Webhook is revoked (either by the developer or the advertiser), the Webhook subscription will no longer work, and no further Webhook events will be received. In such cases, regenerate the <code>access_token</code> and reconfigure the Webhook using the new access token.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/subscription/subscribe/|/v1.3/subscription/subscribe/|
|Request parameter name|`object`<br>`url`|`subscribe_entity`<br>`callback_url`|
|Request parameter data type|`advertiser_id`: number<br>`library_id`: number<br>`page_id`: number<br>`adgroup_id`: number<br>`ad_id`: number|`advertiser_id`: string<br>`library_id`: string<br>`page_id`: string<br>`adgroup_id`: string<br>`ad_id`: string|
|Request parameters deprecated in v1.3|  /  |`tcm_account_id`|
|New request parameters|  /  |`tto_tcm_account_id`<br>`lead_source`<br>`product_filters`<br>`notify_frequency` |
|Response parameter data type |`subscription_id`: number|`subscription_id`: string|
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/subscription/subscribe/

**Method** POST

**Header**
```xtable
|Field|Type|Description|
|---|---|---|
|Content-Type {Required}|string| The content type of the request. For this endpoint, use `application/json`.|
```

**Parameters**
```xtable
| Field|Type|Description |
|---|---|---|
|app_id{Required}|string| ID of your developer application. You can find the <strong>App ID</strong> by navigating to <strong>[My Apps](https://ads.tiktok.com/marketing_api/apps/)</strong> > <strong>App Detail</strong> > <strong>Basic Information</strong>.|
|secret {Required}|string| Secret of your developer application. You can find the <strong>Secret</strong> by navigating to <strong>[My Apps](https://ads.tiktok.com/marketing_api/apps/)</strong> > <strong>App Detail</strong> > <strong>Basic Information</strong>. |
|subscribe_entity{Required} |string| The object that you want to subscribe to.<br><br> Enum values:  <ul><li>`REPORT_DATA_CHANGE`: the data change in any of the reporting metrics `spend`, `impressions`, `clicks`, and `video_play_actions` for one or more ad accounts. You need to specify `advertiser_ids` simultaneously. To find the detailed description for these reporting metrics, see [Basic reports > Supported metrics](https://business-api.tiktok.com/portal/docs?id=1751443967255553).<br><br><p><span style="color:darkred"><b>Note</b></span>: To use the `REPORT_DATA_CHANGE` value, ensure that you have enabled the <b>Reporting</b> > <b>Consolidated Report</b> permission for your developer app.</p></li><li>`AD_ACCOUNT_SUSPENSION`: the suspension statuses of ad accounts.</li><li>`LEAD`: leads.<ul><li>TikTok is committed to providing "at least once delivery" for webhooks. Consequently, it’s possible that webhook endpoints may receive duplicate events. To mitigate potential issues, incorporate safeguards against processing duplicate events by designing your event handling to be idempotent.</li></ul></li><li>`AD_GROUP`: the review status of an ad group.</li><li>`AD`: the review status of an ad.</li><li>`TCM_VIDEOS`: the linking of a TikTok video to a TTO campaign.</li><li>`CREATIVE_FATIGUE`: the fatigue status of an ad, ads within an ad group, or ads within an advertiser account.</li><li>`API_SERVICE_STATUS`: the status of API service.</li></ul>|
|callback_url {Required} |string| The callback URL. |
|subscription_detail {Required}|object| Details about the subscription. |
#|access_token {Required}|string| The authorized access token. <br><br>It can be obtained through the [/oauth2/access_token/](https://business-api.tiktok.com/portal/docs?id=1738373164380162) endpoint.<br><br><p><span style="color:darkred"><b>Note</b></span>: If the `access_token` used to configure the Webhook is revoked (either by the developer or the advertiser), the Webhook subscription will no longer work, and no further Webhook events will be received. In such cases, regenerate the `access_token` and reconfigure the Webhook using the new access token.|
#|product_filters|string[]|Valid only when `subscribe_entity` is `API_SERVICE_STATUS`.<br><br>The list of API products to subscribe to.<br><br>Enum values:<ul><li>`BUSINESS_CENTER_API`: Business Center API.</li><li>`CREATIVES_API`: Creatives API.</li><li>`CATALOG_API`: Catalog Management API.</li><li>`TIKTOK_STORE_API`: TikTok Store API.</li><li>`CAMPAIGN_API`: Campaign Management API.</li><li>`REPORTING_API`: Reporting API.</li><li>`AUDIENCE_API`: Audience API.</li><li>`STREAMING_API`: Streaming API.</li><li>`EVENTS_API`: Events API.</li><li>`ACCOUNTS_API`: Accounts API.</li><li>`MENTIONS_API`: Mentions API.</li><li>`TIKTOK_ONE_API`: TikTok One API.</li><li>`DISCOVERY_API`: Discovery API.</li><li>`SPARK_RECOMMEND_API`: Spark Ads Recommendation API.</li><li>`BUSINESS_MESSAGING_API`: Business Messaging API.</li></ul><br>If `product_filters` is not specified, you will subscribe to the updates for all API products.|
#| lead_source | string | Valid only when `subscribe_entity` is `LEAD`. <br><br>The source of the leads to subscribe to.<br><br>Enum values:<ul><li>`INSTANT_FORM`: leads generated through Instant Forms.</li><li>`DIRECT_MESSAGE`: leads generated from direct messages of the associated Business Account.</li></ul><br>Default value: `INSTANT_FORM`.|
#| bc_id {+Conditional} | string | If `subscribe_entity` is `AD_ACCOUNT_SUSPENSION`, you need to pass in either `bc_id` or `advertiser_ids`.<br><br>Business Center ID. <br>You will subscribe to the suspension statuses of all ad accounts owned by the Business Center.<br><br><p><span style="color:darkred"><b>Note</b></span>: You need to have admin access to the Business Center. To obtain the list of Business Centers that you have admin access to, use [/bc/get/](https://business-api.tiktok.com/portal/docs?id=1737115687501826) and select Business Centers with the returned `user_role` as `ADMIN`. |
#| advertiser_ids {+Conditional} | string[] | <ul><li>If `subscribe_entity` is `REPORT_DATA_CHANGE`, this field is required.</li><li>If `subscribe_entity` is `AD_ACCOUNT_SUSPENSION`, you need to pass in either `bc_id` or `advertiser_ids`.</li></ul><br> A list of advertiser IDs.<br><br><ul><li>When `subscribe_entity` is `REPORT_DATA_CHANGE`, you will subscribe to the data change in any of the reporting metrics `spend`, `impressions`, `clicks`, and `video_play_actions` for these ad accounts.</li><li>When `subscribe_entity` is `AD_ACCOUNT_SUSPENSION`, you will subscribe to the suspension statuses of these ad accounts, which can be owned by different Business Centers. </li></ul><br>Max size: 100. <br><br><p><span style="color:darkred"><b>Note</b></span>: You need to have access to these ad accounts. To obtain the list of ad accounts that you have access to, use [/bc/asset/get/](https://business-api.tiktok.com/portal/docs?id=1739432717798401). Set `asset_type` to `ADVERTISER` and select ad accounts with the returned `advertiser_role` as `ADMIN`,`OPERATOR`, or `ANALYST`. |
#| notify_frequency | string | Valid when `subscribe_entity` is `REPORT_DATA_CHANGE`.<br><br>The frequency of the notification. <br>If the reporting metric data for the ad accounts that you're subscribed to has changed within the last notification timeframe, you will receive at most one webhook notification. <br><br>Enum values:  <ul><li>`5_MINUTE`: every five minutes.</li><li>`15_MINUTE`: every 15 minutes.</li><li>`30_MINUTE`: every 30 minutes.</li><li>`60_MINUTE`: every 60 minutes.</li></ul>Default value: `5_MINUTE`. |
#|advertiser_id {+Conditional}|string|Valid when `subscribe_entity` is `AD_GROUP`, `AD`, `LEAD` or `CREATIVE_FATIGUE`.<br> Advertiser ID. <ul><li>If you want to subscribe to the review status of an ad group or ad (`subscribe_entity` is `AD_GROUP`, or `AD`), `advertiser_id` is required.</li><li> If you want to subscribe to leads (`subscribe_entity` is `LEAD`): <ul><li>When `lead_source` is `INSTANT_FORM` or not specified, you need to pass in either `advertiser_id` (to subscribe to leads from Lead Generation ads using Instant Forms that are associated with an ad account) or `library_id` (to subscribe to leads from Lead Generation ads using Instant Forms that are associated with a form library), and `page_id` is optional.</li><li>When `lead_source` is `DIRECT_MESSAGE`, you need to pass in either `advertiser_id` (to subscribe to leads from direct messaging ads associated with an ad account) or `library_id` (to subscribe to leads from direct messaging ads associated with a form library), and `page_id` is not supported.</li></ul></li><li>If you want to subscribe to the fatigue status (`subscribe_entity` is `CREATIVE_FATIGUE`), `advertiser_id` is required and you can choose to pass in `adgroup_id`, or `ad_id`, or neither. When only `advertiser_id` is passed, you will subscribe to the fatigue status of all ads under the ad account.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: If `subscribe_entity` is `LEAD` and `advertiser_id` is specified, you need to have Admin access to the ad account. To obtain the list of ad accounts that you have access to, use [/bc/asset/get/](https://business-api.tiktok.com/portal/docs?id=1739432717798401). Set `asset_type` to `ADVERTISER` and select ad accounts with the returned `advertiser_role` as `ADMIN`. |
#|library_id {+Conditional}|string|Valid when `subscribe_entity` is `LEAD`. <br><br>ID of the form library that contains the leads you want to subscribe to. <br><br>You can get form library ID via `library_id` returned by [/page/library/get/](https://ads.tiktok.com/marketing_api/docs?id=1740029915327489).<br><br><ul><li>If you want to subscribe to Instant Form leads (`subscribe_entity` is `LEAD` and `lead_source` is `INSTANT_FORM` or not specified), you need to pass in either `advertiser_id` or `library_id`, and `page_id` is optional. When only `advertiser_id` is passed, you will subscribe to all `page_id` under the advertiser account (`advertiser_id`).</li><li>If you want to subscribe to direct message leads (`subscribe_entity` is `LEAD` and `lead_source` is `DIRECT_MESSAGE`), you need to pass in either `advertiser_id` or `library_id`, and `page_id` is not supported.</li></ul>|
#|page_id  |string|<ul><li>Valid when `subscribe_entity` is `LEAD` and `lead_source` is `INSTANT_FORM` or not specified.</li><li>Not supported when `subscribe_entity` is `LEAD` and `lead_source` is `DIRECT_MESSAGE`.</li></ul> <br>Page ID of the Instant Form that you want to subscribe to. <br><br>To obtain a list of Instant Forms, use [/page/get/](https://business-api.tiktok.com/portal/docs?id=1820826387779586) and set `business_type` to `LEAD_GEN`.<br><br>If you want to subscribe to Instant Form leads (`subscribe_entity` is `LEAD` and `lead_source` is `INSTANT_FORM` or not specified), you need to pass in either `advertiser_id` or `library_id`, and `page_id` is optional. When only `advertiser_id` is passed, you will subscribe to all `page_id` under the advertiser account (`advertiser_id`). |
#|adgroup_id {+Conditional} |string| Valid when `subscribe_entity` is `AD_GROUP` or `CREATIVE_FATIGUE`.<br>Ad group ID. <ul><li>Required if `subscribe_entity` is `AD_GROUP`.</li><li>If you specify `subscribe_entity` as `CREATIVE_FATIGUE` and pass in both `advertiser_id` and `adgroup_id`, you will subscribe to the fatigue status of the specified ad group.</li></ul>|
#|ad_id {+Conditional} |string|  Valid when `subscribe_entity` is `AD` or `CREATIVE_FATIGUE`.<br>Ad ID.<ul><li>Required if `subscribe_entity` is `AD`.</li><li>If you specify `subscribe_entity` as `CREATIVE_FATIGUE` and pass in both `advertiser_id` and `ad_id`, you will subscribe to the fatigue status of the specified ad.</li></ul> |
#|tto_tcm_account_id {+Conditional}|string| Required when `subscribe_entity` is `TCM_VIDEOS`.<br><br>The ID of a TTO Creator Marketplace account.<br><br>To obtain the IDs of the accounts that you can access via `Access-Token`, use [/tto/oauth2/tcm/](https://business-api.tiktok.com/portal/docs?id=1815693478150146).|
```

### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/subscription/subscribe/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data-raw '{
    "app_id": "{{app_id}}", 
    "secret": "{{secret}}",
    "subscription_detail": {
        "advertiser_id": "{{advertiser_id}}", 
        "access_token": "{{access_token}}", 
        "page_id": "{{page_id}}"
    }, 
    "subscribe_entity": "LEAD", 
    "callback_url": "{{callback_url}}"
}'
(/code)
```

## Response
```xtable
|Field|Type|Description|
|---|---|---|
|code |number|The response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message |string|The response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object|The returned data. |
#|subscription_id |string| The subscription ID |
|request_id |string| The unique ID of the request.  |
```

### Example
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
	"request_id": "{{request_id}}"
    "data": {
        "subscription_id": "{{subscription_id}}"
    },
}
(/code);
```



