---
title: "Subscribe to TikTok account Webhook events via Webhooks API"
doc_id: 1810521730276354
path: "Use Cases / Webhook subscription / Subscribe to TikTok account Webhook events via Webhooks API"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Subscribe to TikTok account Webhook events via Webhooks API

This article walks you through the steps to receive notifications for TikTok post publishing status and comment updates on TikTok accounts via Webhooks.

# Introduction
Webhooks are automated HTTP callbacks that are triggered when specific events occur within the TikTok platform, allowing real-time notifications to be sent to your specified callback URL. This mechanism eliminates the need for constant API polling to retrieve information about asynchronous events. Instead, TikTok proactively sends event data to your system as soon as it becomes available. These notifications are delivered via HTTPS POST in JSON format to the callback URL you've configured for your application. You can use this real-time information to update your system or trigger specific business processes, ensuring your application stays synchronized with TikTok events efficiently and promptly.

**You can make subscriptions through TikTok account Webhooks to track the lifecycle of posted content, and monitor and respond to user engagement through comments.**

# Prerequisites
You've gained access to TikTok API for Business. Ensure that you have completed the following steps: 
1. [Create a TikTok For Business account](https://business-api.tiktok.com/portal/docs?id=1738855099573250).
2. [Register as a developer](https://business-api.tiktok.com/portal/docs?id=1738855176671234).
3. [Create a developer app](https://business-api.tiktok.com/portal/docs?id=1738855242728450).
4. [Accounts API Authorization](https://business-api.tiktok.com/portal/docs?id=1738083939371009).

- If you want to subscribe to post publishing events, make sure that you have selected the **TikTok Accounts** > **Business Content** permission for your developer app.


- If you want to subscribe to the comment update event, make sure that you have selected the **TikTok Accounts** > **Business Comment** permission for your developer App.



# Steps

## Subscribe to post publishing events
To subscribe to post publishing events for your developer app, call [/business/webhook/update/](https://business-api.tiktok.com/portal/docs?id=1810521279479810). In your request, provide the App ID (`app_id`) and Secret (`secret`) of your developer app, enter the callback URL in the `callback_url` field and specify `VIDEO` in the `event_type` field. 

### Example

Request
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

Response
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
Once successfully configured, your specified callback URL will receive real-time notifications about the status changes of posts published through [/business/video/publish/](https://ads.tiktok.com/marketing_api/docs?id=1762228496095234) or [/business/photo/publish/](https://business-api.tiktok.com/portal/docs?id=1803630424390658). To learn about the schema and sample code of the Webhook response for the events within this event category, see [Post publishing events](https://business-api.tiktok.com/portal/docs?id=1810515090228226).


## Subscribe to the comment update event 
To subscribe to comment update event for your developer app, call [/business/webhook/update/](https://business-api.tiktok.com/portal/docs?id=1810521279479810). In your request, provide the App ID (`app_id`) and Secret (`secret`) of your developer app, enter the callback URL in the `callback_url` field and specify `COMMENT` in the `event_type` field. Optionally, you can specify the specific TikTok posts that you want to receive notifications for via `item_list`.

### Example
#### Subscribe to the comment update event for all posts
Request
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

Response
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

Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/business/webhook/update/' \
--header 'Content-Type: application/json' \
--data '{
    "app_id": "{{app_id}}",
    "secret": "{{secret}}",
    "event_type": "COMMENT",
    "callback_url": "{{callback_url}}",
    "item_list": ["{{item_id}}","{{item_id}}"]
}'
(/code)
```

Response
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
Once successfully configured, your specified callback URL will receive real-time notifications when a comment or reply is created or deleted, or the comment or reply visibility settings are modified on any public video under the owned TikTok account. To learn about the schema and sample code of the Webhook response for the event within this event category, see [Comment update event](https://business-api.tiktok.com/portal/docs?id=1810515104773122).

# Related docs
- [Webhooks](https://business-api.tiktok.com/portal/docs?id=1759977800177665)
- [Webhook event types](https://business-api.tiktok.com/portal/docs?id=1759978319341570)
- [Webhooks API](https://business-api.tiktok.com/portal/docs?id=1810521243195393)

