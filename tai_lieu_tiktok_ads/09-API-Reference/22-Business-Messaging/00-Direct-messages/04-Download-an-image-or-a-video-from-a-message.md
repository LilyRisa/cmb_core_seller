---
title: "Download an image or a video from a message"
doc_id: 1832184455450626
path: "API Reference / Business Messaging / Direct messages / Download an image or a video from a message"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Download an image or a video from a message

Use this endpoint to download an image or a video from a message. 


## Before you start
Because of the sensitive nature of direct message data, TikTok has implemented a higher level of due diligence for developers that will process direct messages on behalf of Business Account users. Ensure that you [have completed the necessary data security and privacy reviews and obtained access to Business Messaging API](https://business-api.tiktok.com/portal/docs?id=1832184145137922) before calling this endpoint.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/business/message/media/download/

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
|conversation_id{Required}|string|Conversation ID.<br><br>To get the conversation ID, use  [/business/message/conversation/list/](https://business-api.tiktok.com/portal/docs?id=1832184415059970).|
|message_id{Required}|string|Message ID.<br><br>To get a message ID, use  [/business/message/content/list/](https://business-api.tiktok.com/portal/docs?id=1832184425841170).|
|media_id{Required}|string|Media ID.<br><br>To get a media ID, use [/business/message/content/list/](https://business-api.tiktok.com/portal/docs?id=1832184425841170).|
|media_type{Required}|string|Multimedia type.<br><br>Enum values:<ul><li>`IMAGE`: image.</li><li>`VIDEO`: video.</li></ul>|
```


### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/business/message/media/download/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "business_id": "{{business_id}}",
    "conversation_id": "{{conversation_id}}",
    "message_id": "{{message_id}}",
    "media_id": "{{media_id}}",
    "media_type": "IMAGE"
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
#|download_url|string|Download URL.<br><br>It is valid for 24 hours. If it expires, call this endpoint again to generate a new URL.<br><br><p><span style="color:darkred"><b>Note</b></span>: When downloading the image through the URL, you must include a header. Set the header to `x-user = &#123;&#123;Access-Token&#125;&#125;`.|
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
        "download_url": "{{download_url}}"
    }
}
(/code)
```
