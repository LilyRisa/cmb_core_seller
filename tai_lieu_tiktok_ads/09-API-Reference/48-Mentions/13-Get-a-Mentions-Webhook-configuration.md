---
title: "Get a Mentions Webhook configuration"
doc_id: 1825108014565442
path: "API Reference / Mentions / Get a Mentions Webhook configuration"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get a Mentions Webhook configuration

Use this endpoint to retrieve the existing configuration for Mentions Webhook events at the developer app level.


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/business/webhook/list/

**Method** GET


**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| app_id {Required} | string | ID of your developer application. <br><br>You can find the <strong>App ID</strong> by navigating to <strong>[My Apps](https://ads.tiktok.com/marketing_api/apps/)</strong> > <strong>App Detail</strong> > <strong>Basic Information</strong>. |
| secret {Required} | string | Secret of your developer application. <br><br>You can find the <strong>Secret</strong> by navigating to <strong>[My Apps](https://ads.tiktok.com/marketing_api/apps/)</strong> > <strong>App Detail</strong> > <strong>Basic Information</strong>. |
| event_type {Required} | string |The type of Webhook event that you want to subscribe to.<br><br>Enum values:<ul><li>`BRAND_MENTION`: Mentions events. To learn more about the schema and sample payload of the events within this event category, see [Webhook schema](https://business-api.tiktok.com/portal/docs?id=1825113658431489#item-link-Webhook%20schema).</li></ul>|
```
### Example
```xcodeblock
(code curl http)
curl --location  --request GET 'https://business-api.tiktok.com/open_api/v1.3/business/webhook/list/?app_id={{app_id}}&secret={{secret}}&event_type=DIRECT_MESSAGE'
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
#| event_type| string |The type of Webhook event configuration.<br><br>Enum values:<ul><li>`BRAND_MENTION`: Mentions events. To learn more about the schema and sample payload of the events within this event category, see [Webhook schema](https://business-api.tiktok.com/portal/docs?id=1825113658431489#item-link-Webhook%20schema).</li></ul>|
#| callback_url | string | Returned only when the corresponding event configuration exists for your developer app.<br><br>The callback URL.<br><br> For example, if `event_type` is `BRAND_MENTION` and `callback_url` is not returned in the response, your developer app doesn't have an existing Mentions Webhook configuration.|
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
        "event_type": "BRAND_MENTION"
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
        "event_type": "BRAND_MENTION"
    }
}
(/code)
```
