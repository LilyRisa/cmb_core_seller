---
title: "Get suggested thumbnails for a video"
doc_id: 1740051189071873
path: "API Reference / Video / Get suggested thumbnails for a video"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get suggested thumbnails for a video

Use this endpoint to get a list of suggested video thumbnails based on a video creative. You can preview the thumbnails using the URLs in the response, and then you can set one of the images as the thumbnail for your video ad.

> <span style="color:DodgerBlue">**Note**</span><br>
> If you want to get video thumbnail suggestions for a recently uploaded video, we recommend waiting for 30 seconds to five minutes before calling this endpoint. This allows the video to be transcoded and processed. Calling the endpoint before the transcoding process is complete may result in an `Insufficient permissions for some videos` error.


 ## Comparing v1.2 and v1.3
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/file/video/suggestcover/|/v1.3/file/video/suggestcover/|
|Request parameter data type |`advertiser_id`: number|`advertiser_id`: string|
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/file/video/suggestcover/

**Method** GET

**Header**

```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

```xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id {Required}|string| Advertiser ID.|
|video_id {Required}|string|Video ID. |
|poster_number |number|Number of cover candidates you want to get. Range: `1-10`. Default value: `10` .</br>If the total number of  cover candidates is less than the number given, all candidates will be returned. |
```

### Example

```xcodeblock
(code curl http)
curl --get -H "Access-Token:xxx" \
--data-urlencode "advertiser_id={advertiser_id}" \
--data-urlencode "video_id={video_id}" \
https://business-api.tiktok.com/open_api/v1.3/file/video/suggestcover/
(/code)
```

## Response

```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Return code, see [Appendix-Return code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|message |string|Return Message, see [Appendix-Return Information](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|data |object|Returned data.|
#|list |object[]|A list of image information. |
##|width |number|Image width.|
##|height |number|Image height.|
##|id |string|Image ID.|
##|url |string|Picture preview address, valid for an hour and needs to be re-acquired after expiration.|
|request_id |string|The log id of a request, which uniquely identifies the request.|
```

### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {
        "list": [
          {
            "url": "http://p1.ipstatp.com/large/tos-useast2a-p-0000/8cc546a4b96a4933a27b0c7fefdd66a9_1584964597",
            "width": 560,
            "id": "tos-useast2a-p-0000/8cc546a4b96a4938a27b0c7fefdd66a9_1584964597",
            "height": 320
          },
          {
            "url": "http://p1.ipstatp.com/large/tos-useast2a-p-0000/36973f6fd2344c9fa5889db57d743ac6_1584964597",
            "width": 560,
            "id": "tos-useast2a-p-0000/36973f6fd2344c90a5889db57d743ac6_1584964597",
            "height": 320
          }
        ]
    },
    "request_id": "2020032312235001018904922349195A66"
}
(/code);
```
