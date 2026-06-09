---
title: "Update the automatic message for a Business Account"
doc_id: 1832184596566017
path: "API Reference / Business Messaging / Automatic messages / Update the automatic message for a Business Account"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Update the automatic message for a Business Account

Use this endpoint to update an automatic message for a Business Account.

After you update an automatic message, use [/business/message/auto_message/get/](https://business-api.tiktok.com/portal/docs?id=1832184619749377) to verify the status of the message. 


## Before you start
Because of the sensitive nature of direct message data, TikTok has implemented a higher level of due diligence for developers that will process direct messages on behalf of Business Account users. Ensure that you [have completed the necessary data security and privacy reviews and obtained access to Business Messaging API](https://business-api.tiktok.com/portal/docs?id=1832184145137922) before calling this endpoint.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/business/message/auto_message/update/

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
| auto_message_id {Required}| string | The ID of the automatic message. <br><br>To retrieve the automatic messages for a Business Account, use [/business/message/auto_message/get/](https://business-api.tiktok.com/portal/docs?id=1832184619749377).|
| auto_message_type{Required} | string | The type of automatic message.<br><br>Enum values: <ul><li>`WELCOME_MESSAGE`: welcome message. A welcome message is a message that is automatically sent to a user when they start a direct message conversation with you.</li><li>`SUGGESTED_QUESTION`: suggested question. A suggested question is a frequently asked question displayed at the beginning of a chat. When the user selects a suggested question, the user will automatically receive the corresponding preset answer.</li><li>`CHAT_PROMPT`: chat prompt. A chat prompt is an interactive button displayed above the input box. When users tap a chat prompt, the system will automatically generate a corresponding question for users to start the conversation.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: An automatic message that is pending review cannot be updated. To check the review status of an automatic message, use [/business/message/auto_message/get/](https://business-api.tiktok.com/portal/docs?id=1832184619749377) and ensure the returned `audit_status` for the automatic message is not `REVIEWING`. |
| welcome_message {+Conditional} | object | Required when `auto_message_type` is `WELCOME_MESSAGE`.<br><br>Information about the welcome message. |
#| content{+Conditional}  | string | Required when `welcome_message` is passed.<br><br>The text of the welcome message.<br><br>Example: `Hi there, Please leave us a message and we will respond to you shortly. Thank you!`. |
| suggested_question {+Conditional} | object | Required when `auto_message_type` is `SUGGESTED_QUESTION`.<br><br>Information about the suggested question.<br><br>You can only update one suggested question per call.<br>To update multiple suggested questions, call `/business/message/auto_message/update/` multiple times. |
#| question{+Conditional}  | string | Required when `suggested_question` is passed.<br><br>A frequently asked question to help users start a conversation.<br><br>Length limit: 80 characters. |
#| answer{+Conditional}  | string | Required when `suggested_question` is passed.<br><br>The preset answer that will be automatically sent when a user selects the question.<br><br>Length limit: 200 characters. |
| chat_prompt {+Conditional} | object | Required when `auto_message_type` is `CHAT_PROMPT`.<br><br>Information about the chat prompt. |
#| title{+Conditional}  | string | Required when `chat_prompt` is passed.<br><br>The name of the prompt button that is displayed above the input box.<br><br>Length limit: 18 characters.<br><br>You can use text, system emojis, and both English and non-English languages.<br><br>To ensure optimal display, keep the name concise and clear. |
#| content {+Conditional} | string | Required when `chat_prompt` is passed.<br><br>The corresponding question for the prompt that the system automatically generates for users to start the conversation when users tap the chat prompt.<br><br>Length limit: 150 characters.<br><br>You can use text, system emojis, and both English and non-English languages. |
```


### Example
#### Update a welcome message
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/business/message/auto_message/update/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "business_id": "{{business_id}}",
    "auto_message_id": "{{auto_message_id}}",
    "auto_message_type": "WELCOME_MESSAGE",
    "welcome_message": {
        "content": "{{content}}"
    }
}'
(/code)
```
#### Update a suggested question
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/business/message/auto_message/update/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "business_id": "{{business_id}}",
    "auto_message_id": "{{auto_message_id}}",
    "auto_message_type": "SUGGESTED_QUESTION",
    "suggested_question": {
        "question": "{{question}}",
        "answer":"{{answer}}"
    }
}'
(/code)
```
#### Update a chat prompt
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/business/message/auto_message/update/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "business_id": "{{business_id}}",
    "auto_message_id": "{{auto_message_id}}",
    "auto_message_type": "CHAT_PROMPT",
    "chat_prompt": {
        "title": "{{title}}",
        "content":"{{content}}"
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
#| auto_message | object | Information about the automatic message. |
##| auto_message_id | string | The ID of the automatic message. |
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
        "auto_message": 
            {
             "auto_message_id": "{{auto_message_id}}"
            }
    }
}
(/code)
```
