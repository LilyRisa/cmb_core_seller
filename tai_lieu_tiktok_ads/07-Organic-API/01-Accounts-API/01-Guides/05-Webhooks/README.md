---
title: "Webhooks"
doc_id: 1759977800177665
path: "Organic API / Accounts API / Guides / Webhooks"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Webhooks

Webhooks are automated HTTP callbacks that are triggered when specific events occur within the TikTok platform, allowing real-time notifications to be sent to your specified callback URL. This mechanism eliminates the need for constant API polling to retrieve information about asynchronous events. Instead, TikTok proactively sends event data to your system as soon as it becomes available. These notifications are delivered via HTTPS POST in JSON format to the callback URL you've configured for your application. You can use this real-time information to update your system or trigger specific business processes, ensuring your application stays synchronized with TikTok events efficiently and promptly.

# Webhook callback URL

In order to receive a webhook message, a Webhook callback URL needs to be registered for your developer app. To configure a Webhook callback URL for your developer app, use the Webhook API endpoint [/business/webhook/update/](https://business-api.tiktok.com/portal/docs?id=1810521279479810). 

The callback URL must do the following:
- Immediately respond with a 200 HTTP status code to acknowledge the receipt of the event notification.
- The callback URL endpoint must require HTTPS.

If a 200 HTTP status code is not returned, TikTok assumes that the delivery was unsuccessful. TikTok retries the delivery of event notification for up to 72 hours using exponential backoff. After 72 hours, the notification is discarded and not sent again.

TikTok makes a best effort for "at least once delivery" of webhooks. Webhook endpoints might receive the same event more than once. There should be a guard against duplicated event receipts by making your event processing idempotent.

# Webhook structure
## Request Headers
These are important headers to record for Webhook verification, issue reports and troubleshooting. This is not an exhaustive list of request headers.

``` xtable
|Field{20%}|Data Type{15%}|Description{65%}|
|---|---|---|
| tiktok-signature | string | TikTok Webhooks are sent with a signature that the destination server can use to verify that the event came from TikTok and not a third party or malicious system. Refer to the [Webhook Verification](https://ads.tiktok.com/marketing_api/docs?id=1759978341579777) section to learn more. |
| webhook-schema-version | string |The webhook request body schema version. The current version is v1.0. |
| x-tt-logid | string | Unique identifier for the webhook request. Ensure this is logged for troubleshooting. <br>Example: `202201120803530102451591650189C78` |
```


## Request Body

webhook-schema-version: 1.0

``` xtable
|Field{20%}|Data Type{15%}|Description{65%}|
|---|---|---|
| client_key | string | The application ID provisioned for your application on the TikTok API for Business developer portal. This can be found on the developer portal under <strong>[My Apps](https://ads.tiktok.com/marketing_api/apps/)</strong>. <br>Example: `bwo1m23456a7k89` |
| event | string | Event type name. <br>Example: `post.publish.complete` |
| create_time | integer | The time in which the event occurred. UTC epoch time is in seconds. <br>Example: `1615338610` |
| user_openid | string | Application specific unique ID of the TikTok account - obtained via the [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1738084387220481) endpoint as the `open_id` field and used as the `business_id` parameter in all TikTok Accounts API requests. <br>Example: `_000abPL1cJIdVefgDhGijkl2mFn3AB0abY4` |
| content | string | A serialized JSON string of event information. <br>Example: `&#123;\"share_id\":\"v_pub_url~v1.2345123456789123456\", \"reason\":\"video_format_check_failed\"\&#125;` |
```

`client_key`, `event`, `create_time`, `user_openid` and `content` are common fields available in each webhook request delivered. The contents of the `content` field are what vary between events - so specifications for each event below only include fields within `content` (you can assume all other fields are available consistently as described in the table above).


