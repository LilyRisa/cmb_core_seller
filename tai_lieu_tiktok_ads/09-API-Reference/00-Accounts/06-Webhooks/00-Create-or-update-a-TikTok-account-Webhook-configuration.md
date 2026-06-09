---
title: "Create or update a TikTok account Webhook configuration"
doc_id: 1810521279479810
path: "API Reference / Accounts / Webhooks / Create or update a TikTok account Webhook configuration"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create or update a TikTok account Webhook configuration

Use this endpoint to create or update the configurations for Webhook events related to your TikTok account activities at the developer app level.

To learn more about how to create the configurations for these Webhook events, see [Subscribe to TikTok account Webhook events via Webhooks API](https://business-api.tiktok.com/portal/docs?id=1810521730276354).

If you no longer wish to receive notifications, you can delete the Webhook configuration by using [/business/webhook/delete/](https://business-api.tiktok.com/portal/docs?id=1810521297052673).



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
|--- |--- |--- |
| app_id {Required} | string | ID of your developer application. <br><br>You can find the <strong>App ID</strong> by navigating to <strong>[My Apps](https://ads.tiktok.com/marketing_api/apps/)</strong> > <strong>App Detail</strong> > <strong>Basic Information</strong>. |
| secret {Required} | string | Secret of your developer application. <br><br>You can find the <strong>Secret</strong> by navigating to <strong>[My Apps](https://ads.tiktok.com/marketing_api/apps/)</strong> > <strong>App Detail</strong> > <strong>Basic Information</strong>. |
| event_type {Required} | enum | The type of Webhook event that you want to subscribe to. <br><br> Enum values: <ul><li>`VIDEO`: Post publishing events, which provide you with real-time updates on the status of content published via specific TikTok API endpoints. <ul><li>To learn more about the schema and sample payload of the events within this event category, see [Post publishing events](https://business-api.tiktok.com/portal/docs?id=1810515090228226). </li></ul></li><li>`COMMENT`: comment update event, including the event `comment.update`.<ul><li>To learn more about the schema and sample payload of the event within this event category, see [Comment update event](https://business-api.tiktok.com/portal/docs?id=1810515104773122). </li></ul></li></ul>|
| callback_url {Required} | string | The callback URL. |
| item_list | string[] | <ul><li>Valid only when `event_type` is `COMMENT`. </li><li>Ignored when `event_type` is `DEFAULT` or `VIDEO`. </li></ul><br>The IDs of the specific TikTok posts that you want to receive comment update event notifications for. <br><br>Max size: 100. <br><br> To obtain the IDs of TikTok posts under a TikTok account, use [/business/video/list/](https://business-api.tiktok.com/portal/docs?id=1762228421622786). <br><br><ul><li>When `event_type` is set to `COMMENT` and `item_list` is included in the request: <ul><li> If `item_list` has never been configured for the developer app (`app_id`) previously, you will only receive notifications of `comment.update` events for the specified posts in `item_list`.</li><li>If `item_list` has been configured for the developer app (`app_id`) before, you will receive notifications for `comment.update` events of all previously configured posts in addition to the newly specified posts in `item_list`.</li></ul></li><li>When `event_type` is set to `COMMENT` and `item_list` is not included in the request: <ul><li>If `item_list` has never been configured for the developer app (`app_id`) previously, you will receive notifications of `comment.update` events for all posts under the TikTok account or TikTok accounts that authorized the developer app (`app_id`).</li><li>If `item_list` has been configured for the developer app (`app_id`) before, you will receive notifications of `comment.update` events for all previously configured posts. To review the specific posts configured for your developer app, check the `item_list` value in the response. </li></ul></li></ul> |
```

### Example

#### Subscribe to post publishing events
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/business/webhook/update/' \
--header 'Content-Type: application/json' \
--data '{
    "app_id": "{{app_id}}",
    "secret": "{{secret}}",
    "event_type": "VIDEO",
    "callback_url": "{{callback_url}}"
}'
(/code)
```

#### Subscribe to the comment update event for all posts
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/business/webhook/update/' \
--header 'Content-Type: application/json' \
--data '{
    "app_id": "{{app_id}}",
    "secret": "{{secret}}",
    "event_type": "COMMENT",
    "callback_url": "{{callback_url}}"
}'
(/code)
```

#### Subscribe to the comment update event for specific posts
```xcodeblock
(code curl http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/business/webhook/update/' \
--header 'Content-Type: application/json' \
--data '{
    "app_id": "{{app_id}}",
    "secret": "{{secret}}",
    "event_type": "COMMENT",
    "callback_url": "{{callback_url}}"
    "item_list": ["{{item_id}}", "{{item_id}}"]
}'
(/code)
```

#### Update the callback URL for post publishing events
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/business/webhook/update/' \
--header 'Content-Type: application/json' \
--data '{
    "app_id": "{{app_id}}",
    "secret": "{{secret}}",
    "event_type": "VIDEO",
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
#| app_id | string | ID of your developer application. |
#| event_type| string | The type of Webhook event that you subscribe to. <br><br> Enum values: <ul><li>`VIDEO`: Post publishing events, which provide you with real-time updates on the status of content published via specific TikTok API endpoints. <ul><li>To learn more about the schema and sample payload of the events within this event category, see [Post publishing events](https://business-api.tiktok.com/portal/docs?id=1810515090228226). </li></ul></li><li>`COMMENT`: comment update event, including the event `comment.update`.<ul><li>To learn more about the schema and sample payload of the event within this event category, see [Comment update event](https://business-api.tiktok.com/portal/docs?id=1810515104773122). </li></ul></li></ul>|
#| callback_url | string | The callback URL. |
#| item_list | string[] | Returned only when `event_type` is `COMMENT` and `item_list` is configured for the developer app (`app_id`). <br><br> The IDs of the specific TikTok posts that you will receive comment update event notifications for. |
```

### Example

#### Subscribe to post publishing events
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
#### Subscribe to the comment update event for all posts
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
        "event_type": "COMMENT"
    }
}
(/code)
```
#### Subscribe to the comment update event for specific posts
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
        "event_type": "COMMENT",
        "item_list": [
            "{{item_id}}",
            "{{item_id}}"
        ]
    }
}
(/code)
```
#### Update the callback URL for post publishing events
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
