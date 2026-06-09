---
title: "Upload an image"
doc_id: 1739067433456642
path: "API Reference / Images / Upload an image"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Upload an image

Use this endpoint to upload pictures to the Asset Library and use the obtained image ID for creating ads. 

Request timeout for this endpoint is 10s, and the transmission speed depends on network bandwidth. Please make sure that the file size is reasonable.

To learn about the steps of creating single image ads, refer to [Create single image ads](https://business-api.tiktok.com/portal/docs?id=1777633230937090).

To learn about the steps of creating Carousel Ads, refer to [Create Carousel Ads](https://business-api.tiktok.com/portal/docs?id=1766217791987713).


## Comparing v1.2 and v1.3
The following table outlines the differences between v1.2 and v1.3 endpoints. 
```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/file/image/ad/upload/|/v1.3/file/image/ad/upload/|
|Request parameter data type |`advertiser_id`: number|`advertiser_id`: string|
|New response parameters| / | `is_carousel_usable`|
|Response parameter deprecated in v1.3| / | `id`: string |
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/file/image/ad/upload/

**Method** POST

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type.<br>Use `multipart/form-data` when upload_type is `UPLOAD_BY_FILE`. <br>Use `application/json` when upload_type is `UPLOAD_BY_URL` or `UPLOAD_BY_FILE_ID`.|
```

**Parameters**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|advertiser_id {Required}|string| Advertiser ID.|
|file_name |string |Image name. <br><br>Length limit: 1-100 characters.<br><br> The default value is the file name. If the final filename has more than 100 chars, it will be cut off. <br><br><p><span style="color:darkred"><b>Important</b></span>: Images under the same `advertiser_id` cannot have duplicated file names. You can call [/file/name/check/](https://ads.tiktok.com/marketing_api/docs?id=1759130033155073) to check whether the file name has been used.<br>If you get an error about duplicated file names, please rename the files or append timestamps to the original file names ( for example, in the format of `<original_filename>_<timestamp>`) , and upload the images again.|
|upload_type{Required} |string|Image upload method. <br><br>Default value: `UPLOAD_BY_FILE`.<br><br>Enum values: `UPLOAD_BY_FILE`, `UPLOAD_BY_URL`, `UPLOAD_BY_FILE_ID`.|
|image_file {+Conditional}|file| Required when `upload_type` is `UPLOAD_BY_FILE`.<br><br>Image file.<br><br>Recommended settings: <ul><li>File size: within 50 MB.</li><li>Resolution: The total pixel count of the image, which is obtained by multiplying the width and height, should not exceed 3,686,400 pixels (1440 x 2560). For example, a resolution of 2400 x 1256 pixels is within the supported range, while a resolution of 3600 x 1256 pixels will result in an error.<br>To learn about the recommended image resolution for a certain use case, refer to the section "<a href="#item-link-Image resolution guidelines and use cases">Image resolution guidelines and use cases</a>".</li><li>File type: JPG, JPEG, or PNG</li></ul>|
|image_signature {+Conditional}|string| Required when `upload_type` is `UPLOAD_BY_FILE`.  <br> <br>MD5 of the image (used for server verification).|
|image_url {+Conditional}|url|Required when `upload_type` is `UPLOAD_BY_URL`. <br> <br>Image url address. Example: http://xxx.xxx. |
|file_id{+Conditional}| string | Required when `upload_type` is `UPLOAD_BY_FILE_ID`. <br> <br>The `file_id` of the image that you want to upload. <br>This field is for files that are uploaded to the file repository. <br><br>You can get `file_id` via the [Upload Files](https://ads.tiktok.com/marketing_api/docs?id=1737719988918274) endpoints. |
```

### Image resolution guidelines and use cases
The following table outlines the recommended resolutions for a certain use case. Ensure that the image file you upload has the recommended resolution for the specific use case to avoid any issues with image condensation or expansion (with potential loss of clarity) when delivered in ads.

```xtable
| Use case {40%}| Recommended resolution{60%} |
|---|---|
| Pangle single image ads |<ul><li>720 pixels (width) x 1280 pixels (height) </li><li>1200 pixels (width) x 628 pixels (height)</li><li>640 pixels (width) x 640 pixels (height)</li><li>640 pixels (width) x 100 pixels (height)</li><li> 600 pixels (width) x 500 pixels (height) </li><li>640 pixels (width) x 200 pixels (height)|
| Global App Bundle single image ads | <ul><li>720 pixels (width) x 1280 pixels (height) </li><li>1200 pixels (width) x 628 pixels (height)</li><li>640 pixels (width) x 640 pixels (height)</li></ul>|
| Video cover (thumbnail) in TikTok single video ads | <ul><li>720 pixels (width) x 1280 pixels (height)</li><li>1280 pixels (width) x 720 pixels (height)</li><li>640 pixels (width) x 640 pixels (height)</li></ul> <br><p><span style="color:darkred"><b>Note</b></span>: The video cover resolution must match the video resolution and meet these requirements:<ul><li>Supported aspect ratios: 9:16, 16:9, or 1:1 </li><li>Resolution ranges per aspect ratio:<ul><li>9:16 aspect ratio: 960-3840 pixels (width) × 540-2160 pixels (height)</li><li>16:9 aspect ratio: 960-3840 pixels (width) × 540-2160 pixels (height) </li><li>1:1 aspect ratio: 640-1280 pixels (width) × 640-1280 pixels (height) </li></ul></li></ul>|
```

### Example

```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/file/image/ad/upload/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: multipart/form-data' \
--form 'advertiser_id="{{advertiser_id}}"' \
--form 'file_name="{{file_name}}"' \
--form 'image_file=@"{{image_file}}"' \
--form 'image_signature="{{image_signature}}"'
(/code)

```


## Response

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|code |number|Return code. For the complete list of response codes and descriptions, see [Appendix-Return code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|message |string|Return Message. For details, see [Appendix-Return Information](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object|Returned data. |
#|image_id |string|Image ID that is used to create ads. |
#|material_id|string|Material ID.|
#| is_carousel_usable | boolean | Whether the source of the image is valid for Carousel Ads. <br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>Due to the latency in writing the image data, this field might not be returned. In such situations, we recommend waiting for 10 seconds and then using the `image_id` returned from this endpoint with either [/file/image/ad/info/](https://business-api.tiktok.com/portal/docs?id=1740051721711618) or [/file/image/ad/search/](https://business-api.tiktok.com/portal/docs?id=1740052016789506) to retrieve the data.</li><li>To use an image in Carousel Ads, you need to ensure the value of this field is `true`, and the image meets the specifications requirements listed in [Create Carousel Ads-Steps-Create an ad](https://ads.tiktok.com/marketing_api/docs?id=1766217791987713).</li></ul>  |
#|displayable |bool |Whether it can be displayed on the platform.|
#|height |number|Image height.  |
#|width |number|Image width.  |
#|format|string|Image Format.  |
#|image_url |string|Image URL, valid for an hour and needs to be re-acquired after expiration. |
#|signature |string|MD5 of picture.|
#|size |number|Image size in bytes.|
#|file_name |string |Image name.|
#|create_time |string |Creation time. UTC time. Format: 2020-06-10T07:39:14Z. <br><br><p><span style="color:darkred"><b>Note</b></span>: Due to the latency in writing the image data, this field might not be returned. In such situations, we recommend waiting for 10 seconds and then using the `image_id` returned from this endpoint with either [/file/image/ad/info/](https://business-api.tiktok.com/portal/docs?id=1740051721711618) or [/file/image/ad/search/](https://business-api.tiktok.com/portal/docs?id=1740052016789506) to retrieve the data.|
#|modify_time |string |Modification time. UTC time. Format: 2020-06-10T07:39:14Z. <br><br><p><span style="color:darkred"><b>Note</b></span>: Due to the latency in writing the image data, this field might not be returned. In such situations, we recommend waiting for 10 seconds and then using the `image_id` returned from this endpoint with either [/file/image/ad/info/](https://business-api.tiktok.com/portal/docs?id=1740051721711618) or [/file/image/ad/search/](https://business-api.tiktok.com/portal/docs?id=1740052016789506) to retrieve the data.|
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
        "displayable": false,
        "width": 320,
        "signature": "{{signature}},
        "height": 640,
        "create_time": "{{create_time}}",
        "file_name": "{{file_name}}",
        "image_id": "{{image_id}}",
        "format": "jpeg",
        "modify_time": "{{modify_time}}",
        "image_url": "{{image_url}}",
        "is_carousel_usable": true,
        "material_id": "{{material_id}}",
        "size": 73398
    }
}
(/code);
```
