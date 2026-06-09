---
title: "Get the message event sets for ad creation"
doc_id: 1816979158055937
path: "API Reference / Events 1.0 / CTM Events / Get the message event sets for ad creation"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the message event sets for ad creation

Use this endpoint to obtain the message event sets that can be used to create [TikTok Instant Messaging Ads](https://business-api.tiktok.com/portal/docs?id=1774482999036930).

## Before you start
Ensure that you have message event sets within your ad account. If you don't have message event sets within your ad account, create message event sets [on TikTok Events Manager](https://ads.tiktok.com/help/article/how-to-create-message-events-on-tiktok-events-manager?lang=en).


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/ctm/message_event_set/get/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| advertiser_id {Required} | string | Advertiser ID. |
| messaging_app_type {Required} | string | The type of instant messaging app.<br><br>Enum values: <ul><li>`MESSENGER`: Messenger.</li><li>`WHATSAPP`: WhatsApp.</li><li>`ZALO`: Zalo.</li></ul>|
| messaging_app_account_id {Required} | string | The ID of the instant messaging app account.<br><br> <ul><li>If `messaging_app_type` is `MESSENGER`, specify a Facebook Page ID. A valid Facebook Page ID contains numbers only. <ul><li>To find your Facebook Page ID, go to your Facebook Page and click <b>About</b> below your cover photo, then click <b>Page transparency</b>.</li></ul></li><li>If `messaging_app_type` is `WHATSAPP`, specify your WhatsApp phone number following the format: region calling code followed by phone number without any special symbols. For instance, if the region calling code is +1, and the phone number is (555) 555-1234, set this field to `15555551234`.</li><li>If `messaging_app_type` is `ZALO`, specify the Zalo Official Account ID.</li></ul>|
| message_event_name | string | The name of the message event set to filter the results by. <br><br>Exact match is supported. |
| message_event_set_ids | string[] | The ID list of the message event sets to filter the results by.<br><br>Max size: 10. |
```

### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/ctm/message_event_set/get/?advertiser_id={{advertiser_id}}&messaging_app_type=WHATSAPP&messaging_app_account_id={{essaging_app_account_id}}' \
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
#| message_event_set_list | object[] | The list of existing message event sets within the specified ad account. |
##| message_event_set_id | string | The ID of the message event set. |
##| message_event_name | string | The name of the message event set. |
##| create_time | string | The time when the message event set was created, in the format of `YYYY-MM-DD HH:MM:SS`. <br><br>Example: `2024-01-01 00:00:01`. |
#| matched_event_set | object | The information about the only message event set that is associated with the instant messaging app account (`messaging_app_account_id`). |
##| message_event_set_id | string | The ID of the message event set. |
##| message_event_name | string | The name of the message event set. |
##| create_time | string | The time when the message event set was created, in the format of `YYYY-MM-DD HH:MM:SS`. <br><br>Example: `2024-01-01 00:00:01`. |
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
        "message_event_set_list": [
            {
                "message_event_set_id": "{{message_event_set_id}}",
                "create_time": "2024-04-18 14:25:28",
                "message_event_name": "{{message_event_name}}"
            },
            {
                "message_event_set_id": "{{message_event_set_id}}",
                "create_time": "2024-10-25 09:42:04",
                "message_event_name": "{{message_event_name}}"
            }
        ],
        "matched_event_set": {
            "message_event_set_id": "{{message_event_set_id}}",
            "create_time": "2024-10-25 09:42:04",
            "message_event_name": "{{message_event_name}}"
        }
    }
}
(/code)
```
