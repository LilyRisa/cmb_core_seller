---
title: "Upload a business certificate"
doc_id: 1739938996913218
path: "API Reference / BC Assets / Upload a business certificate"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Upload a business certificate

Use this endpoint to upload a business certificate. You need to be an Admin user of the Business Center. 

If you want to create an ad account in the Business Center for advertisers that are registered in the Chinese Mainland, Hong Kong, France, Brazil, or Mexico, you need to upload images of business licenses or other qualification documents. 

This endpoint returns the ID of the uploaded image (`image_id`). This field can then be passed to the `license_image_id` (for ad accounts registered in the Chinese Mainland or Hong Kong) or `qualification_image_ids` (for ad accounts registered in France, Brazil, or Mexico) field in the [/bc/advertiser/create/](https://ads.tiktok.com/marketing_api/docs?id=1739939020318721) endpoint.

> <span style="color:DarkRed">**Important**</span><br>
> As of September 26, 2024, to ensure ad account security, we have implemented UnionPay verification for the creation of ad accounts in Business Centers via [/bc/advertiser/create/](https://business-api.tiktok.com/portal/docs?id=1739939020318721) for advertisers registered in the Chinese mainland. Existing API workflows will continue to function until January 8, 2025. By mid-October 2024, we have released the API changes along with detailed API documentation supporting this change. Affected developers need to implement the new workflow before January 8, 2025, after which integrations might no longer function correctly. 

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/bc/image/upload/| /v1.3/bc/image/upload/|
|Request parameter data type|`bc_id`: number |`bc_id`: string |
```


## Request

**Endpoint**  https://business-api.tiktok.com/open_api/v1.3/bc/image/upload/

**Method** POST

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
| Content-Type {Required} | String | Request message type, upload files in `multipart/form-data` format.|
```

**Parameters**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|bc_id {Required}|string| Business Center ID |
|image_file {Required}|file| The certificate image file to be uploaded. Supported picture format: JPG/JPEG/PNG. The maximum file size is 10 MB. |
```

### Example

```xcodeblock
(code curl http)
curl --request POST -H "Access-Token:xxx" \
--form 'bc_id=bc_id' \
--form 'image_file=@"filepath"' \
https://business-api.tiktok.com/open_api/v1.3/bc/image/upload/
(/code)

```


## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Return code, see [Appendix-Return codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message |string|Return message, see [Appendix-Return codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097)  .|
|data |object|Return data.|
#|image_id |string| The ID of the uploaded image. This field can  be passed to the `license_image_id` or `qualification_image_ids` field in [/bc/advertiser/create/](https://ads.tiktok.com/marketing_api/docs?id=1739939020318721) endpoint to create an ad account in Business Center.|
#|image_url |string| The preview link to the uploaded certificate image. It becomes invalid after the first view.|
|request_id |string|The log id of the request, which uniquely identifies a request. |
```

### Example

``` xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {}
}
(/code);
```
