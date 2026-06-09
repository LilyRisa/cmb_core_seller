---
title: "Sort the automatic message for a Business Account"
doc_id: 1832184641212433
path: "API Reference / Business Messaging / Automatic messages / Sort the automatic message for a Business Account"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Sort the automatic message for a Business Account

Use this endpoint to sort the chat prompts for a Business Account.

## Before you start
Because of the sensitive nature of direct message data, TikTok has implemented a higher level of due diligence for developers that will process direct messages on behalf of Business Account users. Ensure that you [have completed the necessary data security and privacy reviews and obtained access to Business Messaging API](https://business-api.tiktok.com/portal/docs?id=1832184145137922) before calling this endpoint.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/business/message/auto_message/sort/

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
| auto_message_type{Required} | string | The type of automatic message.<br><br>Enum value: <ul><li>`CHAT_PROMPT`: chat prompt. A chat prompt is an interactive button displayed above the input box. When users tap a chat prompt, the system will automatically generate a corresponding question for users to start the conversation.</li></ul> |
| auto_message_ids{Required} | string[] | The IDs of the chat prompts in the desired order.<br><br>The first ID in the ID list will be the first prompt button that is displayed above the input box.<br><br>To retrieve the existing chat prompts of a Business Account, use [/business/message/auto_message/get/](https://business-api.tiktok.com/portal/docs?id=1832184619749377).|
```


### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/business/message/auto_message/sort/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "business_id": "{{business_id}}",
    "auto_message_type": "CHAT_PROMPT",
    "auto_message_ids": [
        "{{auto_message_id}}",
        "{{auto_message_id}}",
        "{{auto_message_id}}"
    ]
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
|data |object|Returned data.<br><br>For a successful request, this field will be an empty object (`{}`).|
```

### Example
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {}
}
(/code)
```
