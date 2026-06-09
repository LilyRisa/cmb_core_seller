---
title: "Cancel a subscription"
doc_id: 1739094758789122
path: "API Reference / Subscription / Cancel a subscription"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Cancel a subscription

Use this endpoint to cancel a subscription.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/subscription/unsubscribe/|/v1.3/subscription/unsubscribe/|
|Request parameter data type|`app_id`: number<br>`subscription_id`:number|`app_id`: string<br>`subscription_id`:string|
|Response parameter data type|`subscription_id`:number|`subscription_id`:string|
```


## Request
**Endpoint**
https://business-api.tiktok.com/open_api/v1.3/subscription/unsubscribe/

**Method** POST

**Header**

```xtable
|Field|Type|Description|
|---|---|---|
|Content-Type {Required}|string| The content type of the request. For this endpoint, use `application/json`.|
```


**Parameters**
```xtable
| Field | Type | Description |
|---|---|---|
|app_id{Required}|string| ID of your developer application. You can find the <strong>App ID</strong> by navigating to <strong>[My Apps](https://ads.tiktok.com/marketing_api/apps/)</strong> > <strong>App Detail</strong> > <strong>Basic Information</strong>.|
|secret {Required}|string| Secret of your developer application. You can find the <strong>Secret</strong> by navigating to <strong>[My Apps](https://ads.tiktok.com/marketing_api/apps/)</strong> > <strong>App Detail</strong> > <strong>Basic Information</strong>. |
|subscription_id {Required}|string| The subscription ID.<br>You can get the IDs of subscriptions under an app via `subscription_id` returned by [/subscription/get/](https://ads.tiktok.com/marketing_api/docs?id=1739093832125442). |
```

### Example
```xcodeblock
(code curl http)
curl -H "Access-Token:xxx" -H "Content-Type:application/json" -X POST \
-d '{
    "secret": "SECRET", 
    "app_id": "APP_ID", 
    "subscription_id": "SUBSCRIPTION_ID"
}' \
https://business-api.tiktok.com/open_api/v1.3/subscription/unsubscribe/
(/code)
```

## Response
```xtable
|Field|Type|Description|
|---|---|---|
|code |number|The response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message |string|The response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object|The returned data. |
#|subscription_id|string| The subscription ID. |
|request_id |string|The unique ID of the request.
```

### Example
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {
        "subscription_id": 6934682745658408966
    },
    "request_id": "2021030113504601023101600705098212"
}
(/code);
```
