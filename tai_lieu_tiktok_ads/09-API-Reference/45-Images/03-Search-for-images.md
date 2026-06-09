---
title: "Search for images"
doc_id: 1740052016789506
path: "API Reference / Images / Search for images"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Search for images

Use this endpoint to search for image creatives in an advertising account's Asset Library.

Only the first 10000 images (ordered by `modify_time`) will be returned.

 ## Comparing v1.2 and v1.3
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/file/image/ad/search/|/v1.3/file/image/ad/search/|
|Request parameter data type |`advertiser_id`: number|`advertiser_id`: string|
|Response parameter deprecated in v1.3|/|`id`: string|
|New response parameters|/|`is_carousel_usable`|
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/file/image/ad/search/

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
|advertiser_id {Required}|string| Advertiser ID. |
|filtering |object|Filters on the data. This parameter is an array of filter objects.|
#|height |number|Image height.|
#|image_ids |string[]|Image IDs. At most 100 IDs can be included in the list.|
#|material_ids|string[]| A list of material IDs. At most 100 IDs can be included in the list.|
#|ratio |float[]|Image aspect ratio, e.g.: [1.7, 2.5]. Use 1.7 to search for Images with aspect ratio between 1.65-1.75, i.e. the precision floating point is 0.05.|
#|width |number|Image width. |
#|displayable |boolean|Enum values: <ul><li>`False`(default value): Search in all materials.</li><li>`True`: Search in the materials displayed on the platform.</li></ul>|
|page |number|Current page number. Default value: 1. Value range: ≥ 1.|
|page_size |number|Page size. Default value: 20. Value range: 1-100.|
```

### Example

```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/file/image/ad/search/?advertiser_id={{advertiser_id}}' \
--header 'Access-Token: {{Access-Token}}'
(/code)

```

## Response

```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Return code. For the complete list of response codes and descriptions, see [Appendix-Return code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|message |string|Return message. For details, see [Appendix-Return Information](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object|Returned data.|
#|list |object[]|A list of image information.  |
##|image_id |string|Image ID. It can be used to create ads. |
##|material_id|string|Material ID.|
##| is_carousel_usable | boolean | Whether the source of the image is valid for Carousel Ads. <br><p><span style="color:darkred"><b>Note</b></span>: To use an image in Carousel Ads, you need to ensure:  <ul><li> The value of this field is `true`, <strong>and </strong></li><li> The image meets the specifications requirements listed in [Create Carousel Ads-Steps-Create an ad](https://ads.tiktok.com/marketing_api/docs?id=1766217791987713).</li></ul>  |
##|width |number|Image width.|
##|format |string|Image format.|
##|image_url |string|Picture preview link, valid for an hour and needs to be re-acquired after expiration. |
##|height |number|Image height.|
##|signature |string|MD5 of the image. |
##|size |number|Image size.|
##|file_name |string |Image name.|
##|create_time |string |Creation time. UTC time. Format: 2020-06-10T07:39:14Z. |
##|modify_time |string |Modification time. UTC time. Format: 2020-06-10T07:39:14Z. |
##|displayable |boolean|Whether it can be displayed on the platform.|
#|page_info |object|Pagination information.|
##|page |number|Current page number.|
##|page_size |number|Page size.|
##|total_number |number|Total number of results.|
##|total_page |number|Total pages of results.|
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
        "page_info": {
            "page_size": 20,
            "total_page": 1,
            "total_number": 2,
            "page": 1
        },
        "list": [
            {
                "height": 720,
                "size": 118834,
                "image_url": "{{image_url}}",
                "modify_time": "{{modify_time}}",
                "create_time": "{{create_time}}",
                "image_id": "{{image_id}}",
                "file_name": "{{file_name}}",
                "width": 1280,
                "signature": "{{signature}}",
                "displayable": false,
                "material_id": "{{material_id}}",
                "format": "jpeg",
                "is_carousel_usable": false
            },
            {
                "height": 720,
                "size": 159992,
                "image_url": "{{image_url}}",
                "modify_time": "{{modify_time}}",
                "create_time": "{{create_time}}",
                "image_id": "{{image_id}}",
                "file_name": "{{file_name}}",
                "width": 1280,
                "signature": "{{signature}}",
                "displayable": false,
                "material_id": "{{material_id}}",
                "format": "jpeg",
                "is_carousel_usable": false
            }
        ]
    }
}
(/code);
```
