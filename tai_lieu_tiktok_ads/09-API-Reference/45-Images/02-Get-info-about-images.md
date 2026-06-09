---
title: "Get info about images"
doc_id: 1740051721711618
path: "API Reference / Images / Get info about images"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get info about images

Use this endpoint to obtain the information of  images from the Asset Library.

## Comparing v1.2 and v1.3
The following table outlines the differences between v1.2 and v1.3 endpoints. 
```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/file/image/ad/info/|/v1.3/file/image/ad/info/|
|Request parameter data type |`advertiser_id`: number|`advertiser_id`: string|
|New response parameters| / | `is_carousel_usable`|
|Response parameter deprecated in v1.3| / | `id`: string |
```


## Request

**Endpoint**
https://business-api.tiktok.com/open_api/v1.3/file/image/ad/info/

**Method**  GET

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
|image_ids {Required}|string[]|Image ID list. Up to 100 IDs per request. <br><p><span style="color:darkred"><b>Note</b></span>:  If you obtain the image IDs of thumbnails for ads created on TikTok Ads Manager using the [/ad/get/](https://ads.tiktok.com/marketing_api/docs?id=1735735588640770) endpoint, they are not supported. This is because these thumbnails have not been uploaded to the Asset Library and linked to your ad account.|
```

### Example

```xcodeblock
(code curl http)
curl --location  --request GET 'https://business-api.tiktok.com/open_api/v1.3/file/image/ad/info/?advertiser_id={{advertiser_id}}&image_ids=["{{image_id}}"]' \
--header 'Access-Token: {{Access-Token}}'
(/code)

```

## Response

```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Return code. For the complete list of response codes and descriptions, see [Appendix-Return code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|message |string|Return message. For details, see [Appendix-Return Information](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|data |object|Returned Data. |
#|list |object[]| Information about the images. |
##|image_id |string|Image ID, used to create ads. |
##|material_id|string|Material ID.|
##| is_carousel_usable | boolean | Whether the source of the image is valid for Carousel Ads. <br><p><span style="color:darkred"><b>Note</b></span>: To use an image in Carousel Ads, you need to ensure:  <ul><li> The value of this field is `true`, <strong>and </strong></li><li> The image meets the specifications requirements listed in [Create Carousel Ads-Steps-Create an ad](https://ads.tiktok.com/marketing_api/docs?id=1766217791987713).</li></ul>  |
##|width |number|Image width.  |
##|format|string|Image format.  |
##|image_url |string|Image URL, valid for an hour and needs to be re-acquired after expiration. |
##|height |number|Image height.  |
##|signature |string|MD5 of picture.|
##|size |number|Image size in bytes.|
##|file_name |string |Image name.|
##|create_time |string |Creation time. UTC time. Format: 2020-06-10T07:39:14Z. |
##|modify_time |string |Modification time. UTC time. Format: 2020-06-10T07:39:14Z. |
##|displayable |boolean |Whether it can be displayed on the platform.|
|request_id |string|The log id of a request, which uniquely identifies the request. |
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
        "list": [
            {
                "is_carousel_usable": true,
                "size": 39779,
                "format": "jpeg",
                "modify_time": "{{modify_time}}",
                "create_time": "{{create_time}}",
                "displayable": false,
                "width": 750,
                "material_id": "{{material_id}}",
                "image_url": "{{image_url}}",
                "file_name": "{{file_name}}",
                "image_id": "{{image_id}}",
                "signature": "{{signature}}",
                "height": 422
            }
        ]
    }
}
(/code);
```

