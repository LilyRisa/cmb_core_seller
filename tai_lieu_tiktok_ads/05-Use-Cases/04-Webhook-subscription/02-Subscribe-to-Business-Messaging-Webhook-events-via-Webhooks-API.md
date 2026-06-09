---
title: "Subscribe to Business Messaging Webhook events via Webhooks API"
doc_id: 1832190670631937
path: "Use Cases / Webhook subscription / Subscribe to Business Messaging Webhook events via Webhooks API"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Subscribe to Business Messaging Webhook events via Webhooks API

This guide walks you through the steps to receive notifications for Business Messaging events, such as sending and receiving direct messages or updates to automatic message settings, via Webhooks.

# Introduction
Webhooks are automated HTTP callbacks that are triggered when specific events occur within the TikTok platform, allowing real-time notifications to be sent to your specified callback URL. This mechanism eliminates the need for constant API polling to retrieve information about asynchronous events. Instead, TikTok proactively sends event data to your system as soon as it becomes available. These notifications are delivered via HTTPS POST in JSON format to the callback URL you've configured for your application. You can use this real-time information to update your system or trigger specific business processes, ensuring your application stays synchronized with TikTok events efficiently and promptly.

**You can make subscriptions through Business Message Webhooks to stay up-to-date with direct message activities and automatic message updates for your business. This ensures timely responses to incoming messages and helps maintain active, meaningful conversations with your community.**

