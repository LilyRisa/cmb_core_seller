---
title: "Get the automatic messages for a Business Account"
doc_id: 1832184619749377
path: "API Reference / Business Messaging / Automatic messages / Get the automatic messages for a Business Account"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the automatic messages for a Business Account

Use this endpoint to retrieve the automatic messages for a Business Account.

## Before you start
Because of the sensitive nature of direct message data, TikTok has implemented a higher level of due diligence for developers that will process direct messages on behalf of Business Account users. Ensure that you [have completed the necessary data security and privacy reviews and obtained access to Business Messaging API](https://business-api.tiktok.com/portal/docs?id=1832184145137922) before calling this endpoint.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/business/message/auto_message/get/

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
| auto_message_type{Required} | string | The type of automatic message.<br><br>Enum values: <ul><li>`WELCOME_MESSAGE`: welcome message. A welcome message is a message that is automatically sent to a user when they start a direct message conversation with you. Each Business Account can have up to one welcome message.</li><li>`SUGGESTED_QUESTION`: suggested question. A suggested question is a frequently asked question displayed at the beginning of a chat. When the user selects a suggested question, the user will automatically receive the corresponding preset answer. Each Business Account can have up to three suggested questions.</li><li>`CHAT_PROMPT`: chat prompt. A chat prompt is an interactive button displayed above the input box. When users tap a chat prompt, the system will automatically generate a corresponding question for users to start the conversation. Each Business Account can have up to six chat prompts.</li></ul> |
| auto_message_id | string | The ID of the automatic message. |
```


### Example
#### Retrieve the welcome message for a Business Account
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/business/message/auto_message/get/?business_id={{business_id}}&auto_message_type=WELCOME_MESSAGE' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```
#### Retrieve the suggested questions for a Business Account
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/business/message/auto_message/get/?business_id={{business_id}}&auto_message_type=SUGGESTED_QUESTION' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```
#### Retrieve the chat prompts for a Business Account
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/business/message/auto_message/get/?business_id={{business_id}}&auto_message_type=CHAT_PROMPT' \
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
#| business_id | string | Application specific unique identifier for the TikTok Business Account. |
#| operation_status | string | The status of the automatic message.<br><br>Enum values: <ul><li>`ENABLE`: The automatic message is turned on.</li><li>`DISABLE`: The automatic message is turned off.</li></ul><br>If you want to turn on or turn off an automatic message, use [/business/message/auto_message/status/update/](https://business-api.tiktok.com/portal/docs?id=1832184596566017). |
#| auto_messages | object[] | The list of automatic messages. |
##| auto_message_id | string | The ID of the automatic message. |
##| auto_message_type | string | The type of automatic message.<br><br>Enum values: <ul><li>`WELCOME_MESSAGE`: welcome message. A welcome message is a message that is automatically sent to a user when they start a direct message conversation with you.</li><li>`SUGGESTED_QUESTION`: suggested question. A suggested question is a frequently asked question displayed at the beginning of a chat. When the user selects a suggested question, the user will automatically receive the corresponding preset answer.</li><li>`CHAT_PROMPT`: chat prompt. A chat prompt is an interactive button displayed above the input box. When users tap a chat prompt, the system will automatically generate a corresponding question for users to start the conversation.</li></ul> |
##| audit_status | string | The review status of the automatic message.<br><br>Enum values: <ul><li>`REVIEWING`: The automatic message is pending review.</li><li>`APPROVED`: The automatic message has been approved.</li><li>`REJECTED`: The automatic message has been rejected.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>If the `audit_status` of the automatic message is `REJECTED`, you cannot update the automatic message by using [/business/message/auto_message/status/update/](https://business-api.tiktok.com/portal/docs?id=1832184596566017).</li><li>If `audit_status` of the message is `REVIEWING`, you need to wait for some time before rechecking the review status. The exact waiting time may vary depending on the content and other factors, but normally ranges from a few seconds to 2-3 hours.</li></ul> |
##| welcome_message | object | Returned only when `auto_message_type` is `WELCOME_MESSAGE`.<br><br>Information about the welcome message. |
###| content | string | The text of the welcome message.<br><br><p><span style="color:darkred"><b>Note</b></span>: If you enable the welcome message for a Business Account for the first time, the default welcome message will be: `"Hi there,\nThanks for contacting &#123;&#123;display_name&#125;&#125;! Feel free to ask a question here or leave us a message and we'll get back to you shortly.\n\nThank you!"` |
##| suggested_question | object | Returned only when `auto_message_type` is `SUGGESTED_QUESTION`.<br><br>Information about the suggested question. |
###| question | string | A frequently asked question to help users start a conversation. |
###| answer | string | The preset answer that will be automatically sent when a user selects the question. |
##| chat_prompt | object | Returned only when `auto_message_type` is `CHAT_PROMPT`.<br><br>Information about the chat prompt. |
###| title | string | The name of the prompt button that is displayed above the input box. |
###| content | string | The corresponding question for the prompt that the system automatically generates for users to start the conversation when users tap the chat prompt. |
```

  
### Example
#### Retrieve the welcome message for a Business Account
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "operation_status": "ENABLE",
        "auto_messages": [
            {
                "audit_status": "APPROVED",
                "welcome_message": {
                    "content": "{{content}}"
                },
                "auto_message_type": "WELCOME_MESSAGE",
                "auto_message_id": "{{auto_message_id}}"
            }
        ],
        "business_id": "{{business_id}}"
    }
}
(/code)
```
#### Retrieve the suggested questions for a Business Account
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "auto_messages": [
            {
                "auto_message_type": "SUGGESTED_QUESTION",
                "audit_status": "APPROVED",
                "auto_message_id": "{{auto_message_id}}",
                "suggested_question": {
                    "question": "{{question}}",
                    "answer": "{{answer}}"
                }
            },
            {
                "auto_message_type": "SUGGESTED_QUESTION",
                "audit_status": "APPROVED",
                "auto_message_id": "{{auto_message_id}}",
                "suggested_question": {
                    "question": "{{question}}",
                    "answer": "{{answer}}"
                }
            },
            {
                "auto_message_type": "SUGGESTED_QUESTION",
                "audit_status": "APPROVED",
                "auto_message_id": "{{auto_message_id}}",
                "suggested_question": {
                    "question": "{{question}}",
                    "answer": "{{answer}}"
                }
            }
        ],
        "business_id": "{{business_id}}",
        "operation_status": "ENABLE"
    }
}
(/code)
```
#### Retrieve the chat prompts for a Business Account
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "auto_messages": [
            {
                "auto_message_type": "CHAT_PROMPT",
                "audit_status": "APPROVED",
                "auto_message_id": "{{auto_message_id}}",
                "suggested_question": {
                    "title": "{{title}}",
                    "content": "{{content}}"
                }
            },
            {
                "auto_message_type": "CHAT_PROMPT",
                "audit_status": "APPROVED",
                "auto_message_id": "{{auto_message_id}}",
                "suggested_question": {
                    "title": "{{title}}",
                    "content": "{{content}}"
                }
            }
        ],
        "business_id": "{{business_id}}",
        "operation_status": "ENABLE"
    }
}
(/code)
```
