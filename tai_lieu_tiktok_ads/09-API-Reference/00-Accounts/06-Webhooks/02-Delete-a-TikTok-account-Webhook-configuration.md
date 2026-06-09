---
title: "Delete a TikTok account Webhook configuration"
doc_id: 1810521297052673
path: "API Reference / Accounts / Webhooks / Delete a TikTok account Webhook configuration"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Delete a TikTok account Webhook configuration

Use this endpoint to delete an existing configuration for Webhook events related to your TikTok account activities at the developer app level.


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/business/webhook/delete/

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
| event_type {Required} | enum | The type of Webhook event configuration that you want to delete.<br><br> Enum values: <ul><li>`VIDEO`: Post publishing events, which provide you with real-time updates on the status of content published via specific TikTok API endpoints. <ul><li>To learn more about the schema and sample payload of the events within this event category, see [Post publishing events](https://business-api.tiktok.com/portal/docs?id=1810515090228226). </li></ul></li><li>`COMMENT`: comment update event, including the event `comment.update`.<ul><li>To learn more about the schema and sample payload of the event within this event category, see [Comment update event](https://business-api.tiktok.com/portal/docs?id=1810515104773122). </li></ul></li></ul>|
| item_list | string[] | Valid only when `event_type` is `COMMENT`. <br><br>The IDs of the specific TikTok posts that you no longer want to receive comment update event notifications for. <br><br>Max size: 100. <br><br>To obtain the IDs of TikTok posts specified for an existing comment update Webhook configuration, use [/business/webhook/list/](https://business-api.tiktok.com/portal/docs?id=1810521289122818). <ul><li>If `event_type` is set to `COMMENT` and this field is provided, you will no longer receive notification of `comment.update` events for <b>the specified posts</b> under the TikTok account or TikTok accounts that authorized the developer app (`app_id`).</li><li>If `event_type` is set to `COMMENT` and this field is not provided, you will no longer receive notification of `comment.update` events for <b>all posts</b> under the TikTok account or TikTok accounts that authorized the developer app (`app_id`). </li></ul>|
```

### Example

#### Delete an existing post publishing event Webhook configuration
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/business/webhook/delete/' \
--header 'Content-Type: application/json' \
--data '{
    "app_id": "{{app_id}}",
    "secret": "{{secret}}",
    "event_type": "VIDEO"
}'
(/code)
```

#### Delete specific TikTok posts from an existing comment update event Webhook configuration
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/business/webhook/delete/' \
--header 'Content-Type: application/json' \
--data '{
    "app_id": "{{app_id}}",
    "secret": "{{secret}}",
    "event_type": "COMMENT",
    "item_list": [
                "{{item_id_1}}",
                "{{item_id_2}}"]
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
#| event_type| string |The type of Webhook event configuration.<br><br> Enum values: <ul><li>`VIDEO`: Post publishing events, which provide you with real-time updates on the status of content published via specific TikTok API endpoints. <ul><li>To learn more about the schema and sample payload of the events within this event category, see [Post publishing events](https://business-api.tiktok.com/portal/docs?id=1810515090228226). </li></ul></li><li>`COMMENT`: comment update event, including the event `comment.update`.<ul><li>To learn more about the schema and sample payload of the event within this event category, see [Comment update event](https://business-api.tiktok.com/portal/docs?id=1810515104773122). </li></ul></li></ul>|
#| item_list | string[] | Returned only when `event_type` is `COMMENT` and `item_list` is configured for the developer app (`app_id`). <br><br>The IDs of the specific TikTok posts that you will still receive comment update event notifications for.<br><br>For example, if you have previously configured the `item_list` as `["&#123;&#123;item_id_1&#125;&#125;","&#123;&#123;item_id_2&#125;&#125;"]` for your developer app and you set `item_list` to `["&#123;&#123;item_id_1&#125;&#125;"]` in the request, the value of this field in the response will be `["&#123;&#123;item_id_2&#125;&#125;"]`.|
```

### Example
#### Delete an existing post publishing event Webhook configuration
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

#### Delete specific TikTok posts from an existing comment update event Webhook configuration
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "app_id": "{{app_id}}",
        "event_type": "COMMENT",
        "item_list": [
            "{{item_id_3}}",
            "{{item_id_4}}"
        ]
    }
}
(/code)
```
