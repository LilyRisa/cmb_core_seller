---
title: "Upload an image"
doc_id: 1832184437720193
path: "API Reference / Business Messaging / Direct messages / Upload an image"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Upload an image

Use this endpoint to upload an image. 

## Before you start
- Because of the sensitive nature of direct message data, TikTok has implemented a higher level of due diligence for developers that will process direct messages on behalf of Business Account users. Ensure that you [have completed the necessary data security and privacy reviews and obtained access to Business Messaging API](https://business-api.tiktok.com/portal/docs?id=1832184145137922) before calling this endpoint.
- You can use [/business/message/capabilities/get/](https://business-api.tiktok.com/portal/docs?id=1836061597865986) to query if the Business Account can send a media attachment to a conversation.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/business/message/media/upload/

**Method** POST

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|A short-term access token authorized by the TikTok Business Account owner. <br>For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1832184175482945).|
|Content-Type {Required}|string|Request message type.<br>Allowed format: `multipart/form-data`.  |
```


**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| business_id {Required}| string | Application specific unique identifier for the TikTok Business Account. <br><br> Pass the value of the `open_id` field returned in the response of [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1832184175482945#item-link-Obtain%20a%20short-term%20access%20token) to this field. |
|file {Required}|file|Multimedia File.<br><br>File size limit: 3MB.<br><br>Supported image file format: JPG and PNG.|
|media_type {Required}|string|Multimedia type.<br><br>Currently, we only support `IMAGE` as the multimedia type.|
```



### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/business/message/media/upload/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: multipart/form-data' \
--form 'business_id="{{business_id}}"' \
--form 'file=@"/Users/xxx/ss.png"' \
--form 'media_type="IMAGE"'
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
#|media_id|string|Media ID of the uploaded image.<br><br>Validity period: 30 days. Once the Media ID expires, you need to re-upload the image to obtain a new Media ID.|
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
        "media_id": "{{media_id}}"
    }
}
(/code)
```
