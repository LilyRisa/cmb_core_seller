---
title: "Get a list of messages"
doc_id: 1832184425841170
path: "API Reference / Business Messaging / Direct messages / Get a list of messages"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get a list of messages

Use this endpoint to get a list of messages in a conversation. 

><span style="color:DodgerBlue">**Note**</span><br> 
> - A maximum of 20 of the most recent messages will be returned for each conversation.

## Before you start
Because of the sensitive nature of direct message data, TikTok has implemented a higher level of due diligence for developers that will process direct messages on behalf of Business Account users. Ensure that you [have completed the necessary data security and privacy reviews and obtained access to Business Messaging API](https://business-api.tiktok.com/portal/docs?id=1832184145137922) before calling this endpoint.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/business/message/content/list/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|A short-term access token authorized by the TikTok Business Account owner. <br>For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1832184175482945).|
```


**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| business_id {Required}| string | Application specific unique identifier for the TikTok Business Account. <br><br> Pass the value of the `open_id` field returned in the response of [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1832184175482945#item-link-Obtain%20a%20short-term%20access%20token) to this field. |
|conversation_id{Required}|string|Conversation ID.<br><br>To get the conversation ID, use [/business/message/conversation/list/](https://business-api.tiktok.com/portal/docs?id=1832184415059970).<br><br><p><span style="color:darkred"><b>Note</b></span>: If you receive a `"Param conversation_id is invalid."` error, it may be due to encoding issues. Try replacing any "+" characters with "%2B" to resolve potential encoding issues.|
```


### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/business/message/content/list/?business_id={{business_id}}&conversation_id={{conversation_id}}' \
--header 'Access-Token: {{Access-Token}}' 
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
#|messages|object[]|Messages in the conversation.|
##|sender|string|TikTok username of the message sender.|
##|recipient|string|TikTok username of the message recipient.|
##|conversation_id|string|Conversation ID.<br><br>Example: `a1AbcBdeCfghDijkEF2b3+lmGn==`.|
##|message_id|string|Message ID.<br><br>Example: `a1AbcdefBCghDE2iFGjk3HIJ4l==`.|
##|timestamp|integer|The time when the message was sent, in the format of an Epoch/Unix timestamp in milliseconds.<br><br>Example: 1719447205701.|
##| message_tag | object | Additional information about the message. |
###| source | string | The source of the message, indicating the platform from which the message was sent.<br><br>Enum values:<ul><li>`APP`: TikTok mobile app.</li><li>`WEB`: TikTok Web or TikTok Business Center.</li><li>`API`: TikTok Business Messaging API.</li><li>`OTHERS`: Other TikTok internal servers or tools, including automatic bots.</li><li>`UNKNOWN_SOURCE`: Unknown source.</li></ul> |
##|message_type|string|The type of the message.<br><br>Enum values:<ul><li>`TEXT`: text.</li><li>`IMAGE`: image.</li><li>`SHARE_POST`: TikTok post.</li><li>`VIDEO`: video.</li><li>`EMOJI`: emoji.</li><li>`STICKER`: sticker.</li><li>`TEMPLATE`: message template.</li><li>`OTHER`: other message types.</li></ul>|
##|auto_message_type |string|The type of automatic message.<br><br>Enum values:<ul><li>`WELCOME_MESSAGE`: welcome message. A welcome message is a message that is automatically sent to a user when they start a direct message conversation with you.</li><li>`SUGGESTED_QUESTION`: suggested question. A suggested question is a frequently asked question displayed at the beginning of a chat. When the user selects a suggested question, the user will automatically receive the corresponding preset answer.</li><li>`AUTO_REPLY`: keyword reply. A keyword reply is an automated response triggered by specific keywords in a message.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When the message is not an automatic message, this field will not be returned.</p>|
##|text|object|Returned only when `message_type` is `TEXT`.<br><br>Text information.|
###|body|string|Text content.|
##|image|object|Returned only when `message_type` is `IMAGE`.<br><br>Image information.|
###|media_id|string|Image ID.<br><br>To download the image associated with the image ID, use [/business/message/media/download/](https://business-api.tiktok.com/portal/docs?id=1832184455450626). |
##|share_post|object|Returned only when `message_type` is `SHARE_POST`.<br><br>Information about the post shared.|
###|embed_url|string|An embeddable link for the TikTok post.<br>This URL can be used to embed the TikTok post in external websites or applications.<br>If the privacy setting of the post is "Friends" or "Only you", the post will not be viewable through this link.|
##| reactions | object[] | Information about the list of reactions in response to the message. |
###| type | string | The type of reaction.<br><br>Enum values:<ul><li>`EMOJI`: A text emoji.</li><li>`AI_EMOJI`: An AI-generated emoji derived from a selfie-based avatar.</li></ul> |
###| emoji | string | Returned only when `type` is `EMOJI`.<br><br>The text of the text emoji. |
###| ai_emoji_url | string | Returned only when `type` is `AI_EMOJI`.<br><br>The URL to the AI-generated emoji image.<br><br>Validity period: 30 days.<br><br>The expiration time is included in the URL after the `x-expires` parameter, in the format of an Epoch/Unix timestamp in seconds. |
###| unique_identifier | string | The identifier of the sender.<br><br><ul><li>When `role` within the object `from_user` is `BUSINESS_ACCOUNT`, this field is the same as the `business_id`, the application specific unique identifier for the TikTok Business Account.</li><li>When `role` within the object `from_user` is `PERSONAL_ACCOUNT`, this field is the same as the `unique_identifier`, the globally unique identifier assigned to the TikTok Personal Account user in the conversation. This identifier remains consistent across different APIs, such as [the Accounts comment listing endpoint](https://business-api.tiktok.com/portal/docs?id=1760232109619202), facilitating cross-referencing and integration.</li></ul> |
###| timestamp | integer | The timestamp when the reaction was made, in the format of an Epoch/Unix timestamp in milliseconds.<br><br>Example: 1719447205701. |
##| sticker | object | Returned only when `message_type` is `STICKER`.<br><br>Information about the sticker. |
###| url | string | The URL to the sticker.<br><br>Validity period: 30 days.<br><br>The expiration time is included in the URL after the `x-expires` parameter, in the format of an Epoch/Unix timestamp in seconds. |
##| emoji | object | Returned only when `message_type` is `EMOJI`.<br><br>Information about the emoji. |
###| url | string | The URL to the emoji. <br><br>The URL will not expire. |
##| video | object | Returned only when `message_type` is `VIDEO`.<br><br>Information about the video. |
###| media_id | string | The video ID.<br><br>To download the video associated with the video ID, use [/business/message/media/download/](https://business-api.tiktok.com/portal/docs?id=1832184455450626). |
##|template|object|Returned only when `message_type` is `TEMPLATE`.<br><br>Details about the message template sent.|
###|type|string|The type of message template.<br><br>Enum values: <ul><li>`QA_BUTTON_CARD`: Q&A button card, a card showing a question and buttons that represent answers. When users click one of the buttons, the text content of the button will be sent to the conversation thread on behalf of the user as an answer to the pre-set question on the card.</li><li>`QA_LINK_CARD`: Q&A text link card, a card showing a question and interactive text links that represent answers to the question. When users click one of the text links, the text content of the text link will be sent to the conversation thread on behalf of the user as an answer to the pre-set question on the card.</li></ul>|
###|title|string|The pre-set question on the card.|
###|buttons|object[]|A list of buttons or text links that represent answers to the question on the card.|
####|type|string|The type of the button or text link.<br><br>Enum value: <ul><li>`REPLY`: Reply button or reply text link. When users click the button or text link, the system will send the text content of the button or text link as a reply message on behalf of the user in the conversation.</li></ul>|
####|title|string|The text content of the button or text link.|
####|id|string|A self-defined ID for the button or text link.<br><br>You can use this ID to distinguish between the buttons and text links.|
##|from_user|object|User information of the sender.|
###|role|string|The TikTok account type of the sender.<br><br>Enum values:<ul><li>`BUSINESS_ACCOUNT`: Business Account.</li><li>`PERSONAL_ACCOUNT`: Personal Account.</li></ul>|
###|id|string|The identifier of the sender.<br><br><ul><li>When `role` is `BUSINESS_ACCOUNT`, this field is the same as the `business_id`, the application specific unique identifier for the TikTok Business Account.</li><li>When `role` is `PERSONAL_ACCOUNT`, this field is the same as the `unique_identifier`, the globally unique identifier assigned to the TikTok Personal Account user in the conversation. This identifier remains consistent across different APIs, such as [the Accounts comment listing endpoint](https://business-api.tiktok.com/portal/docs?id=1760232109619202), facilitating cross-referencing and integration.</li></ul>|
##|to_user|object|User information of the receiver.|
###|role|string|The TikTok account type of the receiver.<br><br>Enum values:<ul><li>`BUSINESS_ACCOUNT`: Business Account.</li><li>`PERSONAL_ACCOUNT`: Personal Account.</li></ul>|
###|id|string|The identifier of the receiver.<br><br><ul><li>When `role` is `BUSINESS_ACCOUNT`, this field is the same as the `business_id`, the application specific unique identifier for the TikTok Business Account.</li><li>When `role` is `PERSONAL_ACCOUNT`, this field is the same as the `unique_identifier`, the globally unique identifier assigned to the TikTok Personal Account user in the conversation. This identifier remains consistent across different APIs, such as [the Accounts comment listing endpoint](https://business-api.tiktok.com/portal/docs?id=1760232109619202), facilitating cross-referencing and integration.</li></ul>|
##|referenced_message_info|object|Information about the original message that is replied to.|
###|referenced_message_id|string|The ID of the original message that is replied to.<br><br><ul><li>When `message_type` is `TEXT` and `referenced_message_id` is returned, the object `text` represents the text reply to the original message.</li><li>When `message_type` is `OTHER` and `referenced_message_id` is returned, you won't be able to retrieve the content of the reply itself through API.</li></ul>|
#|participants|object[]|Information of participants in the conversation.|
##|role|string|The TikTok account type of the participant.<br><br>Enum values:<ul><li>`BUSINESS_ACCOUNT`: Business Account.</li><li>`PERSONAL_ACCOUNT`: Personal Account.</li></ul>|
##|id|string|The identifier of the participant.<br><br><ul><li>When `role` is `PERSONAL_ACCOUNT`, this field is the same as the `unique_identifier`, the globally unique identifier assigned to the TikTok Personal Account user in the conversation. This identifier remains consistent across different APIs, such as [the Accounts comment listing endpoint](https://business-api.tiktok.com/portal/docs?id=1760232109619202), facilitating cross-referencing and integration.</li><li>When `role` is `BUSINESS_ACCOUNT`, this field is the same as the `business_id`, the application specific unique identifier for the TikTok Business Account.</li></ul>|
##|display_name|string|The TikTok display name (nickname) of the participant.|
##|profile_image|string|Temporary URL for the TikTok profile image of the participant and needs to be re-acquired after expiration.<br><br>The expiration time is included in the URL after the `x-expires` parameter, in the format of an Epoch/Unix timestamp in seconds.<br><br>Example: `https://p16-sign-sg.tiktokcdn.com/aweme/100x100/tos-alisg-avt-1234/5678a9b1c2d345ef6789123b4ghijkl5.jpeg?lk3s=a5d48078&nonce=56434&refresh_token=3b1a321962699a37d22f145bf20cf766&<b>x-expires=1719579600</b>&x-signature=RTkvJazVm0fjkS%2FGfNHOuqIpla4%3D&shp=a5d48078&shcp=8aecc5ac`|
##|is_follower|boolean|Returned only when `role` is `PERSONAL_ACCOUNT`.<br><br>Whether the Personal Account follows the Business Account in the conversation.<br><br>Supported values: `true`, `false`.|
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
       "participants": [
            {
                "id": "{{id}}",
                "role": "BUSINESS_ACCOUNT",
                "profile_image": "{{profile_image}}",
                "display_name": "{{display_name}}"
            },
            {
                "id": "{{id}}",
                "role": "PERSONAL_ACCOUNT",
                "profile_image": "{{profile_image}}",
                "display_name": "{{display_name}}",
                "is_follower": true
            }
        ],
        "messages": [
            {
                "text": {
                    "body": "Hello!', 'How are you?"
                },
                "sender": "abc_x_def",
                "recipient": "abc.abc.test",
                "message_id": "{{message_id}}",
                "conversation_id": "{{conversation_id}}",
                "timestamp": 1705089513995,
                "message_type": "TEXT",
                "from_user": {
                    "id": "{{id}}",
                    "role": "BUSINESS_ACCOUNT"
                },
                "to_user": {
                    "id": "{{id}}",
                    "role": "PERSONAL_ACCOUNT"
                }
            },
            {
                "text": {
                    "body": "👍👍"
                },
                "sender": "abc.abc.test",
                "recipient": "abc_x_def",
                "message_id": "{{message_id}}",
                "conversation_id": "{{conversation_id}}",
                "timestamp": 1705089491478,
                "message_type": "TEXT",
                "from_user": {
                    "id": "{{id}}",
                    "role": "BUSINESS_ACCOUNT"
                },
                "to_user": {
                    "id": "{{id}}",
                    "role": "PERSONAL_ACCOUNT"
                }
            }
        ]
    }
}
(/code)
```
