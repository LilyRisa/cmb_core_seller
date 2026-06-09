---
title: "Get the Comment-to-Message setting of a Business Account"
doc_id: 1843697576682497
path: "API Reference / Business Messaging / Direct messages / Get the Comment-to-Message setting of a Business Account"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the Comment-to-Message setting of a Business Account

Use this endpoint to get the status of the Comment-to-Message feature for a Business Account.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/business/message/direct_reply/get/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Access token authorized by TikTok Business Account owner. <br>To obtain an access token, use [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1738084387220481#item-link-Get%20a%20TikTok%20account%20access%20token).|
```

**Parameters**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|business_id {Required}|string|Application specific unique identifier for the TikTok Business Account. <br>Pass the value of the `open_id` field returned in the response of [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1738084387220481#item-link-Get%20a%20TikTok%20account%20access%20token) to this field.|
|direct_reply_type {Required}|string|The type of direct reply. <br><br>Enum value:<ul><li> `COMMENT_TO_MESSAGE`: Comment-to-Message. Once you enable this feature, the TikTok system will identify high-intent comments, enabling you to send customized replies to assist these users more effectively.</li></ul>|
```

### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/business/message/direct_reply/get/?business_id={{business_id}}&direct_reply_type=COMMENT_TO_MESSAGE' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

## Response

```xtable
|Field{30%}|Type{15%}|Description{55%}|
|---|---|---|
|code|number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message|string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id|string|The log ID of a request, which uniquely identifies the request.|
|data|object|Returned data.|
#|business_id|string|Application specific unique identifier for the TikTok Business Account.|
#|direct_reply_type|string|The type of direct reply. <br><br>Enum value:<ul><li> `COMMENT_TO_MESSAGE`: Comment-to-Message. Once you enable this feature, the TikTok system will identify high-intent comments, enabling you to send customized replies to assist these users more effectively.</li></ul>|
#|operation_status|string|The status of the direct reply type (`direct_reply_type`). <br><br>Enum values:<ul><li>  `ENABLE`: Enabled.</li><li>`DISABLE`: Disabled.</li></ul><br>If you want to enable or disable this setting, use [/business/message/direct_reply/update/](https://business-api.tiktok.com/portal/docs?id=1843697551083522). |
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
        "business_id": "{{business_id}}",
        "direct_reply_type": "COMMENT_TO_MESSAGE",
        "operation_status": "ENABLE"
    }
}
(/code)
```
