---
title: "Subscribe to mentions via Webhooks API"
doc_id: 1825113658431489
path: "Use Cases / Webhook subscription / Subscribe to mentions via Webhooks API"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Subscribe to mentions via Webhooks API

This guide walks you through the steps to receive notifications for mentions of your business in post captions or comments via Webhooks.

# Introduction
Webhooks are automated HTTP callbacks that are triggered when specific events occur within the TikTok platform, allowing real-time notifications to be sent to your specified callback URL. This mechanism eliminates the need for constant API polling to retrieve information about asynchronous events. Instead, TikTok proactively sends event data to your system as soon as it becomes available. These notifications are delivered via HTTPS POST in JSON format to the callback URL you've configured for your application. You can use this real-time information to update your system or trigger specific business processes, ensuring your application stays synchronized with TikTok events efficiently and promptly.

**You can make subscriptions through Mentions Webhooks to stay in sync with mentions of your business, enabling timely responses to comments and maintaining active conversations with your community.**

> <span style="color:DodgerBlue">**Note**</span><br>
> The latency of Mentions Webhooks can be up to two or three hours.

# Prerequisites
You've gained access to TikTok API for Business. Ensure that you have completed the following steps: 
1. [Create a TikTok For Business account](https://business-api.tiktok.com/portal/docs?id=1738855099573250).
2. [Register as a developer](https://business-api.tiktok.com/portal/docs?id=1738855176671234).
3. [Create a developer app](https://business-api.tiktok.com/portal/docs?id=1738855242728450).
4. [Mentions API Authorization](https://business-api.tiktok.com/portal/docs?id=1825103730490434).

- Ensure that you have selected the **Mentions** permission for your developer app.


# Steps

To subscribe to the Mentions events for your developer app, call [/business/webhook/update](https://business-api.tiktok.com/portal/docs?id=1825108008121346). In your request, provide the App ID (`app_id`) and Secret (`secret`) of your developer app, enter the callback URL in the `callback_url` field and specify `BRAND_MENTION` in the `event_type` field. 

**Example**

Request
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
        "event_type": "BRAND_MENTION"
    }
}
(/code)
```

Once successfully configured, your specified callback URL will receive notifications within up to two or three hours after a user mentions your business in post captions or comments. To learn about the schema and sample code of the Webhook response for the events within this event category, see <a href="#item-link-Webhook schema">Webhook schema</a>.

To learn about how to conduct Webhook verification, see [Webhook verification](https://business-api.tiktok.com/portal/docs?id=1759978341579777).

## Webhook schema

Once you have successfully subscribed to Mentions Webhooks, we will notify you using the following schema.

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
|client_key|string|The application ID provisioned for your application on the TikTok API for Business developer portal. This can be found on the developer portal under [My Apps](https://ads.tiktok.com/marketing_api/apps/).<br><br>Example: `bwo1m23456a7k89`.|
|content|string|A serialized JSON string of event information.<br><br>To learn about the parameters within `content`, see <a href="#item-link-Event content">Event `content`</a>.|
|create_time|integer|The time when the event occurred, in the format of an Epoch/Unix timestamp (UTC time) in seconds.<br><br>Example: 1615338610.|
|event|string|Event type name.<br><br>Enum value:<ul><li>`brand.mention.event`: A brand mention event.</li></ul>|
|user_openid|string|Application specific unique ID of the mentioned TikTok account.<br><br>You can obtain the ID via the [/tt_user/oauth2/token/](ttps://business-api.tiktok.com/portal/docs?id=1825105003772930#item-link-Obtain%20an%20access%20token) endpoint as the `open_id` field and use it as the `business_id` parameter in all Mentions API requests.<br><br>Example: `1234a567-8912-3456-b7cd-8efg9h123ad4`.|
```

**Example payload**
```xcodeblock
(code curl http)
{
    "client_key": "{{client_key}}",
    "content": "{\"type_of_event\": \"CREATE\",\"mention_type\": \"VIDEO MENTION\",\"video_id\": \"{{video_id}}\",\"video_create_time\":1732150245,\"open_id_of_mentioned_user\": \"{{open_id_of_mentioned_user}}\",\"unique_name_of_mentioned_user\": \"{{unique_name_of_mentioned_user}}\",\"unique_identifier\": \"{{unique_identifier}}\",\"share_url\": \"{{share_url}}\",\"video_caption\": \"{{video_caption}}\",\"comment_id\": "0"}",
    // Event creation time
    "create_time": 1732150252,
    "event": "brand.mention.event",
    // Video Author OpenID
    "user_openid": "{{user_openid}}"
}
(/code)
```

### Event `content`
``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
|type_of_event|string|The type of event.<br><br>Enum values:<ul><li>`CREATE`: Create a mention.</li><li>`EDIT`: Edit a mention.</li><li>`PRIVACY_CHANGE`: Change the privacy setting of a TikTok post.</li></ul>|
|mention_type|string|Mention type.<br><br>Enum values:<ul><li>`COMMENT`: A comment or reply mentions (@) the business's handle in the comment text.</li><li>`VIDEO_MENTION`: A post mentions (@) the business's handle in the caption.</li></ul>|
|video_id|string|Unique identifier for the TikTok post.|
|video_create_time|integer|The time when the video was created, in the format of an Epoch/Unix timestamp (UTC time) in seconds.<br><br>Example: 1615338610.|
|open_id_of_mentioned_user|string|Application specific unique ID of the mentioned TikTok account.<br><br>You can obtain the ID via the [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1825105003772930#item-link-Obtain%20an%20access%20token) endpoint as the `open_id` field and use it as the `business_id` parameter in all Mentions API requests.<br><br>Example: `1234a567-8912-3456-b7cd-8efg9h123ad4`.|
|unique_name_of_mentioned_user|string|The username of the mentioned TikTok account.|
|unique_identifier|string|A globally unique identifier assigned to each user commenting. This identifier remains consistent across different APIs, facilitating cross-referencing and integration.<br><br>Example: `abcdefg+GUH12wq34==`.|
|share_url|string|A shareable URL for the TikTok post.|
|video_caption|string|The caption of the TikTok post.|
|comment_id|string|Unique identifier for the comment.<br><br>When `mention_type` is `VIDEO_MENTION`, the mention doesn't involve a comment and this field will be `0`.|
```

```xcodeblock
(code curl http)
"content": {
    "type_of_event": "CREATE",
    "mention_type": "VIDEO MENTION",
    "video_id": "{{video_id}}",
    "video_create_time": 1615338610,
    "open_id_of_mentioned_user": "{{open_id_of_mentioned_user}}",
    "unique_name_of_mentioned_user": "{{unique_name_of_mentioned_user}}",
    "unique_identifier": "{{unique_identifier}}",
    "share_url": "{{share_url}}",
    "video_caption": "{{video_caption}}",
    "comment_id": "0"
  }
(/code)
```



