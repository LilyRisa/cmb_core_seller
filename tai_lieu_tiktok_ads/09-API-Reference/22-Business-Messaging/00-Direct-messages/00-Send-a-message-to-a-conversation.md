---
title: "Send a message to a conversation"
doc_id: 1832184403754242
path: "API Reference / Business Messaging / Direct messages / Send a message to a conversation"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Send a message to a conversation

Use this endpoint to send a message to a conversation. 

><span style="color:DodgerBlue">**Note**</span><br> 
> - You can send up to 10 messages within a 48-hour period after receiving a message from a TikTok user. For example, if you receive message A from a TikTok user, you can send up to 10 messages within the next 48 hours. If the same user sends message B later, a new 48-hour window begins, allowing you to send another 10 messages to the user.
> - A message cannot consist of both text and image simultaneously.

## Before you start
Because of the sensitive nature of direct message data, TikTok has implemented a higher level of due diligence for developers that will process direct messages on behalf of Business Account users. Ensure that you [have completed the necessary data security and privacy reviews and obtained access to Business Messaging API](https://business-api.tiktok.com/portal/docs?id=1832184145137922) before calling this endpoint.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/business/message/send/

**Method** POST

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|A short-term access token authorized by the TikTok Business Account owner. <br>For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1832184175482945).|
|Content-Type {Required}|string|Request message type.<br>Allowed format: `application/json`.  |
```


**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| business_id {Required}| string | Application specific unique identifier for the TikTok Business Account. <br><br> Pass the value of the `open_id` field returned in the response of [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1832184175482945#item-link-Obtain%20a%20short-term%20access%20token) to this field. |
| recipient_type {+Conditional}| string | <ul><li>Required when `direct_reply` is not specified.</li><li>Not supported when `direct_reply` is specified.</li></ul><brThe type of recipient. <br>  <br> Currently, the only supported value is `CONVERSATION`. |
| recipient {+Conditional}| string | <ul><li>Required when `direct_reply` is not specified.</li><li>Not supported when `direct_reply` is specified.</li></ul><br>Recipient. <br>  <br> When `recipient_type` is `CONVERSATION`, you need to specify `conversation_id` in this field. <br> <br>  To get the conversation ID, use [/business/message/conversation/list/](https://business-api.tiktok.com/portal/docs?id=1832184415059970). |
| message_type {Required} | string | The type of the message. <br> <br> Enum values: <ul> <li>`TEXT`: text message.</li> <li>`IMAGE`: image message.</li><li>`SHARE_POST`: TikTok post message.</li><li>`TEMPLATE`: message template.</li> <li>`SENDER_ACTION`: Sender action. Note that for this type, the returned message ID is an empty string (`""`) because no actual message is sent.</li> </ul> |
| text {+Conditional} | object | Required when `message_type` is `TEXT`. <br><br> Text information. <br> A message cannot include both a text and an image. |
#| body {+Conditional} | string |Required when `text` is specified.<br><br>Text content. <br><br>Length limit: 6,000 characters, including spaces and emojis.|
| image {+Conditional}| object | Required when `message_type` is `IMAGE`. <br><br> Image information. <br> A message cannot include both a text and an image. <br>  <br> Sending and receiving image attachments via API are only available when both the sender and receiver are in countries that support image attachments in direct messages. Image sharing is not available in all markets. |
#| media_id {+Conditional} | string |Required when `image` is specified.<br><br>Image ID. <br><br>You can use [/business/message/capabilities/get/](https://business-api.tiktok.com/portal/docs?id=1836061597865986) to query if the Business Account can send a media attachment to a conversation.<br>Before sending an image, use [/business/message/media/upload/](https://business-api.tiktok.com/portal/docs?id=1832184437720193) to upload an image to your account and  obtain the `media_id`.  |
| share_post {+Conditional}| object | Required when `message_type` is `SHARE_POST`. <br><br> Information about the post to be shared. |
#| item_id {+Conditional} | string |Required when `share_post` is specified.<br><br>The ID of the TikTok post to share with others. <br><br>To obtain the IDs of TikTok posts associated with your TikTok account, use [/business/video/list/](https://business-api.tiktok.com/portal/docs?id=1762228421622786).<br><br><p><span style="color:darkred"><b>Note</b></span>: Currently, you can only share your posts.|
|template{+Conditional}|object|Required when `message_type` is `TEMPLATE`.<br><br>Details about the message template to send.|
#|type{+Conditional}|string|Required when `template` is specified.<br><br>The type of message template.<br><br>Enum values: <ul><li>`QA_BUTTON_CARD`: Q&A button card, a card showing a question and buttons that represent answers. When users click one of the buttons, the text content of the button will be sent to the conversation thread on behalf of the user as an answer to the pre-set question on the card.</li><li>`QA_LINK_CARD`: Q&A text link card, a card showing a question and interactive text links that represent answers to the question. When users click one of the text links, the text content of the text link will be sent to the conversation thread on behalf of the user as an answer to the pre-set question on the card.</li></ul>|
#|title{+Conditional}|string|Required when `template` is specified.<br><br>The pre-set question on the card.<br><br>Length limit: 40 characters.|
#|buttons{+Conditional}|object[]|Required when `template` is specified.<br><br>A list of buttons or text links that represent answers to the question on the card.<br><br>Size range: 1-3.|
##|type{+Conditional}|string|Required when `buttons` is specified.<br><br>The type of the button or text link.<br><br>Enum value: <ul><li>`REPLY`: Reply button or reply text link. When users click the button or text link, the system will send the text content of the button or text link as a reply message on behalf of the user in the conversation.</li></ul>|
##|title{+Conditional}|string|Required when `buttons` is specified.<br><br>The text content of the button or text link.<br><br>Length limit: <ul><li>When `type` of the message template is `QA_BUTTON_CARD`: 20 characters.</li><li>When `type` of the message template is `QA_LINK_CARD`: 40 characters.</li></ul>|
##|id|string|A self-defined ID for the button or text link.<br><br>You can use this ID to distinguish between different buttons and text links.<br><br>Length limit: 40 characters.|
| sender_action{+Conditional} | string | Required when `message_type` is `SENDER_ACTION`. <br><br> The sender's action in the messaging session. <br><br><ul><li>`TYPING`: The sender is preparing a reply. This displays a “Typing” icon in the messaging window, which will automatically disappear after five seconds.</li><li>`MARK_READ`: The sender marks all messages in the session as read, displaying a “Seen” icon for those messages.</li></ul> |
| referenced_message_info{+Conditional} | object | Required when you want to send a reply to a message.<br><br>Information about the original message to reply to. |
#| referenced_message_id {+Conditional}| string |Required when `referenced_message_info` is specified.<br><br> The ID of the original message that you want to reply to. <br><br> The original message can be a text, an image, or a TikTok post. To obtain such a message, use [/business/message/content/list/](https://business-api.tiktok.com/portal/docs?id=1832184425841170) and ensure the `message_type` of the message is `TEXT`, `IMAGE`, or `SHARE_POST`. <br> <br> When using this field, make sure to set the `message_type` to `TEXT` and specify `text` simultaneously, as only text replies are supported. After you send the reply, the original message will be quoted in the conversation thread. |
|direct_reply {+Conditional}|object|Required when you are sending a direct reply.<br><br>Details of the direct reply.<br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>To send a direct reply, ensure that Comment-to-Message is enabled for the Business Account. To obtain the Comment-to-Message setting for a Business Account, use [/business/message/direct_reply/get/](https://business-api.tiktok.com/portal/docs?id=1843697576682497).</li><li>The Comment-to-Message feature is only available for Business Accounts registered in Vietnam, Indonesia, and Thailand.</li><li>Once you enable Comment-to-Message for a Business Account, the Business Account can only reply to comments published by TikTok accounts that are registered in the APAC, LATAM, and METAP regions. </li></ul>|
#|reply_type {+Conditional}|string|Required when `direct_reply` is specified.<br><br>The type of direct reply. <br><br>Enum value:<ul><li>`COMMENT_REPLY`: a reply to a comment on a video.</li></ul><br><br><p><span style="color:darkred"><b>Note</b></span>: To send a comment reply, ensure that all the following conditions are all met:<ul><li>The comment that you want to reply to is a first-level comment on a  video of the Business Account.</li><li>Your reply is sent within 48 hours after the user posted the comment.</li><li>The comment has not been replied to through direct messages yet in any way, including from the TikTok app or via the API.</li><li>The user who posted the comment had no direct messaging communication with the Business Account in the past 24 hours.</li><li>The user who posted the comment is over 18 years old.</li></ul>|
#|comment_reply {+Conditional}|object|Required when `direct_reply` is specified.<br><br>Details of the comment reply.|
##|comment_id {+Conditional}|string|Required when `direct_reply` is specified.<br><br>ID of the high-intent comment you want to reply to. <br>A high-intent comment is one that expresses a strong intention to purchase or seek further information. <br><br>You can obtain the ID of a high-intent comment from the webhook event [im_receive_high_intent_comment](https://business-api.tiktok.com/portal/docs?id=1832190670631937#item-link-im_receive_high_intent_comment).|
```


