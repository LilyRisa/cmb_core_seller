---
title: "Edit an image"
doc_id: 1739067107903489
path: "API Reference / Creative Tools / Edit an image"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Edit an image

Use this endpoint to edit an image. The image editing endpoint can edit an image according to the size you want as well as apply creative trimmings. This endpoint only supports cropping/trimming of an image that has been uploaded to the Asset Library. The edited images will be automatically uploaded to the Asset Library and the new picture ID will be returned.

If you want to upload an image to the Asset Library, use the [/file/image/ad/upload/](https://ads.tiktok.com/marketing_api/docs?id=1739067433456642) endpoint.

## Image editing methods

1. `fix_size`: fixed length and width cropping, no trimming.
2. `only_gauss_pad`: only Gaussian blur trimming, no cropping.
3. `gauss_padding_reserve_score`: Gaussian blur catch-all, with which picture will be adjusted according to the size of main body in the original image.

![image_cut.png](https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/2c6dd4de1bf01d783a8a27af922ec8e9)

This text is an annotation and will not be displayed. From left to right are the effects of the original image, `fix_size`, `only_gauss_pad`, and `gauss_padding_reserve_score`, respectively.

For images, we recommend a resolution ≥1200 * 628px.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/creative/image/tailor/|/v1.3/creative/image/edit/|
|Request parameter name|`cut_method`|`edit_method`|
|Request parameter data type |`advertiser_id`: number |`advertiser_id`: string |
|Response parameter name|`url`|`image_url`|
|Response parameter deprecated in v1.3|/|`id`|
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/creative/image/edit/

**Method** POST

**Header**

```xtable
|Field|Data Type|Description|
|--- |--- |--- |
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

```xtable
| Field | Data Type | Description |
|---|---|---|
| advertiser_id {required} | string | Advertiser ID. |
| image_id {required} | string | Image ID. |
| edit_method | string | Editing method. Supported methods: `fix_size`, `only_gauss_pad`, and `gauss_padding_reserve_score`. Default value: `fix_size`. <br>See the <strong>"Image editing methods"</strong> section to learn about the differences between the editing methods.|
| width {required} | number | Width of edited image. |
| height {required} | number | Height of edited image. |
| image_name | string | Image name after editing. If the name is not set, this will be automatically generated. |
```
### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/creative/image/edit/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data-raw '{
  "advertiser_id": "{{advertiser_id}}",
  "image_id": "{{image_id}}",
  "edit_method": "fix_size",
  "width": 620,
  "height": 788
}'
(/code)
```

## Response

```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|data |object|Returned Data. |
#|image_id |string|Image ID that is used to create ads.|
#|material_id|string|Material ID.|
#|displayable |boolean |Whether it can be displayed on the platform.|
#|width |number|Image width.  |
#|format|string|Image format. |
#|image_url |string|Image URL, valid for an hour and needs to be re-acquired after <br>expiration. |
#|height |number|Image height. |
#|signature |string|MD5 of Picture.|
#|size |number|Image size, in Bytes.|
#|file_name |string |Image name.|
#|create_time |string |Creation time. UTC time, format: 2020-06-10T07:39:14Z |
#|modify_time |string |Modification time. UTC time, format: 2020-06-10T07:39:14Z |
|request_id |string|The log id of a request, which uniquely identifies the request. |
```

### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
  "message": "OK",
  "code": 0,
  "data": {
    "format": "jpeg",
    "image_url": "http://p1.ipstatp.com/large/ad-site-i18n/202006215d0de2e725f892ed4e31beb4",
    "file_name": "file_name_test2",
    "height": 394,
    "width": 394,
    "create_time": "2020-06-21T08:30:54Z",
    "modify_time": "2020-06-30T11:50:15Z",
    "signature": "d96d4e822020e862ed43e1d0bd104fda",
    "image_id": "ad-site-i18n/202006215d0de2e725f892ed4e31beb4",
    "size": 22305
  },
  "request_id": "2020063011501401023125104013052"
}
(/code);
```