# Prerequisites
You've gained access to TikTok API for Business. Ensure that you have completed the following steps: 
1. [Create a TikTok For Business account](https://business-api.tiktok.com/portal/docs?id=1738855099573250).
2. [Register as a developer](https://business-api.tiktok.com/portal/docs?id=1738855176671234).
3. [Create a developer app](https://business-api.tiktok.com/portal/docs?id=1738855242728450).
4. [Business Messaging API Authorization](https://business-api.tiktok.com/portal/docs?id=1832184159540418).
- Ensure that you have selected the **Business Messaging** permission for your developer app.
5. To ensure webhook notifications work, please remind businesses to **set TikTok Business Accounts to accept direct messages from everyone** in the TikTok app before authorizing a developer app to manage messages. Otherwise, it requires the Business Account owner to manually accept the message request from TikTok users in the TikTok app before receiving webhooks.

# Steps
You (Developers) need to subscribe to Business Messaging Webhooks at the developer app level, after receiving authorization from a business. After completing the Webhook configuration for your developer app, you'll receive Webhook notifications for all businesses that have authorized your developer app, without needing to resubscribe for each new business.

To subscribe to the Business Messaging Webhook events for your developer app, call [/business/webhook/update/](https://business-api.tiktok.com/portal/docs?id=1832184470423554). In your request, provide the App ID (`app_id`) and Secret (`secret`) of your developer app, enter the callback URL in the `callback_url` field and specify `DIRECT_MESSAGE` in the `event_type` field. 


**Example**

Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/business/webhook/update/' \
--header 'Content-Type: application/json' \
--data '{
    "app_id": "{{app_id}}",
    "secret": "{{secret}}",
    "event_type": "DIRECT_MESSAGE",
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
        "event_type": "DIRECT_MESSAGE"
    }
}
(/code)
```

Once successfully configured, your specified callback URL will receive notifications for Business Messaging Webhook events. To learn about the schema and sample code of the Webhook response for the events within this event category, see <a href="#item-link-Webhook schema">Webhook schema</a>.

To learn about how to conduct Webhook verification, see [Webhook verification](https://business-api.tiktok.com/portal/docs?id=1759978341579777).

## Webhook schema

Once you have successfully subscribed to Business Messaging Webhooks, we will notify you using the following schemas.

The following tables outline the schemas for different event types.

### `im_send_msg`
``` xtable
|Field{25%}|Data Type{15%}|Description{60%}|
|---|---|---|
| client_key | string | ID of your developer application.<br><br>To find the App ID, navigate to <b>[My Apps](https://ads.tiktok.com/marketing_api/apps/)</b> > <b>App Detail</b> > <b>Basic Information</b>.<br><br>Example: `bwo1m23456a7k89`. |
| event | string | Event name.<br><br>Enum value:<ul><li>`im_send_msg`: Receive real-time notifications if you send a message to a user.</li></ul> |
| create_time | integer | The time when the event notification is sent, in the format of an Epoch/Unix timestamp in seconds.<br><br>Example: 1714430514. |
| user_openid | string | Application specific unique ID of the TikTok account, obtained via the `open_id` field from the [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1832184175482945#item-link-Obtain%20a%20short-term%20access%20token) and used as the `business_id` parameter in Business Messaging API requests.<br><br>Example: `b1d2f34d-5b6c-78fb-91c2-cd345678912b`. |
| content | string | Content of the Webhook. See <a href="#item-link-content schema for im_send_msg">`content` schema for `im_send_msg`</a> for details.|
```

**Example**
```xcodeblock
(code curl http)
{
  "client_key": "{{client_key}}",
  "event": "im_send_msg",
  "create_time": 1701090882,
  "user_openid": "{{user_openid}}",
  "content": "{\"from\":\"vappe256\",\"to\":\"userxyz\",\"unique_identifier\":\"{{unique_identifier}}\",\"from_user\": {\"id\": \"{{id}}\",\"role\": \"business_account\"}, \"to_user\": {\"id\": \"{{id}}\",\"role\": \"personal_account\"},\"conversation_id\":\"{{conversation_id}}\",\"message_id\":\"{{message_id}}\", \"timestamp\":1701090879815, \"type\":\"image\",\"image\":{\"media_id\":\"{{media_id}}=\"},\"scene_type\":0,\"is_follower\":true,\"message_tag\":{\"source\":\"APP\"}}"
}
(/code)
```

#### `content` schema for `im_send_msg`

``` xtable
|Field{25%}|Data Type{15%}|Description{60%}|
|---|---|---|
| from | string | TikTok username of the message sender. |
| to | string | TikTok username of the message receiver. |
| unique_identifier | string | Unique ID of the TikTok user in the conversation.<br><br><p><span style="color:darkred"><b>Note</b></span>: This identifier remains consistent across different APIs, such as [the Accounts comment listing endpoint](https://business-api.tiktok.com/portal/docs?id=1760232109619202), facilitating cross-referencing and integration. |
| from_user | object | User information of the sender. |
#| role | string | The TikTok account type of the sender.<br><br>Enum values:<ul><li>`business_account`: Business Account.</li><li>`personal_account`: Personal Account.</li></ul> |
#| id | string | The identifier of the sender<ul><li>When `role` is `business_account`, this field is the same as the `business_id`, the application specific unique identifier for the TikTok Business Account.</li><li>When `role` is `personal_account`, this field is the same as the `unique_identifier`, the globally unique identifier assigned to the TikTok Personal Account user in the conversation. This identifier remains consistent across different APIs, such as [the Accounts comment listing endpoint](https://business-api.tiktok.com/portal/docs?id=1760232109619202), facilitating cross-referencing and integration. |
| to_user | object | User information of the receiver. |
#| role | string | The TikTok account type of the receiver.<br><br>Enum values:<ul><li>`business_account`: Business Account.</li><li>`personal_account`: Personal Account.</li></ul> |
#| id | string | The identifier of the receiver.<ul><li>When `role` is `business_account`, this field is the same as the `business_id`, the application specific unique identifier for the TikTok Business Account.</li><li>When `role` is `personal_account`, this field is the same as the `unique_identifier`, the globally unique identifier assigned to the TikTok Personal Account user in the conversation. This identifier remains consistent across different APIs, such as [the Accounts comment listing endpoint](https://business-api.tiktok.com/portal/docs?id=1760232109619202), facilitating cross-referencing and integration. |
| referenced_message_info | object | Information about the original message that is replied to. |
#| referenced_message_id | string | The ID of the original message that is replied to.<ul><li>When `type` is `text` and `referenced_message_id` is returned, the object `text` represents the text reply to the original message.</li><li>When `type` is not `text` and `referenced_message_id` is returned, you won't be able to retrieve the content of the reply itself through API.</li></ul> |
| conversation_id | string | Conversation ID. You need to use this value to send messages.|
| message_id | string | Message ID. |
| timestamp | integer | The time when you sent a message to a user, in the format of an Epoch/Unix timestamp in milliseconds.<br><br>Example: 1701090879815. |
| text | object | Returned only when `type` is `text`. <br><br>Text information. |
#| body | string | Text content.|
| image | object | Returned only when `type` is `image`. <br><br>Image information. |
#| media_id | string | Image ID.<br><br>To download the video associated with the video ID, use [/business/message/media/download/](https://business-api.tiktok.com/portal/docs?id=1832184455450626).  |
| share_post | object | Returned only when `type` is `share_post`.<br><br> Information about the post to be shared. |
#| embed_url | string | An embeddable link for the TikTok post.<br><br>This URL can be used to embed the TikTok post in external websites or applications.<br>If the privacy setting of the post is "Friends" or "Only you", the post will not be viewable through this link.|
#|video_id | string | The ID of the TikTok post. |
| reaction | object[] | Information about the list of reactions in response to the message. |
#| operation | string | The type of operation.<br><br>Enum values:<ul><li>`ADD`: The reaction was made to the message.</li><li>`REMOVE`: The reaction to the message was canceled.</li></ul> |
#| type | string | The type of reaction.<br><br>Enum values:<ul><li>`EMOJI`: A text emoji.</li><li>`AI_EMOJI`: An AI-generated emoji derived from a selfie-based avatar.</li></ul> |
#| emoji | string | Returned only when `type` is `EMOJI`.<br><br>The text of the text emoji. |
#| ai_emoji_url | string | Returned only when `type` is `AI_EMOJI`.<br><br>The URL to the AI-generated emoji image.<br><br>Validity period: 30 days.<br><br>The expiration time is included in the URL after the `x-expires` parameter, in the format of an Epoch/Unix timestamp in seconds. |
#| unique_identifier | string | The identifier of the sender.<br><br><ul><li>When `role` within the object `from_user` is `business_account`, this field is the same as the `business_id`, the application specific unique identifier for the TikTok Business Account.</li><li>When `role` within the object `from_user` is `personal_account`, this field is the same as the `unique_identifier`, the globally unique identifier assigned to the TikTok Personal Account user in the conversation. This identifier remains consistent across different APIs, such as [the Accounts comment listing endpoint](https://business-api.tiktok.com/portal/docs?id=1760232109619202), facilitating cross-referencing and integration.</li></ul> |
#| timestamp | integer | The timestamp when the reaction was made, in the format of an Epoch/Unix timestamp in milliseconds.<br><br>Example: 1719447205701. |
#|original_msg_id | string | The ID of the message that received this reaction. |
| sticker | object | Information about the sticker. |
#| url | string | The URL to the sticker.<br><br>Validity period: 30 days.<br><br>The expiration time is included in the URL after the `x-expires` parameter, in the format of an Epoch/Unix timestamp in seconds. |
| emoji | object | Information about the emoji. |
#| url | string | The URL to the emoji. <br><br>The URL will not expire. |
| video | object | Information about the video. |
#| media_id | string | The video ID.<br><br>To download the video associated with the video ID, use [/business/message/media/download/](https://business-api.tiktok.com/portal/docs?id=1832184455450626). |
|template|object|Returned only when `message_type` is `template`.<br><br>Details about the message template sent.|
#|type|string|The type of message template.<br><br>Enum values: <ul><li>`qa_button_card`: Q&A button card, a card showing a question and buttons that represent answers. When users click one of the buttons, the text content of the button will be sent to the conversation thread on behalf of the user as an answer to the pre-set question on the card.</li><li>`qa_link_card`: Q&A text link card, a card showing a question and interactive text links that represent answers to the question. When users click one of the text links, the text content of the text link will be sent to the conversation thread on behalf of the user as an answer to the pre-set question on the card.</li></ul>|
#|elements|object[]|Details about the cards in the message template.<br><br>Currently, only one card is supported within elements.|
##|title|string|The pre-set question on the card.|
##|buttons|object[]|A list of buttons or text links that represent answers to the question on the card.|
###|text|string|The text content of the button or text link.|
###|type|string|The type of the button or text link.<br><br>Enum value: <ul><li>`REPLY`: Reply button or reply text link. When users click the button or text link, the system will send the text content of the button or text link as a reply message on behalf of the user in the conversation.</li></ul>|
###|id|string|A self-defined ID for the button or text link.<br><br>You can use this ID to distinguish between different buttons and text links.|
| type | string | Message type.<br><br>Enum values:<ul><li>`text`: text.</li><li>`image`: image.</li><li>`share_post`: TikTok post.</li><li>`video`: video.</li><li>`emoji`: emoji.</li><li>`sticker`: sticker.</li><li>`reaction`: reaction.</li><li>`template`: message template.</li></ul> |
|auto_message_type |string|The type of automatic message.<br><br>Enum values:<ul><li>`WELCOME_MESSAGE`: welcome message. A welcome message is a message that is automatically sent to a user when they start a direct message conversation with you.</li><li>`SUGGESTED_QUESTION`: suggested question. A suggested question is a frequently asked question displayed at the beginning of a chat. When the user selects a suggested question, the user will automatically receive the corresponding preset answer.</li><li>`AUTO_REPLY`: keyword reply. A keyword reply is an automated response triggered by specific keywords in a message.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When the message is not an automatic message, this field will not be returned.</p>|
| scene_type | integer | Scene type. <br><br>By default, it is set to 0 (normal message). |
| is_follower | boolean | Whether the Personal Account follows the Business Account in the conversation.<br><br>Supported values: `true`, `false`.|
| message_tag | object | Additional information about the message. |
#| source | string | The source of the message, indicating the platform from which the message was sent.<br><br>Enum values:<ul><li>`APP`: TikTok mobile app.</li><li>`WEB`: TikTok Web or TikTok Business Center.</li><li>`API`: TikTok Business Messaging API.</li><li>`OTHERS`: Other TikTok internal servers or tools, including automatic bots.</li><li>`UNKNOWN_SOURCE`: Unknown source.</li></ul> |
```

**Example**
```xcodeblock
(code curl http)
{
    "from":"{{username}}", 
    "to":"{{username}}", 
    "unique_identifier": "{{unique_identifier}}",
    "from_user": {
        "id": "{{id}}",
        "role": "business_account"},
     "to_user": {
        "id": "{{id}}",
        "role": "personal_account"},
    "conversation_id":"{{conversation_id}}",
    "message_id":"{{message_id}}",
    "timestamp":1701090879815,
    "text": {
        "body": "{{body}}"
        },
    "type" : "text",
    "scene_type": 0,
    "is_follower" : true,
    "message_tag":{
        "source":"APP"
        }
}
(/code)
```

### `im_receive_msg`
``` xtable
|Field{25%}|Data Type{15%}|Description{60%}|
|---|---|---|
| client_key | string | ID of your developer application.<br><br>To find the App ID, navigate to <b>[My Apps](https://ads.tiktok.com/marketing_api/apps/)</b> > <b>App Detail</b> > <b>Basic Information</b>.<br><br>Example: `bwo1m23456a7k89`. |
| event | string | Event name.<br><br>Enum value:<ul><li>`im_receive_msg`: Receive real-time notifications if a user outside the European Economic Area (EEA), Switzerland, or the UK sends a message to you.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: If the message originates from a user within the EEA, Switzerland, or the UK, you will receive an <a href="#item-link-im_receive_msg_eu">`im_receive_msg_eu`</a> webhook event. |
| create_time | integer | The time when the event notification is sent, in the format of an Epoch/Unix timestamp in seconds.<br><br>Example: 1714430514. |
| user_openid | string | Application specific unique ID of the TikTok account, obtained via the `open_id` field from the [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1832184175482945#item-link-Obtain%20a%20short-term%20access%20token) and used as the `business_id` parameter in Business Messaging API requests.<br><br>Example: `b1d2f34d-5b6c-78fb-91c2-cd345678912b`. |
| content | string | Content of the Webhook. See <a href="#item-link-content schema for im_receive_msg">`content` schema for `im_receive_msg`</a> for details.|
```

**Example**
```xcodeblock
(code curl http)
{
  "client_key": "{{client_key}}",
  "event": "im_receive_msg",
  "create_time": 1701090882,
  "user_openid": "{{user_openid}}",
  "content": "{\"from\":\"vappe256\",\"to\":\"userxyz\",\"unique_identifier\":\"{{unique_identifier}}\",\"from_user\": {\"id\": \"{{id}}\",\"role\": \"personal_account\"}, \"to_user\": {\"id\": \"{{id}}\",\"role\": \"business_account\"},\"conversation_id\":\"{{conversation_id}}\",\"message_id\":\"{{message_id}}\", \"timestamp\":1701090879815, \"type\":\"image\",\"image\":{\"media_id\":\"{{media_id}}=\"},\"scene_type\":0,\"is_follower\":true,\"message_tag\":{\"source\":\"APP\"}}"
}
(/code)
```

#### `content` schema for `im_receive_msg`
``` xtable
|Field{25%}|Data Type{15%}|Description{60%}|
|---|---|---|
| from | string | TikTok username of the message sender. |
| to | string | TikTok username of the message receiver. |
| unique_identifier | string | Unique ID of the TikTok user in the conversation.<br><br><p><span style="color:darkred"><b>Note</b></span>: This identifier remains consistent across different APIs, such as [the Accounts comment listing endpoint](https://business-api.tiktok.com/portal/docs?id=1760232109619202), facilitating cross-referencing and integration. |
| from_user | object | User information of the sender. |
#| role | string | The TikTok account type of the sender.<br><br>Enum values:<ul><li>`business_account`: Business Account.</li><li>`personal_account`: Personal Account.</li></ul> |
#| id | string | The identifier of the sender<ul><li>When `role` is `business_account`, this field is the same as the `business_id`, the application specific unique identifier for the TikTok Business Account.</li><li>When `role` is `personal_account`, this field is the same as the `unique_identifier`, the globally unique identifier assigned to the TikTok Personal Account user in the conversation. This identifier remains consistent across different APIs, such as [the Accounts comment listing endpoint](https://business-api.tiktok.com/portal/docs?id=1760232109619202), facilitating cross-referencing and integration. |
| to_user | object | User information of the receiver. |
#| role | string | The TikTok account type of the receiver.<br><br>Enum values:<ul><li>`business_account`: Business Account.</li><li>`personal_account`: Personal Account.</li></ul> |
#| id | string | The identifier of the receiver.<ul><li>When `role` is `business_account`, this field is the same as the `business_id`, the application specific unique identifier for the TikTok Business Account.</li><li>When `role` is `personal_account`, this field is the same as the `unique_identifier`, the globally unique identifier assigned to the TikTok Personal Account user in the conversation. This identifier remains consistent across different APIs, such as [the Accounts comment listing endpoint](https://business-api.tiktok.com/portal/docs?id=1760232109619202), facilitating cross-referencing and integration. |
| referenced_message_info | object | Information about the original message that is replied to. |
#| referenced_message_id | string | The ID of the original message that is replied to.<ul><li>When `type` is `text` and `referenced_message_id` is returned, the object `text` represents the text reply to the original message.</li><li>When `type` is not `text` and `referenced_message_id` is returned, you won't be able to retrieve the content of the reply itself through API.</li></ul>|
| conversation_id | string | Conversation ID. You need to use this value to send messages.|
| message_id | string | Message ID. |
| timestamp | integer | The time when you sent a message to a user, in the format of an Epoch/Unix timestamp in milliseconds.<br><br>Example: 1701090879815. |
| text | object | Returned only when `type` is `text`. <br><br>Text information. |
#| body | string | Text content.|
| image | object | Returned only when `type` is `image`. <br><br>Image information.  |
#| media_id | string | Multimedia ID. <br><br>To download the video associated with the video ID, use [/business/message/media/download/](https://business-api.tiktok.com/portal/docs?id=1832184455450626). |
| share_post | object | Returned only when `type` is `share_post`.<br><br> Information about the post to be shared. |
#| embed_url | string | An embeddable link for the TikTok post.<br><br>This URL can be used to embed the TikTok post in external websites or applications.<br>If the privacy setting of the post is "Friends" or "Only you", the post will not be viewable through this link.|
#|video_id | string | The ID of the TikTok post. |
| reaction | object[] | Information about the list of reactions in response to the message. |
#| operation | string | The type of operation.<br><br>Enum values:<ul><li>`ADD`: The reaction was made to the message.</li><li>`REMOVE`: The reaction to the message was canceled.</li></ul> |
#| type | string | The type of reaction.<br><br>Enum values:<ul><li>`EMOJI`: A text emoji.</li><li>`AI_EMOJI`: An AI-generated emoji derived from a selfie-based avatar.</li></ul> |
#| emoji | string | Returned only when `type` is `EMOJI`.<br><br>The text of the text emoji. |
#| ai_emoji_url | string | Returned only when `type` is `AI_EMOJI`.<br><br>The URL to the AI-generated emoji image.<br><br>Validity period: 30 days.<br><br>The expiration time is included in the URL after the `x-expires` parameter, in the format of an Epoch/Unix timestamp in seconds. |
#| unique_identifier | string | The identifier of the sender.<br><br><ul><li>When `role` within the object `from_user` is `business_account`, this field is the same as the `business_id`, the application specific unique identifier for the TikTok Business Account.</li><li>When `role` within the object `from_user` is `personal_account`, this field is the same as the `unique_identifier`, the globally unique identifier assigned to the TikTok Personal Account user in the conversation. This identifier remains consistent across different APIs, such as [the Accounts comment listing endpoint](https://business-api.tiktok.com/portal/docs?id=1760232109619202), facilitating cross-referencing and integration.</li></ul> |
#| timestamp | integer | The timestamp when the reaction was made, in the format of an Epoch/Unix timestamp in milliseconds.<br><br>Example: 1719447205701. |
#|original_msg_id | string | The ID of the message that received this reaction. |
| sticker | object | Information about the sticker. |
#| url | string | The URL to the sticker.<br><br>Validity period: 30 days.<br><br>The expiration time is included in the URL after the `x-expires` parameter, in the format of an Epoch/Unix timestamp in seconds. |
| emoji | object | Information about the emoji. |
#| url | string | The URL to the emoji. <br><br>The URL will not expire. |
| video | object | Information about the video. |
#| media_id | string | The video ID.<br><br>To download the video associated with the video ID, use [/business/message/media/download/](https://business-api.tiktok.com/portal/docs?id=1832184455450626). |
|template|object|Returned only when `message_type` is `template`.<br><br>Details about the message template sent.|
#|type|string|The type of message template.<br><br>Enum values: <ul><li>`qa_button_card`: Q&A button card, a card showing a question and buttons that represent answers. When users click one of the buttons, the text content of the button will be sent to the conversation thread on behalf of the user as an answer to the pre-set question on the card.</li><li>`qa_link_card`: Q&A text link card, a card showing a question and interactive text links that represent answers to the question. When users click one of the text links, the text content of the text link will be sent to the conversation thread on behalf of the user as an answer to the pre-set question on the card.</li></ul>|
#|elements|object[]|Details about the cards in the message template.<br><br>Currently, only one card is supported within elements.|
##|title|string|The pre-set question on the card.|
##|buttons|object[]|A list of buttons or text links that represent answers to the question on the card.|
###|text|string|The text content of the button or text link.|
###|type|string|The type of the button or text link.<br><br>Enum value: <ul><li>`REPLY`: Reply button or reply text link. When users click the button or text link, the system will send the text content of the button or text link as a reply message on behalf of the user in the conversation.</li></ul>|
###|id|string|A self-defined ID for the button or text link.<br><br>You can use this ID to distinguish between different buttons and text links.|
| type | string | Message type.<br><br>Enum values:<ul><li>`text`: text.</li><li>`image`: image.</li><li>`share_post`: TikTok post.</li><li>`video`: video.</li><li>`emoji`: emoji.</li><li>`sticker`: sticker.</li><li>`reaction`: reaction.</li><li>`template`: message template.</li></ul> |
|auto_message_type |string|The type of automatic message.<br><br>Enum values:<ul><li>`WELCOME_MESSAGE`: welcome message. A welcome message is a message that is automatically sent to a user when they start a direct message conversation with you.</li><li>`SUGGESTED_QUESTION`: suggested question. A suggested question is a frequently asked question displayed at the beginning of a chat. When the user selects a suggested question, the user will automatically receive the corresponding preset answer.</li><li>`AUTO_REPLY`: keyword reply. A keyword reply is an automated response triggered by specific keywords in a message.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When the message is not an automatic message, this field will not be returned.</p>|
| scene_type | integer | Scene type. <br><br>By default, it is set to 0 (normal message). |
| is_follower | boolean | Whether the Personal Account follows the Business Account in the conversation.<br><br>Supported values: `true`, `false`.|
|reply_source_payload|object|Returned only when `message_type` is `text` and the text is an auto-generated reply triggered by a button on a Q&A button card or a text link on a Q&A text link card.<br><br>Details about the message and the original button or text link.|
#|reply_source_msg_id|string|The ID of the message that is sent to the conversation thread on behalf of the user after the user clicks a button on the Q&A button card or a text link on the Q&A text link card.|
#|reply_source_unique_id|string|The ID defined by the TikTok Business Account owner for the button on the Q&A button card or the text link on the Q&A text link card.<br><br>You can use this ID to distinguish between different buttons and text links.|
| message_tag | object | Additional information about the message. |
#| source | string | The source of the message, indicating the platform from which the message was sent.<br><br>Enum values:<ul><li>`APP`: TikTok mobile app.</li><li>`WEB`: TikTok Web or TikTok Business Center.</li><li>`API`: TikTok Business Messaging API.</li><li>`OTHERS`: Other TikTok internal servers or tools, including automatic bots.</li><li>`UNKNOWN_SOURCE`: Unknown source.</li></ul> |
```

**Example**
```xcodeblock
(code curl http)
{
    "from":"{{username}}", 
    "to":"{{username}}", 
    "unique_identifier": "{{unique_identifier}}",
    "from_user": {
        "id": "{{id}}",
        "role": "business_account"},
     "to_user": {
        "id": "{{id}}",
        "role": "personal_account"},
    "conversation_id":"{{conversation_id}}",
    "message_id":"{{message_id}}",
    "timestamp":1701090879815,
    "text": {
        "body": "{{body}}"
        },
    "type" : "text",
    "scene_type": 0,
    "is_follower" : true,
    "message_tag":{
        "source":"APP"
        }
}
(/code)
```



### `im_receive_msg_eu`
```xtable
|Field{25%}|Data Type{15%}|Description{60%}|
|---|---|---|
| client_key | string | ID of your developer application.<br><br>To find the App ID, navigate to <b>[My Apps](https://ads.tiktok.com/marketing_api/apps/)</b> > <b>App Detail</b> > <b>Basic Information</b>.<br><br>Example: `bwo1m23456a7k89`. |
| event | string | Event name.<br><br>Enum value:<ul><li>`im_receive_msg_eu`: Receive real-time notifications if a user from the European Economic Area (EEA), Switzerland, or the UK sends a message to you.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: If the message originates from a user outside the EEA, Switzerland, or the UK, you will receive an <a href="#item-link-im_receive_msg">`im_receive_msg`</a> webhook event. |
| create_time | integer | The time when the event occurred, in the format of an Epoch/Unix timestamp in seconds.<br><br>Example: 1714430514. |
| user_openid | string | Application specific unique ID of the TikTok account, obtained via the `open_id` field from the [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1832184175482945#item-link-Obtain%20a%20short-term%20access%20token) and used as the `business_id` parameter in all TikTok Accounts API requests.<br><br>Example: `b1d2f34d-5b6c-78fb-91c2-cd345678912b`. |
| content | string | Content of the Webhook. See <a href="#item-link-content schema for im_receive_msg_eu">`content` schema for `im_receive_msg_eu`</a> for details. |
```


**Example**
```xcodeblock
(code curl http)
{
  "client_key": "{{client_key}}",
  "event": "im_receive_msg_eu",
  "create_time": 1701090882,
  "user_openid": "{{user_openid}}",
  "content": "{\"to\":\"userxyz\", \"to_user\": {\"id\": \"{{id}}\",\"role\": \"business_account\"},\"timestamp\":1701090879815}"
}
(/code)
```

#### `content` schema for `im_receive_msg_eu`
```xtable
|Field{25%}|Data Type{15%}|Description{60%}|
|---|---|---|
| to | string | TikTok username of the message receiver. |
| to_user | object | User information of the receiver. |
#| role | string | The TikTok account type of the receiver.<br><br>Enum value:<ul><li><code>business_account</code>: Business Account.</li></ul> |
#| id | string | The identifier of the receiver.<ul><li>When <code>role</code> is <code>business_account</code>, this field is the same as the <code>business_id</code>, the application specific unique identifier for the TikTok Business Account.</li></ul> |
| timestamp | integer | The time of the event, in the format of an Epoch/Unix timestamp in milliseconds.<br><br>Example: 1701090879815. |
```


**Example**
```xcodeblock
(code curl http)
{
    "to":"{{username}}", 
    "to_user": {
        "id": "{{id}}",
        "role": "business_account"
    },
    "timestamp":1701090879815
}
(/code)
```


### `im_referral_msg`
``` xtable
|Field{25%}|Data Type{15%}|Description{60%}|
|---|---|---|
| client_key | string | ID of your developer application.<br><br>To find the App ID, navigate to <b>[My Apps](https://ads.tiktok.com/marketing_api/apps/)</b> > <b>App Detail</b> > <b>Basic Information</b>.<br><br>Example: `bwo1m23456a7k89`. |
| event | string | Event name.<br><br>Enum value:<ul><li>`im_referral_msg`: Receive real-time notification when a user enters the conversation from a referral source (currently limited to Click-to-Message Ads).</li></ul> |
| create_time | integer | The time when the event notification is sent, in the format of an Epoch/Unix timestamp in seconds.<br><br>Example: 1714430514. |
| user_openid | string | Application specific unique ID of the TikTok account, obtained via the `open_id` field from the [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1832184175482945#item-link-Obtain%20a%20short-term%20access%20token) and used as the `business_id` parameter in Business Messaging API requests.<br><br>Example: `b1d2f34d-5b6c-78fb-91c2-cd345678912b`. |
| content | string | Content of the Webhook. See <a href="#item-link-content schema for im_referral_msg">`content` schema for `im_referral_msg`</a> for details.|
```

**Example**

**Ad referral**
```xcodeblock
(code curl http)
{
  "client_key": "{{client_key}}",
  "event": "im_referral_msg",
  "create_time": 1701090882,
  "user_openid": "{{user_openid}}",
  "content": "{\"from\":\"vappe256\",\"to\":\"userxyz\",\"unique_identifier\":\"{{unique_identifier}}\",\"from_user\": {\"id\": \"{{id}}\",\"role\": \"personal_account\"}, \"to_user\": {\"id\": \"{{id}}\",\"role\": \"business_account\"}, \"conversation_id\":\"{{conversation_id}}\",\"timestamp\":1701090879815,\"is_follower\":true,\"referral\":{\"source\":\"ad\",\"ad\":{\"advertiser_id\":\"{{advertiser_id}}\",\"ad_id\":\"{{ad_id}}\",\"timestamp\":1701090879815,\"ad_name\":\"{{ad_name}}\",\"embed_url\":\"{{embed_url}}\"}}}"
}
(/code)
```

**tiktok.me link referral**

```xcodeblock
(code curl http)
{
  "client_key": "{{client_key}}",
  "event": "im_referral_msg",
  "create_time": 1701090882,
  "user_openid": "{{user_openid}}",
  "content": "{\"from\":\"vappe256\",\"to\":\"userxyz\",\"unique_identifier\":\"{{unique_identifier}}\",\"from_user\": {\"id\": \"{{id}}\",\"role\": \"personal_account\"}, \"to_user\": {\"id\": \"{{id}}\",\"role\": \"business_account\"}, \"conversation_id\":\"{{conversation_id}}\",\"timestamp\":1701090879815,\"is_follower\":true,\"referral\":{\"source\":\"short_link\",\"short_link\":{\"ref\":\"{{ref}}\",\"prefilled_message\":\"{{message}}\",\"prefilled_message_audit_status\":\"{{PASS}}\"}}}"
}
(/code)
```


#### `content` schema for `im_referral_msg`
``` xtable
|Field{30%}|Data Type{10%}|Description{60%}|
|---|---|---|
| from | string | TikTok username of the message sender. |
| to | string | TikTok username of the message receiver. |
| unique_identifier | string | Unique ID of the TikTok user in the conversation.<br><br><p><span style="color:darkred"><b>Note</b></span>: This identifier remains consistent across different APIs, such as [the Accounts comment listing endpoint](https://business-api.tiktok.com/portal/docs?id=1760232109619202), facilitating cross-referencing and integration. |
| from_user | object | User information of the sender. |
#| role | string | The TikTok account type of the sender.<br><br>Enum values:<ul><li>`business_account`: Business Account.</li><li>`personal_account`: Personal Account.</li></ul> |
#| id | string | The identifier of the sender<ul><li>When `role` is `business_account`, this field is the same as the `business_id`, the application specific unique identifier for the TikTok Business Account.</li><li>When `role` is `personal_account`, this field is the same as the `unique_identifier`, the globally unique identifier assigned to the TikTok Personal Account user in the conversation. This identifier remains consistent across different APIs, such as [the Accounts comment listing endpoint](https://business-api.tiktok.com/portal/docs?id=1760232109619202), facilitating cross-referencing and integration. |
| to_user | object | User information of the receiver. |
#| role | string | The TikTok account type of the receiver.<br><br>Enum values:<ul><li>`business_account`: Business Account.</li><li>`personal_account`: Personal Account.</li></ul> |
#| id | string | The identifier of the receiver.<ul><li>When `role` is `business_account`, this field is the same as the `business_id`, the application specific unique identifier for the TikTok Business Account.</li><li>When `role` is `personal_account`, this field is the same as the `unique_identifier`, the globally unique identifier assigned to the TikTok Personal Account user in the conversation. This identifier remains consistent across different APIs, such as [the Accounts comment listing endpoint](https://business-api.tiktok.com/portal/docs?id=1760232109619202), facilitating cross-referencing and integration. |
| conversation_id | string | Conversation ID. You need to use this value to send messages. |
| timestamp | integer | The time when the user entered the conversation from a referral source, in the format of an Epoch/Unix timestamp in milliseconds.<br><br>Example: 1701090879815. |
| is_follower | boolean | Whether the Personal Account follows the Business Account in the conversation.<br><br>Supported  values: `true`, `false`. |
| referral | object | Referral information. |
#| source | string | Referral source.<br><br>Enum values:<ul><li>`ad`: ad referral. The webhook is triggered by a user entering the conversation from Click-to-Message Ads.</li><li>`short_link`: TikTok.me link referral. The webhook is triggered by a user entering the conversation from a TikTok.me link. A TikTok.me link is a shareable TikTok shortlink that allows users to initiate a one-on-one conversation with a business in the TikTok App.</li></ul> |
#| ad | object | Returned only when `source` is `ad`. <br><br>Ad information. |
##| advertiser_id | string | The ID of the advertiser account that is associated with the ad. |
##| ad_id | string | Ad ID of the referral ad.<br><br>The types of data you can get from this metric include:<ul><li>The ID of a [Manual Ad](https://business-api.tiktok.com/portal/docs?id=1735735524758530).<ul><li>To obtain the details of an ad, you can integrate with the [/ad/get/](https://business-api.tiktok.com/portal/docs?id=1735735588640770) endpoint. However, note that you need to go through the authorization and authentication workflow for an advertiser. For details, see [Authorization](https://business-api.tiktok.com/portal/docs?id=1738373141733378) and [Authentication](https://business-api.tiktok.com/portal/docs?id=1738373164380162).</li></ul></li><li>The ID of an ad within a [Smart+ Campaign](https://business-api.tiktok.com/portal/docs?id=1765314260115458).</li><li>The ID of a creative within an [Upgraded Smart+ Ad](https://business-api.tiktok.com/portal/docs?id=1843310530260097).</li></ul> |
##| timestamp | integer | The time of the referral (the TikTok user clicks the ad), in the format of an Epoch/Unix timestamp in milliseconds.<br><br>Example: 1701090879815. |
##| ad_name | string | The name of the related ad. |
##| embed_url | string | An embeddable link for the TikTok post used in the related ad.<br><br>This URL can be used to embed the TikTok post in external websites or applications.<br>If the privacy setting of the post is "Friends" or "Only you", the post will not be viewable through this link. |
##| message_material_id|string|Material ID.<br><br>The encoded ID of a creative within [Upgraded Smart+ Ads](https://business-api.tiktok.com/portal/docs?id=1843310530260097).|
#| short_link | object | Returned only when `source` is `short_link`.<br><br> Information about the TikTok.me link. |
##| ref | string | The referral parameter (`ref`) that is configured in the TikTok.me link, offering context about the conversation. You can incorporate the referral parameter in your TikTok.me link to monitor various links posted on different channels.<br><br>This parameter is a URL-encoded string with a maximum length of 2,083 characters.<br><br><p><span style="color:darkred"><b>Note</b></span>: The `ref` parameter only supports alphanumeric characters and the special characters `-`, `_`, and `=`. |
##| prefilled_message | string | The prefilled message that is configured in the TikTok.me link. |
##| prefilled_message_audit_status | string | The audit status of the prefilled message.<br><br>Enum values:<ul><li>`REJECT`: The message was rejected during the review. If `prefilled_message_audit_status` is `REJECT`, the `prefilled_message` will not be prefilled into the message input box.</li><li>`PASS`: The message passed the review.</li></ul> |
```

**Example**

**Ad referral**
```xcodeblock
(code curl http)
{
    "from":"{{username}}", 
    "to":"{{username}}", 
    "unique_identifier": "{{unique_identifier}}",
    "from_user": {
        "id": "{{id}}",
        "role": "personal_account"},
     "to_user": {
        "id": "{{id}}",
        "role": "business_account"},
    "conversation_id":"{{conversation_id}}",
    "timestamp": 1701090879815,
    "is_follower" : true,
    "referral": {
        "source": "ad", 
        "ad": {
            "advertiser_id": "{{advertiser_id}}", 
            "ad_id": "{{ad_id}}", 
            "timestamp": 1701090879815,
            "ad_name": "{{ad_name}}",
            "embed_url": "https://www.tiktok.com/player/v1/1234567891234567891?music_info=1&description=1&autoplay=1&loop=1&utm_campaign=tt4d_open_api&utm_source=dm"
        }
    }
}
(/code)
```

**tiktok.me link referral**
```xcodeblock
(code curl http)
{
    "from":"{{username}}", 
    "to":"{{username}}", 
    "unique_identifier": "{{unique_identifier}}",
    "from_user": {
        "id": "{{id}}",
        "role": "personal_account"},
     "to_user": {
        "id": "{{id}}",
        "role": "business_account"},
    "conversation_id":"{{conversation_id}}",
    "timestamp": 1701090879815,
    "is_follower" : true,
    "referral": {
        "source": "short_link", 
        "short_link": {
            "ref": "{{ref}}", 
            "prefilled_message": "{{message}}",
            "prefilled_message_audit_status": "PASS"
        }
    }
}
(/code)
```


### `im_mark_read_msg`
``` xtable
|Field{25%}|Data Type{15%}|Description{60%}|
|---|---|---|
| client_key | string | ID of your developer application.<br><br>To find the App ID, navigate to <b>[My Apps](https://ads.tiktok.com/marketing_api/apps/)</b> > <b>App Detail</b> > <b>Basic Information</b>.<br><br>Example: `bwo1m23456a7k89`. |
| event | string | Event name.<br><br>Enum value:<ul><li>`im_mark_read_msg`: Receive real-time notifications when a Personal Account user marks all messages in a session as read.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: If a Business Account user marks all messages in a session as read, no notifications will be sent. |
| create_time | integer | The time when the event notification is sent, in the format of an Epoch/Unix timestamp in seconds.<br><br>Example: 1714430514. |
| user_openid | string | Application specific unique ID of the TikTok account, obtained via the `open_id` field from the [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1832184175482945#item-link-Obtain%20a%20short-term%20access%20token) and used as the `business_id` parameter in Business Messaging API requests.<br><br>Example: `b1d2f34d-5b6c-78fb-91c2-cd345678912b`. |
| content | string | Content of the Webhook. See <a href="#item-link-content schema for im_mark_read_msg">`content` schema for `im_mark_read_msg`</a> for details.|
```


**Example**
```xcodeblock
(code curl http)
{
  "client_key": "{{client_key}}",
  "event": "im_mark_read_msg",
  "create_time": 1701090882,
  "user_openid": "{{user_openid}}",
  "content": "{\"from\":\"vappe256\",\"to\":\"userxyz\",\"unique_identifier\":\"{{unique_identifier}}\",\"from_user\": {\"id\": \"{{id}}\",\"role\": \"personal_account\"}, \"to_user\": {\"id\": \"{{id}}\",\"role\": \"business_account\"},\"conversation_id\":\"{{conversation_id}}\", \"timestamp\":1701090879815, \"read\":{\" last_read_timestamp\":\"{{last_read_timestamp}}\"}}
}
(/code)
```

#### `content` schema for `im_mark_read_msg`
``` xtable
|Field{25%}|Data Type{15%}|Description{60%}|
|---|---|---|
| from | string | TikTok username of the message sender. |
| to | string | TikTok username of the message receiver. |
| unique_identifier | string | Unique ID of the TikTok user in the conversation.<br><br><p><span style="color:darkred"><b>Note</b></span>: This identifier remains consistent across different APIs, such as [the Accounts comment listing endpoint](https://business-api.tiktok.com/portal/docs?id=1760232109619202), facilitating cross-referencing and integration. |
| from_user | object | User information of the sender. |
#| role | string | The TikTok account type of the sender.<br><br>Enum value:<ul><li>`personal_account`: Personal Account.</li></ul> |
#| id | string | The identifier of the sender.<br><br>When `role` is `personal_account`, this field is the same as the `unique_identifier`, the globally unique identifier assigned to the TikTok Personal Account user in the conversation. This identifier remains consistent across different APIs, such as [the Accounts comment listing endpoint](https://business-api.tiktok.com/portal/docs?id=1760232109619202), facilitating cross-referencing and integration. |
| to_user | object | User information of the receiver. |
#| role | string | The TikTok account type of the receiver.<br><br>Enum value:<ul><li>`business_account`: Business Account.</li></ul> |
#| id | string | The identifier of the receiver.<br><br>When `role` is `business_account`, this field is the same as the `business_id`, the application specific unique identifier for the TikTok Business Account. |
| conversation_id | string | Conversation ID. You need to use this value to send messages. |
| timestamp | integer | The time when a Personal Account user marked all messages in a session as read, in the format of an Epoch/Unix timestamp in milliseconds.<br><br>Example: 1701090879815. |
| read | object | Information about the action of marking messages as read. |
#| last_read_timestamp | integer | The time when the last message that is marked as seen was sent, in the format of an Epoch/Unix timestamp in milliseconds.<br><br>Example: 1701090879815. |
```

**Example**
```xcodeblock
(code curl http)
{
    "from":"{{username}}", 
    "to":"{{username}}", 
    "unique_identifier": "{{unique_identifier}}",
    "from_user": {
        "id": "{{id}}",
        "role": "personal_account"},
     "to_user": {
        "id": "{{id}}",
        "role": "business_account"},
    "conversation_id":"{{conversation_id}}",
    "timestamp":1701090879815,
    "read": {
        "last_read_timestamp": 1701090879815
    }
}
(/code)
```

### `im_auto_message_config_update`
> <span style="color:DodgerBlue">**Note**</span><br>
> - To ensure that you can receive `im_auto_message_config_update` events, the TikTok account owner needs to authorize your developer app with the `message.list.read` scope (Read the comments and replies of your in-app content).
> - The authorization must remain valid and active. If the authorization expires or is revoked, you will no longer receive `im_auto_message_config_update` events for the TikTok account.


``` xtable
|Field{25%}|Data Type{15%}|Description{60%}|
|---|---|---|
| client_key | string | ID of your developer application.<br><br>To find the App ID, navigate to <b>[My Apps](https://ads.tiktok.com/marketing_api/apps/)</b> > <b>App Detail</b> > <b>Basic Information</b>.<br><br>Example: `bwo1m23456a7k89`. |
| event | string | Event name.<br><br>Enum value:<ul><li>`im_auto_message_config_update`: Automatic message update event. Fired within five minutes of an automatic message being created, deleted, updated, turned on, or turned off. This applies to both automatic messages configured through API and those manually configured through the TikTok app or Business Center.</li></ul> |
| create_time | integer | The time when the event notification is sent, in the format of an Epoch/Unix timestamp in seconds.<br><br>Example: 1714430514. |
| user_openid | string | Application specific unique ID of the TikTok account, obtained via the `open_id` field from the [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1832184175482945#item-link-Obtain%20a%20short-term%20access%20token) and used as the `business_id` parameter in Business Messaging API requests.<br><br>Example: `b1d2f34d-5b6c-78fb-91c2-cd345678912b`. |
| content | string | Content of the Webhook. See <a href="#item-link-content schema for im_auto_message_config_update">`content` schema for `im_auto_message_config_update`</a> for details.|
```

**Example**
```xcodeblock
(code curl http)
{
  "client_key": "{{client_key}}",
  "event": "im_auto_message_config_update",
  "create_time": 1714430514,
  "user_openid": "{{user_openid}}",
  "content": "{\"auto_message_id\":\"{{auto_message_id}}\", \"auto_message_type\":\"WELCOME_MESSAGE\",\"auto_message_action\": \"CREATE\",\"welcome_message\": {\"content\": \"Hi there, Thanks for contacting! Feel free to ask a question here or leave us a message and we'll get back to you shortly. Thank you!\"},\"timestamp\":1739374355}"
}
(/code)
```

#### `content` schema for `im_auto_message_config_update`
``` xtable
|Field{25%}|Data Type{15%}|Description{60%}|
|---|---|---|
| auto_message_id | string | The ID of the automatic message. |
| auto_message_type | string | The type of automatic message.<br><br>Enum values:<ul><li>`WELCOME_MESSAGE`: welcome message. A welcome message is a message that is automatically sent to a user when they start a direct message conversation with you.</li><li>`SUGGESTED_QUESTION`: suggested question. A frequently asked question displayed at the beginning of a chat. When the user selects a suggested question, the user will automatically receive the corresponding preset answer.</li><li>`CHAT_PROMPT`: chat prompt. An interactive button displayed above the input box. When users tap a chat prompt, the system automatically generates a corresponding question for users to start the conversation.</li></ul> |
| auto_message_action | string | The action taken on an automatic message.<br><br><ul><li>For welcome messages, the supported enum values are:<ul><li>`CREATE`: The welcome message was created or updated.</li><li>`DELETE`: Deleting a welcome message is not supported. This indicates the previous welcome message was deleted since you created or updated a new one.</li><li>`ENABLE`: The welcome message was turned on.</li><li>`DISABLE`: The welcome message was turned off.</li></ul><p><span style="color:darkred"><b>Note</b></span>: When you create or update a welcome message, you can receive up to three webhook events, including:<ul><li>one `im_auto_message_config_update` event with `auto_message_action` as `DELETE`</li><li>one `im_auto_message_config_update` event with `auto_message_action` as `CREATE`</li><li>one `im_auto_message_audit_update` event</li></ul></li><li>For suggested questions, the supported enum values are:<ul><li>`CREATE`: The suggested question was created.</li><li>`UPDATE`: The suggested question content was updated.</li><li>`DELETE`: The suggested question was deleted.</li><li>`ENABLE`: The suggested questions were turned on.</li><li>`DISABLE`: The suggested questions were turned off.</li></ul></li><li>For chat prompts, the supported enum values are:<ul><li>`CREATE`: The chat prompt was created.</li><li>`UPDATE`: The chat prompt content was updated.</li><li>`DELETE`: The chat prompt was deleted.</li><li>`ENABLE`: The chat prompts were turned on.</li><li>`DISABLE`: The chat prompts were turned off.</li></ul></li></ul> |
| timestamp | number | The time when an automatic message was created, deleted, updated, turned on, or turned off, in the format of an Epoch/Unix timestamp in milliseconds.<br><br>Example: 1714430514238. |
| welcome_message | object | Returned only when `auto_message_type` is `WELCOME_MESSAGE`.<br><br>Information about the welcome message.<br><br>For welcome messages, the supported `auto_message_action` values are `CREATE`, `DELETE`, `ENABLE`, and `DISABLE`. |
#| content | string | The text of the welcome message.<br><br><p><span style="color:darkred"><b>Note</b></span>: If you enable the welcome message for a Business Account for the first time, the default welcome message will be: `"Hi there,\nThanks for contacting &#123;&#123;display_name&#125;&#125;! Feel free to ask a question here or leave us a message and we'll get back to you shortly.\n\nThank you!"` |
| suggested_question | object | Returned only when `auto_message_type` is `SUGGESTED_QUESTION`.<br><br>Information about the suggested question.<br><br>For suggested questions, the supported `auto_message_action` values are `CREATE`, `UPDATE`, `DELETE`, `ENABLE`, and `DISABLE`. |
#| question | string | A frequently asked question to help users start a conversation. |
#| answer | string | The preset answer that will be automatically sent when a user selects the question. |
| chat_prompt | object | Returned only when `auto_message_type` is `CHAT_PROMPT`.<br><br>Information about the chat prompt.<br><br>For chat prompts, the supported `auto_message_action` values are `CREATE`, `UPDATE`, `DELETE`, `ENABLE`, and `DISABLE`. |
#| title | string | The name of the prompt button that is displayed above the input box. |
#| content | string | The corresponding question for the prompt that the system automatically generates for users to start the conversation when users tap the chat prompt. |
```

**Example**
```xcodeblock
(code curl http)
{
    "auto_message_id":"{{auto_message_id}}", 
    "auto_message_type":"WELCOME_MESSAGE", 
    "auto_message_action": "CREATE",
    "welcome_message": {
        "content": "Hi there, Thanks for contacting! Feel free to ask a question here or leave us a message and we'll get back to you shortly. Thank you!"
    },
    "timestamp":1714430514238
}
(/code)
```

### `im_auto_message_audit_update`

> <span style="color:DodgerBlue">**Note**</span><br>
> - To ensure that you can receive `im_auto_message_audit_update` events, the TikTok account owner needs to authorize your developer app with the `message.list.read` scope (Read the comments and replies of your in-app content).
> - The authorization must remain valid and active. If the authorization expires or is revoked, you will no longer receive `im_auto_message_audit_update` events for the TikTok account.



``` xtable
|Field{25%}|Data Type{15%}|Description{60%}|
|---|---|---|
| client_key | string | ID of your developer application.<br><br>To find the App ID, navigate to <b>[My Apps](https://ads.tiktok.com/marketing_api/apps/)</b> > <b>App Detail</b> > <b>Basic Information</b>.<br><br>Example: `bwo1m23456a7k89`. |
| event | string | Event name.<br><br>Enum value:<ul><li>`im_auto_message_audit_update`: Automatic message audit status update event. Fired within five minutes of an automatic message being rejected or approved. This applies to both automatic messages configured through API and those manually configured through the TikTok app or Business Center.</li></ul> |
| create_time | integer | The time when the event notification is sent, in the format of an Epoch/Unix timestamp in seconds.<br><br>Example: 1714430514. |
| user_openid | string | Application specific unique ID of the TikTok account, obtained via the `open_id` field from the [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1832184175482945#item-link-Obtain%20a%20short-term%20access%20token) and used as the `business_id` parameter in Business Messaging API requests.<br><br>Example: `b1d2f34d-5b6c-78fb-91c2-cd345678912b`. |
| content | string | Content of the Webhook. See <a href="#item-link-content schema for im_auto_message_audit_update">`content` schema for `im_auto_message_audit_update`</a> for details.|
```

**Example**
```xcodeblock
(code curl http)
{
  "client_key": "{{client_key}}",
  "event": "im_auto_message_audit_update",
  "create_time": 1714430514,
  "user_openid": "{{user_openid}}",
  "content": "{\"auto_message_id\":\"{{auto_message_id}}\", \"auto_message_type\":\"WELCOME_MESSAGE\",\"audit_status\": \"APPROVED\",\"timestamp\":1701090879815}"
}
(/code)
```

#### `content` schema for `im_auto_message_audit_update`
``` xtable
|Field{25%}|Data Type{15%}|Description{60%}|
|---|---|---|
| auto_message_id | string | The ID of the automatic message. |
| auto_message_type | string | The type of automatic message.<br><br>Enum values:<ul><li>`WELCOME_MESSAGE`: welcome message. A welcome message is a message that is automatically sent to a user when they start a direct message conversation with you.</li><li>`SUGGESTED_QUESTION`: suggested question. A frequently asked question displayed at the beginning of a chat. When the user selects a suggested question, the user will automatically receive the corresponding preset answer.</li><li>`CHAT_PROMPT`: chat prompt. An interactive button displayed above the input box. When users tap a chat prompt, the system automatically generates a corresponding question for users to start the conversation.</li></ul> |
| audit_status | string | Audit status.<br><br>Enum values:<ul><li>`APPROVED`: The automatic message has been approved.</li><li>`REJECTED`: The automatic message has been rejected.</li></ul> |
| timestamp | number | The time when an automatic message was rejected or approved, in the format of an Epoch/Unix timestamp in milliseconds.<br><br>Example: 1714430514238. |
```

**Example**
```xcodeblock
(code curl http)
{
    "auto_message_id":"{{auto_message_id}}", 
    "auto_message_type":"WELCOME_MESSAGE", 
    "audit_status": "APPROVED",
    "timestamp":1714430514238
}
(/code)
```

### `im_receive_high_intent_comment`
> <span style="color:DodgerBlue">**Note**</span><br>
> To receive this webhook event, you need to enable the Comment-to-Message feature for the Business Account by using [/business/message/direct_reply/update/](https://business-api.tiktok.com/portal/docs?id=1843697551083522) first.

```xtable
|Field{25%}|Data Type{15%}|Description{60%}|
|---|---|---|
|client_key|string|ID of your developer application.<br><br>To find the App ID, navigate to <b>[My Apps](https://ads.tiktok.com/marketing_api/apps/)</b> > <b>App Detail</b> > <b>Basic Information</b>.<br><br>Example: `bwo1m23456a7k89`.|
|event|string|Event name. <br><br>Enum value: <ul><li>`im_receive_high_intent_comment`: Receive real-time notifications if a user leaves a high-intent comment on your video. A high-intent comment is one that expresses a strong intention to purchase or seek further information. </li></ul>|
|create_time|integer|The time when the event notification is sent, in the format of an Epoch/Unix timestamp in seconds.<br><br>Example: 1714430514.|
|user_openid|string|Application specific unique ID of the TikTok account, obtained via the `open_id` field from the [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1832184175482945#item-link-Obtain%20a%20short-term%20access%20token) and used as the `business_id` parameter in Business Messaging API requests.<br><br>Example: `b1d2f34d-5b6c-78fb-91c2-cd345678912b`.|
|content|string|Content of the Webhook. <br><br>See <a href="#item-link-content schema for im_receive_high_intent_comment">`content` schema for `im_receive_high_intent_comment`</a> for details.|
```

**Example**

```xcodeblock
(code json)
{
  "client_key": "{{client_key}}",
  "event": "im_receive_high_intent_comment",
  "create_time": 1701090882,
  "user_openid": "{{user_openid}}",
  "content": "{\"from\":\"vappe256\",\"to\":\"userxyz\",\"unique_identifier\":\"{{unique_identifier}}\",\"from_user\": {\"id\": \"{{id}}\",\"role\": \"personal_account\"}, \"to_user\": {\"id\": \"{{id}}\",\"role\": \"business_account\"},\"comment_id\":\"{{comment_id}}\",\"comment_text\":\"{{comment_text}}\", \"is_follower\":true}"
}
(/code)
```

#### `content` schema for `im_receive_high_intent_comment`

```xtable
|Field{25%}|Data Type{15%}|Description{60%}|
|---|---|---|
|from|string|TikTok username of the comment sender.|
|to|string|TikTok username of the comment receiver.|
|timestamp|number|The time of the event, in the format of an Epoch/Unix timestamp in milliseconds. <br><br>Example: 1701090879815.|
|unique_identifier|string|Unique ID of the TikTok user in the conversation.<br><br><p><span style="color:darkred"><b>Note</b></span>: This identifier remains consistent across different APIs, such as [the Accounts comment listing endpoint](https://business-api.tiktok.com/portal/docs?id=1760232109619202), facilitating cross-referencing and integration.</p>|
|from_user|object|User information of the sender.|
#|role|string|The TikTok account type of the sender.<br><br>Enum values:<ul><li>`business_account`: Business Account.</li><li>`personal_account`: Personal Account.</li></ul>|
#|id|string|The identifier of the sender<br><br><ul><li>When `role` is `business_account`, this field is the same as the `business_id`, the application specific unique identifier for the TikTok Business Account.</li><li>When `role` is `personal_account`, this field is the same as the `unique_identifier`, the globally unique identifier assigned to the TikTok Personal Account user in the conversation. This identifier remains consistent across different APIs, such as [the Accounts comment listing endpoint](https://business-api.tiktok.com/portal/docs?id=1760232109619202), facilitating cross-referencing and integration.</li></ul>|
|to_user|object|User information of the receiver.|
#|role|string|The TikTok account type of the receiver.<br><br>Enum values:<ul><li>`business_account`: Business Account.</li><li>`personal_account`: Personal Account.</li></ul>|
#|id|string|The identifier of the receiver.<br><br><ul><li>When `role` is `business_account`, this field is the same as the `business_id`, the application specific unique identifier for the TikTok Business Account.</li><li>When `role` is `personal_account`, this field is the same as the `unique_identifier`, the globally unique identifier assigned to the TikTok Personal Account user in the conversation. This identifier remains consistent across different APIs, such as [the Accounts comment listing endpoint](https://business-api.tiktok.com/portal/docs?id=1760232109619202), facilitating cross-referencing and integration.</li></ul>|
|comment_id|string|Unique identifier for the comment.|
|comment_text|string|Text content of the comment.<br><br>Example: `This is a comment #comment 🙂`.|
|is_follower|boolean|Whether the Personal Account follows the Business Account in the conversation.<br><br>Supported values: `true`, `false`.|
```

**Example**

```xcodeblock
(code json)
{
    "from":"{{username}}",
    "to":"{{username}}",
    "unique_identifier": "{{unique_identifier}}",
    "from_user": {
        "id": "{{id}}",
        "role": "business_account"},
     "to_user": {
        "id": "{{id}}",
        "role": "personal_account"},
    "comment_id":"{{comment_id}}",
    "comment_text":"{{comment_text}}",
    "is_follower" : true
}
(/code)
```


# Next step
[Authentication for Business Messaging API](https://business-api.tiktok.com/portal/docs?id=1832184175482945)