### Example
#### Send a text message
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/business/message/send/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "business_id": "{{business_id}}",
    "recipient_type": "CONVERSATION",
    "recipient": "{{recipient}}",
    "message_type": "TEXT",
    "text": {
        "body": "{{body}}"
    }
}'
(/code)
```
#### Send an image message
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/business/message/send/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "business_id": "{{business_id}}",
    "recipient_type": "CONVERSATION",
    "recipient": "{{recipient}}",
    "message_type": "IMAGE",
    "image": {
        "media_id": "{{media_id}}"
    }
}'
(/code)
```
#### Send a Share Post message
```xcodeblock
(code curl http)
curl --location --request POST'https://business-api.tiktok.com/open_api/v1.3/business/message/send/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "business_id": "{{business_id}}",
    "recipient_type": "CONVERSATION",
    "recipient": "{{recipient}}",
    "message_type": "SHARE_POST",
    "share_post": {
        "item_id": "{{item_id}}"
    }
}'
(/code)
```

#### Send a Q&A button card
```xcodeblock
(code curl http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/business/message/send/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "business_id": "{{business_id}}",
    "recipient_type": "CONVERSATION",
    "recipient": "{{recipient}}",
    "message_type": "TEMPLATE",
    "template": {
        "type": "QA_BUTTON_CARD",
        "title": "{{title}}",
        "buttons": [
            {
                "type": "REPLY",
                "title": "{{title}}",
                "id": "{{id}}"
            },
            {
                "type": "REPLY",
                "title": "{{title}}",
                "id": "{{id}}"
            },
            {
                "type": "REPLY",
                "title": "{{title}}",
                "id": "{{id}}"
            }          
        ]
    }
}'
(/code)
```

