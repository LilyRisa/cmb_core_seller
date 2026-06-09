---
title: "Upload catalog videos via a file URL"
doc_id: 1803655037415489
path: "API Reference / Catalog Videos / Upload catalog videos via a file URL"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Upload catalog videos via a file URL

Use this endpoint to upload catalog videos via a file URL. Videos will be added to the E-commerce catalog through an asynchronous task. The response will include a `feed_log_id` representing the task processing ID. You can use this ID to check the processing status and final results via the [/catalog/video/log/](https://business-api.tiktok.com/portal/docs?id=1803655061642242) endpoint.

**Currently, the API only accepts CSV files containing video URLs.** The CSV template for catalog videos is available at [catalog_video_template](https://bytedance.sg.larkoffice.com/sheets/PcBjsyYxahFGMqteR3jl3lv8gKh).

>  <span style="color:DodgerBlue">**Note**</span><br>
>  The endpoint can only handle one file task at the same time, and new tasks will fail if the current file task is not complete. Wait for the current task to complete before sending the next task. 

## Before you start
You need to have an existing E-commerce catalog with products available for ad creation.

If you don't have such an E-commerce catalog yet, follow the instructions in [Upload products to different catalog types via JSON schema](https://business-api.tiktok.com/portal/docs?id=1766862002851842) to create one.


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/catalog/video/file/

**Method** POST

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type.<br>Allowed format: `application/json`.  |
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| bc_id {Required} | string | Business Center ID.<br><br>To obtain the list of Business Centers that you have access to, use [/bc/get/](https://business-api.tiktok.com/portal/docs?id=1737115687501826). |
| catalog_id {Required} | string | Catalog ID. <br><br>To obtain the list of E-commerce catalogs within a Business Center, use [/catalog/get/](https://business-api.tiktok.com/portal/docs?id=1740315452868610) and retrieve `catalog_id` values with `catalog_type` as `ECOM`. <br><br>You need to have Catalog Management permission for the catalog. To obtain the catalogs for which you have Catalog Management permission, use [/bc/asset/get/](https://business-api.tiktok.com/portal/docs?id=1739432717798401). Set `asset_type` to `CATALOG` to retrieve `asset_id` values with `catalog_role` as `ADMIN`. |
| file_url {Required} | string | The download URL of the CSV file. <br><br>The CSV catalog video template is accessible via [catalog_video_template](https://bytedance.sg.larkoffice.com/sheets/PcBjsyYxahFGMqteR3jl3lv8gKh). <br><br>To find a detailed description of each catalog video parameter, see <a href="#item-link-Catalog video parameters">Catalog video parameters</a>. |
| advertiser_ids | string[] | The IDs of the ad accounts that you want to sync the videos to. <br>Once synced, the videos will be available in the creative library of those ad accounts. If you don't want to sync the catalog videos to any ad accounts, do not pass this field. <br><br>Max size: 100. <br><br>The ad account and the catalog (`catalog_id`) must be within the same Business Center (`bc_id`) and you need to have Admin or Operator permission for the ad account. Ineligible ad accounts will be ignored. To obtain the ad accounts for which you have Admin or Operator permission, use [/bc/asset/get/](https://business-api.tiktok.com/portal/docs?id=1739432717798401). Set `asset_type` to `ADVERTISER` to retrieve `asset_id` values with `advertiser_role` as `ADMIN` or `OPERATOR`. |
```

### Catalog video parameters
The following table provides the detailed description of each parameter that you can specify for your catalog video in the CSV template via `file_url`.

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| video_name {Required} | string | The name of the video.<br><br>Length limit: 500 characters. |
| video_link {Required} | string | The URL of the video file. <br><br>File size limit: 100 MB.<ul><li>For TikTok placement:<ul><li>Aspect ratio requirements: Horizontal(16:9) / Square(1:1) / Vertical(9: 16)</li><li>Resolution: Horizontal (minimum 960*540) / Square (minimum 640*640) / Vertical (minimum 540*960) </li><li>Bitrate: > 516.00 kbps</li><li>Duration: 5-60s</li><li>Safe zone: From left: 44 px, From right: 140px, From top: 130px, From bottom:483-484px </li></ul></li><li>For Global App Bundle and Pangle placements:<ul><li>Recommended duration: 5-60s</li></ul></li></ul><br>Example: https://www.tiktok.com/tiktok_t_shirt.mp4 |
| sku_id_list {Required} | string[] | The SKU ID list of E-commerce catalog products to associate the video (`video_link`) with. <br><br>Max size: 20.<br><br>To specify multiple SKU IDs, separate them with commas. <br><br>Example: `SKU_ID_1,SKU_ID_2,SKU_ID_3,SKU_ID_4`. <br><br>To obtain E-commerce catalog products that can be used in ads, use [/catalog/product/get/](https://business-api.tiktok.com/portal/docs?id=1740564136678402). If the returned `ad_creation_eligible` for the product is `AVAILABLE`, you can use the product in ads. |
| category | string | The product category for the video.<br><br>Length limit: 500 characters. If the value exceeds the limit, the value will be overwritten with null. <br><br>Example: `Apparel & Accessories> Clothing> Shirts`. |
| brand | string | The brand name for the video.<br><br>Length limit: 500 characters. If the value exceeds the limit, the value will be overwritten with null. <br><br>Example: TikTok. |
| creator | string | The creator of the video. <br><br>Length limit: 500 characters. If the value exceeds the limit, the value will be overwritten with null.<br><br> Example: TikTok Creator. |
| video_type | string | The type of the video. <br><br> Length limit: 500 characters. If the value exceeds the limit, the value will be overwritten with null. <br><br> Example: Promotional video. |
| description | string | A short description of the video.<br><br> Length limit: 500 characters. If the value exceeds the limit, the value will be overwritten with null. |
| landing_page_url | string | URL of the landing page where you can view and purchase the products that are associated with the video.<br><br> Example: https://www.landingpage.com |
| custom_label_0 | string | Additional information about the video.<br><br>No length limit. |
| custom_label_1 | string | Additional information about the video.<br><br> No length limit. |
| custom_label_2 | string | Additional information about the video. <br><br>No length limit. |
| custom_label_3 | string | Additional information about the video.<br><br>No length limit. |
| custom_label_4 | string | Additional information about the video. <br><br> No length limit. |
```

### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/catalog/video/file/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "bc_id": "{{bc_id}}",
    "catalog_id": "{{catalog_id}}",
    "advertiser_ids": ["{{advertiser_id}}"],
    "file_url": "{{file_url}}"
}'
(/code)
```

## Response

``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|-|-|-|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data.|
#| feed_log_id| string | Catalog video handling log ID. <br><br>To check the processing status and final results of the upload, use [/catalog/video/log/](https://business-api.tiktok.com/portal/docs?id=1803655061642242). |
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
        "feed_log_id": "{{feed_log_id}}"
    }
}
(/code)
```
