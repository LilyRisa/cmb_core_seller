---
title: "Get welcome messages within an ad account"
doc_id: 1822106498804738
path: "API Reference / Welcome Messages / Get welcome messages within an ad account"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get welcome messages within an ad account

Use this endpoint to retrieve the welcome messages within the welcome message library of your ad account.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/creative/auto_message/get/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1738373164380162).|
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| advertiser_id {Required} | string | Advertiser ID. |
| auto_message_type {Required} | string | The type of automatic message to greet people with when they start a conversation with you.<br><br>Enum value: <ul><li>`WELCOME_MESSAGE`: welcome message. A welcome message is a message that is automatically sent to welcome people to the conversation after they tap on your ad.</li></ul>|
| auto_message_id | string | The ID of the automatic message. <br><br>When `auto_message_type` is `WELCOME_MESSAGE`, you can specify the ID of a welcome message via this field to filter the results. <br><br> If this field is not passed, all messages that belong to the specified automatic message type will be returned. |
| page | number | Current page number.<br><br> Value range: ≥ 1.<br>Default value: 1. |
| page_size| number | Page size.<br><br> Value range: 1-100.<br>Default value: 10. |
```

### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/creative/auto_message/get/?advertiser_id={{advertiser_id}}&auto_message_type=WELCOME_MESSAGE&page=1&page_size=100' \
--header 'Access-Token: {{Access-Token}}'
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
#| list | object[] | The list of automatic messages within the ad account. |
##| auto_message_id | string | The ID of the automatic message. |
##| auto_message_type | string |The type of automatic message to greet people with when they start a conversation with you.<br><br>Enum value: <ul><li>`WELCOME_MESSAGE`: welcome message. A welcome message is a message that is automatically sent to welcome people to the conversation after they tap on your ad.</li></ul>|
##| welcome_message | string | Information about the welcome message. |
###| title | string | The name of the welcome message.<br><br>You can use the name to distinguish between different welcome messages within the welcome message library of your ad account. |
###| content | string | The greeting within the welcome message. <br><br> Example: `Hi, thanks for contacting us. How can we help you?` |
###| suggested_questions | object[] | A list of suggested questions for customers to tap.<br>A suggested question is a frequently asked question displayed at the beginning of a chat. When the user selects a suggested question, the user will automatically receive the corresponding preset answer.Suggested questions. |
####| question | string | A frequently asked question to help users start a conversation. |
####| answer | string | The preset answer that will be automatically sent when a user selects the question. |
##| audit_status| string | The review status of the automatic message. <br><br>Enum values: <ul><li>`AUDITING`: The automatic message is pending review.</li><li>`PASS`: The automatic message has been approved.</li><li>`REJECTED`: The automatic message has been rejected. </li></ul>|
##| create_time | string | The time when the automatic message was created, in the format of `YYYY-MM-DD HH:MM:SS` (UTC+0). <br><br> Example: `2025-01-01 00:00:01`. |
#| page_info | object | Pagination information.<br><br><p><span style="color:darkred"><b>Note</b></span>: If `auto_message_id` is specified in the request, `page_info` will not be returned. |
##| page | number | Current page number. |
##| page_size | number | Page size. |
##| total_number | number | Total number of results. |
##| total_page | number | Total pages of results. |
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
        "auto_message_list": [
            {
                "audit_status": "PASS",
                "auto_message_id": "{{auto_message_id}}",
                "auto_message_type": "WELCOME_MESSAGE",
                "create_time": "{{create_time}}",
                "welcome_message": {
                    "content": "{{content}}",
                    "suggested_questions": [
                        {
                            "answer": "{{answer}}",
                            "question": "{{question}}"
                        },
                        {
                            "answer": "{{answer}}",
                            "question": "{{question}}"
                        },
                        {
                            "answer": "{{answer}}",
                            "question": "{{question}}"
                        }
                    ],
                    "title": "{{title}}"
                }
            }
        ]
    }
}
(/code)
```
