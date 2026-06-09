---
title: "Create a welcome message within an ad account"
doc_id: 1822106113771521
path: "API Reference / Welcome Messages / Create a welcome message within an ad account"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create a welcome message within an ad account

Use this endpoint to create a welcome message in your ad account.

Each welcome message will be saved in the welcome message library of the ad account for future use in [TikTok Direct Messaging Ads](https://business-api.tiktok.com/portal/docs?id=1774482992153601).


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/creative/auto_message/create/

**Method** POST

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type.<br>Allowed format: `application/json`.  |
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| advertiser_id {Required} | string | Advertiser ID. |
| auto_message_type {Required} | string | The type of automatic message to greet people with when they start a conversation with you.<br><br>Enum value: <ul><li>`WELCOME_MESSAGE`: welcome message. A welcome message is a message that is automatically sent to welcome people to the conversation after they tap on your ad.</li></ul>|
| welcome_message {+Conditional} | object | Required when `auto_message_type` is `WELCOME_MESSAGE`. <br><br> Information about the welcome message. |
#| title {+Conditional} | string | Required when `welcome_message` is passed.<br><br> The name of the welcome message. <br> You can specify a unique name to distinguish between different welcome messages within the welcome message library of your ad account. |
#| content {+Conditional} | string | Required when `welcome_message` is passed. <br><br> The greeting within the welcome message. <br><br> Length limit: 200 characters. <br><br> Example: `Hi, thanks for contacting us. How can we help you?` |
#| suggested_questions {+Conditional} | object[] | Required when `welcome_message` is passed. <br><br>A list of suggested questions for customers to tap. <br>A suggested question is a frequently asked question displayed at the beginning of a chat. When the user selects a suggested question, the user will automatically receive the corresponding preset answer.<br><br> Size range: 1-3. |
##| question {+Conditional} | string | Required when `welcome_message` is passed.<br><br> A frequently asked question to help users start a conversation. <br><br> Length limit: 70 characters. |
##| answer {+Conditional} | string | Required when `welcome_message` is passed.<br><br>The preset answer that will be automatically sent when a user selects the question. <br><br>Length limit: 500 characters. |
```

### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/creative/auto_message/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id":"{{advertiser_id}}",
    "auto_message_type":"WELCOME_MESSAGE", 
    "welcome_message": {
        "title": "{{title}}",
        "content": "{{content}}",
        "suggested_questions": [
            {
                "question": "{{question}}",
                "answer": "{{answer}}"
                
            },
            {
                "question": "{{question}}",
                "answer": "{{answer}}"
                
            },
            {
                "question": "{{question}}",
                "answer": "{{answer}}"
            }
        ]             
    }
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
# |auto_message_id|string| The ID of the automatic message.|
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
        "auto_message_id": "{{auto_message_id}}"
    }
}
(/code)
```
