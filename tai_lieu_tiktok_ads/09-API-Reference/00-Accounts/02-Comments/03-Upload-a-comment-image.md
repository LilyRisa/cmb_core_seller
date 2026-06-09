---
title: "Upload a comment image"
doc_id: 1856212334897154
path: "API Reference / Accounts / Comments / Upload a comment image"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Upload a comment image

Use this endpoint to upload an image for a new comment or for a reply to an existing comment and receive an `image_uri` that you can include when calling the [/business/comment/create/](https://business-api.tiktok.com/portal/docs?id=1762228442072066) or [/business/comment/reply/create/](https://business-api.tiktok.com/portal/docs?id=1762228448779266) endpoint.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/business/comment/image/upload/

**Method** POST

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token|
|Content-Type {Required}|string|Request message type.<br><br>Allowed format: `multipart/form-data`.|
```

**Parameters**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|image_file {Required}|file|The image file which will be used to generate a new comment or to add reply to an existing comment thread.<br><br>Maximum size: 5 MB.<br><br>Maximum resolution: 1080 x 1920 pixels or 1920 x 1080 pixels.<br><br>Minimum resolution: 360 x 360 pixels.<br><br>File types supported: JPG, JPEG, WebP, or PNG.|
```

### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/business/comment/image/upload/' \
--header 'Access-Token: {{Access-Token}}' \
--form 'business_id="{{business_id}}"' \
--form 'image_file=@"{{image_file}}"'
(/code)
```

## Response

```xtable
|Field{30%}|Type{15%}|Description{55%}|
|---|---|---|
|request_id|string|Unique identifier for the API request.<br><br><p><span style="color:darkred"><b>Note</b></span>: Please record this field for all API requests. Important for issue reports and troubleshooting.</p>|
|code|integer|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://business-api.tiktok.com/portal/docs?id=1737172488964097).|
|message|string|Response message. For details, see [Appendix - Return Information](https://business-api.tiktok.com/portal/docs?id=1737172488964097).|
|data|object|Returned data.|
#|image_uri|string|URI for the uploaded image.<br><br>Returned when an accepted image type is uploaded.|
#|width|integer|Width of the `image_file`.<br><br>**Example**: 720.|
#|height|integer|Height of the `image_file`.<br><br>**Example**: 900.|
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
        "height": 402,
        "image_uri": "{{image_uri}}",
        "width": 428
    }
}
(/code)
```
