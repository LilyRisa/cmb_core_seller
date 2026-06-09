---
title: "Delete creative assets"
doc_id: 1797202997456897
path: "API Reference / Creative Tools / Delete creative assets"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Delete creative assets

Use this endpoint to delete your creative assets.

## Request 
**Endpoint** https://business-api.tiktok.com/open_api/v1.3/creative/asset/delete/

**Method** POST

**Header**
```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
| Content-Type {Required} | string | The content type of the request. <br>Allowed value: `"application/json"`.|
```
**Parameters**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id{Required}|string|Advertiser ID.|
|video_ids|string[]|A list of video IDs. At most 50 IDs can be included in the list.|
|image_ids|string[]|A list of image IDs. At most 50 IDs can be included in the list.|
```

### Examples
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/creative/asset/delete/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data-raw '{
    "advertiser_id": "{{advertiser_id}}",
    "video_ids": "{{video_ids}}",
    "image_ids": "{{image_ids}}"
}' 

(/code)
```

## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Return code, see [Appendix-Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message |string|Return message, see [Appendix-Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The log id of the request, which uniquely identifies a request. |
|data |object|Returned data.|
#|failed_video_ids|string[]|A list of unsuccessfully deleted video IDs.|
#|failed_image_ids|string[]|A list of unsuccessfully deleted image IDs.|
```

### Example

``` xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
  "message": "OK",
  "code": 0,
  "data": {
          “failed_video_ids”: [],
          “failed_image_ids”: []
        },
  "request_id": "{{request_id}}"
}
(/code)
```
