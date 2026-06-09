---
title: "Create a Mentions Webhook configuration"
doc_id: 1825108008121346
path: "API Reference / Mentions / Create a Mentions Webhook configuration"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create a Mentions Webhook configuration

Use this endpoint to create the configuration for Mentions events related to your TikTok account at the developer app level.

To learn more about how to create the configuration for these Webhook events , see [Subscribe to Mentions Webhook events via Webhooks API](https://business-api.tiktok.com/portal/docs?id=1825113658431489).

If you no longer wish to receive notifications, you can delete the Webhook configuration by using [/business/webhook/delete/](https://business-api.tiktok.com/portal/docs?id=1825108021550082).



## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/business/webhook/update/

**Method** POST

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Content-Type {Required}|string|Request message type.<br>Allowed format: `application/json`.  |
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|app_id {Required}|string|ID of your developer application.<br><br>You can find the <strong>App ID</strong> by navigating to <strong>[My Apps](https://ads.tiktok.com/marketing_api/apps/)</strong> > <strong>App Detail</strong> > <strong>Basic Information</strong>. |
|secret {Required}|string|Secret of your developer application.<br><br>You can find the <strong>Secret</strong> by navigating to <strong>[My Apps](https://ads.tiktok.com/marketing_api/apps/)</strong> > <strong>App Detail</strong> > <strong>Basic Information</strong>. |
|event_type {Required}|string|The type of Webhook event that you want to subscribe to.<br><br>Enum values:<ul><li>`BRAND_MENTION`: Mentions events. To learn more about the schema and sample payload of the events within this event category, see [Webhook schema](https://business-api.tiktok.com/portal/docs?id=1825113658431489#item-link-Webhook%20schema).</li></ul>|
|callback_url {Required}|string|The callback URL.|
```

### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/business/webhook/update/' \
--header 'Content-Type: application/json' \
--data '{
    "app_id": "{{app_id}}",
    "secret": "{{secret}}",
    "event_type": "BRAND_MENTION",
    "callback_url": "{{callback_url}}"
}'
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
#|app_id|string|ID of your developer application.|
#|event_type|string|The type of Webhook event configuration.<br><br>Enum values:<ul><li>`BRAND_MENTION`: Mentions events. To learn more about the schema and sample payload of the events within this event category, see [Webhook schema](https://business-api.tiktok.com/portal/docs?id=1825113658431489#item-link-Webhook%20schema).</li></ul>|
#|callback_url|string|The callback URL.|
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
        "app_id": "{{app_id}}",
        "callback_url": "{{callback_url}}",
        "event_type": "BRAND_MENTION"
    }
}
(/code)
```

