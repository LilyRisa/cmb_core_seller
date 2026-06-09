---
title: "Get Started"
doc_id: 1758051319816193
path: "Marketing API / Ad Measurement / Guides / Events API 1.0 / Events API: Offline / Get Started"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get Started

This guide walks you through the process of setting up Offline Events API.

# 1. Authorization and Authentication

There are two methods to obtain the access token: 
- Using OAuth 2.0 API flow
- Using the TikTok Events Manager (TTEM) UI to generate the token manually

## Using OAuth2.0 API
1. Enable "Offline Events Management" permission for the developer app under My Apps.

2. Please follow the steps in [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162)  to generate the access token using the API once the developer app has been authorized.

## Using TikTok Events Manager UI
1. The advertiser logs into TikTok Ads Manager and clicks Assets > Events > Offline to create an offline event set and share with partners the offline event set ID.

- The advertiser generates access token under Offline event set > Settings, and provides partner/developer with offline events posting access and permission.

# 2. Alternative to create an Offline Event set
You can create a new Offline Event set using [/v1.3/offline/create/](https://ads.tiktok.com/marketing_api/docs?id=1758427576470529).
## Request
**Address** https://business-api.tiktok.com/open_api/v1.3/offline/create/

**Method** POST

**Request Header**

```xtable
|Field|Data Type|Description|
|---|---|---|
|Content-Type {Required}|string|Request message type.<br>Allowed format: `"application/json"`.|
|Access-Token {Required}|string|Authorized access token. To obtain the access token, see the "Authorization and Authentication" section above.
```

**Parameters**
```xtable
|Field|Data Type|Description
|---|---|---|---|
|advertiser_id {Required}|string|Advertiser ID.|
|name{Required}|string|Event set name. Example: in_store_purchases.
|description|string|Event set description. Example: in_store_purchases_blackfriday.
|auto_tracking|boolean|Whether to enable Auto Tracking. If this field is set to true, new campaigns created afterwards under this advertiser account will use this event set for ad data connection and attribution. Enum values: `true`, `false`.
```
### Example
> <span style="color: DodgerBlue">**Note**</span><br>You need to replace the dummy data in the example with your own data.

```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/offline/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data-raw '{
  "advertiser_id": "123456", 
  "name": "EVENT_SET_NAME", 
  "description": "In_store_purchases_blackfriday_2021",
  "auto_tracking": true
}
'
(/code)
```

## Response
```xtable
|Field|Data Type|Description|
|---|---|---|---|
|event_set_id |string|The event set ID that you just created.
```

### Example
```xcodeblock
(code)
HTTPS/1.1 200 OK
{
    "event_set_id": "{{event_set_id}}"
}
(/code)
```
# 3. Upload Offline Events to your Offline Event set
After you create an Offline Event set, you need to upload Offline Events (actions an offline visitor takes to achieve a business goal) to your Offline Event set. Both single Offline Event upload and batch Offline Event upload are supported.

For details of supported Offline Events and parameters, see [Report Offline Events and Parameters](https://ads.tiktok.com/marketing_api/docs?id=1758053486938113).

Note that another alternative for uploading Offline Events to an Offline Event set is via **file** upload on Events Manager.

# 4. Manage Offline Event sets (Optional)
You can manage your Offline Event sets in the following ways:
- Update the name or the Auto Tracking setting of an Offline Event set via [/offline/update/](https://ads.tiktok.com/marketing_api/docs?id=1765596741157889).
- Delete an Offline Event set via [/offline/delete/](https://ads.tiktok.com/marketing_api/docs?id=1765596790860802).
- Get the Offline Event sets under an advertiser account via [/offline/get/](https://ads.tiktok.com/marketing_api/docs?id=1765596808589313).

# 5. Measure Offline Event sets in your regular ads or aco ads
Specify the Offline Event sets that you want to measure in your ads via `tracking_offline_event_set_ids` in [/ad/create/](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354) or [/ad//aco/create/](https://ads.tiktok.com/marketing_api/docs?id=1739473063234626). Note that you can measure at most 50 Offline Event sets, including a maximum of 10 auto-tracking ones, in an ad. 
