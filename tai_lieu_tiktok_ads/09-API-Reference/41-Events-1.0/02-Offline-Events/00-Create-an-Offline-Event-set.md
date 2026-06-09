---
title: "Create an Offline Event set"
doc_id: 1758427576470529
path: "API Reference / Events 1.0 / Offline Events / Create an Offline Event set"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create an Offline Event set

Use this endpoint to create a new Offline Event set.

> <span style="color:DarkRed">**Important**</span><br>
> By using Events API endpoints, you agree to the [TikTok Business Products (Data) Terms](https://ads.tiktok.com/i18n/official/policy/business-products-terms) and that you will not share sensitive data with TikTok.
## Request
**Endpoint** https://business-api.tiktok.com/open_api/v1.3/offline/create/

**Method** POST

**Header**

```xtable
|Field|Data Type|Description|
|---|---|---|
|Content-Type {Required}|string|Request message type.<br>Allowed format: `"application/json"`.|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**
```xtable
|Field|Data Type|Description
|---|---|---|---|
|advertiser_id {Required}|string|Advertiser ID.|
|name{Required}|string|Event set name. <br>Length limit: 40 characters.<br>Example: in_store_purchases.<br><p><span style="color:darkred"><b>Note</b></span>: You cannot specify a name that has been used by another event set. Otherwise, an error will occur.|
|description|string|Event set description.<br> Example: in_store_purchases_blackfriday.|
|auto_tracking|boolean|Whether to enable Auto Tracking. If this field is set to true, new campaigns created afterwards under this advertiser account will use this event set for ad tracking and attribution. Enum values: `true`, `false`.<br><p><span style="color:darkred"><b>Note</b></span>: The maximum number of auto-tracking Offline Event sets under an advertiser account is 10. If the limit is reached, you cannot create another auto-tracking Offline Event set.|
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
|event_set_id |string| The event set ID that you just created.|
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