#### Send a Q&A text link card
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/business/message/send/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "business_id": "{{business_id}}",
    "recipient_type": "CONVERSATION",
    "recipient": "{{recipient}}",
    "message_type": "TEMPLATE",
    "template": {
        "type": "QA_LINK_CARD",
        "title": "{{title}}",
        "buttons": [
            {
                "type": "REPLY",
                "title": "{{title}}",
                "id": "{{id}}"
            },
            {
                "type": "REPLY",
                "title": "{{title}}",
                "id": "{{id}}"
            },
            {
                "type": "REPLY",
                "title": "{{title}}",
                "id": "{{id}}"
            }          
        ]
    }
}'
(/code)
```

#### Send a typing action
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/business/message/send/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "business_id": "{{business_id}}",
    "recipient_type": "CONVERSATION",
    "recipient": "{{recipient}}",
    "message_type": "SENDER_ACTION",
    "sender_action": "TYPING"
}'
(/code)
```
#### Send a mark-as-read action
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/business/message/send/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "business_id": "{{business_id}}",
    "recipient_type": "CONVERSATION",
    "recipient": "{{recipient}}",
    "message_type": "SENDER_ACTION",
    "sender_action": "MARK_READ"
}'
(/code)
```
#### Send a text reply to a message
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/business/message/send/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "business_id": "{{business_id}}",
    "recipient_type": "CONVERSATION",
    "recipient": "{{recipient}}",
    "message_type": "TEXT",
    "text": {
        "body": "{{body}}"
    },
    "referenced_message_info":{
        "referenced_message_id":"{{referenced_message_id}}"       
    }
}'
(/code)
```
#### Send a text reply to a high-intent comment
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/business/message/send/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "business_id": "{{business_id}}",
    "direct_reply": {
        "reply_type":"COMMENT_REPLY",
        "comment_reply":{
            "comment_id":"{{comment_id}}",
        }
    },
    "message_type": "TEXT",
    "text": {
        "body": "{{body}}"
    }
}'
(/code)
```

## Response

``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://business-api.tiktok.com/portal/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://business-api.tiktok.com/portal/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data.|
#| message | object | Message details. |
##| message_id | string | Message ID. <br> <br><p><span style="color:darkred"><b>Note</b></span>: If `message_type` is set to `SENDER_ACTION` in the request, the value of this field will be an empty string (`""`) because no actual message is sent. |
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
        "message": {
            "message_id": "{{message_id}}"
        }
    }
}
(/code)
```


