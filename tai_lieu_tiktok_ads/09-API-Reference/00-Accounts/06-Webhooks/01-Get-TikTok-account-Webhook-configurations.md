---
title: "Get TikTok account Webhook configurations"
doc_id: 1810521289122818
path: "API Reference / Accounts / Webhooks / Get TikTok account Webhook configurations"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get TikTok account Webhook configurations

Use this endpoint to retrieve the existing configurations for Webhook events related to your TikTok account activities at the developer app level.


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/business/webhook/list/

**Method** GET


**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| app_id {Required} | string | ID of your developer application. <br><br>You can find the <strong>App ID</strong> by navigating to <strong>[My Apps](https://ads.tiktok.com/marketing_api/apps/)</strong> > <strong>App Detail</strong> > <strong>Basic Information</strong>. |
| secret {Required} | string | Secret of your developer application. <br><br>You can find the <strong>Secret</strong> by navigating to <strong>[My Apps](https://ads.tiktok.com/marketing_api/apps/)</strong> > <strong>App Detail</strong> > <strong>Basic Information</strong>. |
| event_type {Required} | enum | The type of Webhook event that you subscribe to. <br><br> Enum values: <ul><li>`VIDEO`: Post publishing events, which provide you with real-time updates on the status of content published via specific TikTok API endpoints. <ul><li>To learn more about the schema and sample payload of the events within this event category, see [Post publishing events](https://business-api.tiktok.com/portal/docs?id=1810515090228226). </li></ul></li><li>`COMMENT`: comment update event, including the event `comment.update`.<ul><li>To learn more about the schema and sample payload of the event within this event category, see [Comment update event](https://business-api.tiktok.com/portal/docs?id=1810515104773122). </li></ul></li></ul>|
```
### Example
#### Retrieve an existing Webhook configuration
```xcodeblock
(code curl http)
curl --location  --request GET 'https://business-api.tiktok.com/open_api/v1.3/business/webhook/list/?app_id={{app_id}}&secret={{secret}}&event_type=VIDEO'
(/code)
```

#### Retrieve an empty Webhook configuration
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/business/webhook/list/?app_id={{app_id}}&secret={{secret}}&event_type=VIDEO' 
(/code)
```

## Response

``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data.|
#| app_id | string | ID of your developer application. |
#| event_type| string | The type of Webhook event that you subscribe to. <br><br> Enum values: <ul><li>`VIDEO`: Post publishing events, which provide you with real-time updates on the status of content published via specific TikTok API endpoints. <ul><li>To learn more about the schema and sample payload of the events within this event category, see [Post publishing events](https://business-api.tiktok.com/portal/docs?id=1810515090228226). </li></ul></li><li>`COMMENT`: comment update event, including the event `comment.update`.<ul><li>To learn more about the schema and sample payload of the event within this event category, see [Comment update event](https://business-api.tiktok.com/portal/docs?id=1810515104773122). </li></ul></li></ul>|
#| callback_url | string | Returned only when the corresponding event configuration exists for your developer app.<br><br>The callback URL.<br><br> For example, if `event_type` is `VIDEO` and `callback_url` is not returned in the response, your developer app doesn't have an existing post publishing event Webhook configuration.|
#| item_list | string[] | Returned only when `event_type` is `COMMENT` and `item_list` is configured for the developer app (`app_id`). <br><br> The IDs of the specific TikTok posts that you will receive comment update event notifications for. |
```

### Example
#### Retrieve an existing Webhook configuration
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "app_id": "{{app_id}}",
        "callback_url": "{{callback_url}}",
        "event_type": "VIDEO"
    }
}
(/code)
```

#### Retrieve an empty Webhook configuration
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "app_id": "{{app_id}}",
        "event_type": "VIDEO"
    }
}
(/code)
```
