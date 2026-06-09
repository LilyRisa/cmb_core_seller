---
title: "Get the uploaded catalog videos within a catalog"
doc_id: 1803655082498050
path: "API Reference / Catalog Videos / Get the uploaded catalog videos within a catalog"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the uploaded catalog videos within a catalog

Use this endpoint to retrieve the list of uploaded catalog videos within an E-commerce catalog.



## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/catalog/video/get/ 

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| bc_id {Required} | string | Business Center ID.<br><br>To obtain the list of Business Centers that you have access to, use [/bc/get/](https://business-api.tiktok.com/portal/docs?id=1737115687501826). |
| catalog_id {Required} | string | Catalog ID. <br><br>To obtain the list of E-commerce catalogs within a Business Center, use [/catalog/get/](https://business-api.tiktok.com/portal/docs?id=1740315452868610) and retrieve `catalog_id` values with `catalog_type` as `ECOM`. <br><br>You need to have Catalog Management or Ad Promotion permission for the catalog. To obtain the catalogs for which you have Catalog Management or Ad Promotion permission, use [/bc/asset/get/](https://business-api.tiktok.com/portal/docs?id=1739432717798401). Set `asset_type` to `CATALOG` to retrieve `asset_id` values with `catalog_role` as `ADMIN` or `AD_PROMOTE`. |
| catalog_video_ids | string[] | The ID list of catalog videos that you want to filter.<br><br>Max size: 50.<br><br><p><span style="color:darkred"><b>Note</b></span>: If you specify a value for `catalog_video_ids`, the pagination parameters `page` and `page_size` will be ignored. |
| page | integer | Current page number. <br><br>Value range: ≥1. Default value: 1. <br><br><p><span style="color:darkred"><b>Note</b></span>: If you specify a value for `catalog_video_ids`, the pagination parameters `page` and `page_size` will be ignored. |
| page_size | integer | Page size. <br><br>Value range: 1-50. Default value: 10. <br><br><p><span style="color:darkred"><b>Note</b></span>: If you specify a value for `catalog_video_ids`, the pagination parameters `page` and `page_size` will be ignored. |
```

### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/catalog/video/get/?bc_id={{bc_id}}&catalog_id={{catalog_id}}&page=1&page_size=50' \
--header 'Access-Token: {{Access-Token}}' \
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
#| videos | object[] | The list of catalog videos. |
##|catalog_video_id | string | The ID of the catalog video.<br><br>Example:  `1234567891234567891_1234567891234567891_12345678912345_12345678-a12b-34cd-ef56-7gh89i1j2k3l `. |
##| video_name| string | The name of the video. |
##| video_link | string | The URL of the video file. |
##| sku_id_list | string[] | The list of SKU IDs associated with the video. |
##| category | string | The product category for the video. |
##| brand | string | The brand name for the video. |
##| creator | string | The creator of the video. |
##| video_type | string | The type of the video. |
##| description | string | A short description of the video. |
##| landing_page_url | string | URL of the landing page where you can view and purchase the products that are associated with the video. |
##| custom_label_0 | string | Additional information about the video. |
##| custom_label_1 | string | Additional information about the video. |
##| custom_label_2 | string | Additional information about the video. |
##| custom_label_3 | string | Additional information about the video. |
##| custom_label_4 | string | Additional information about the video. |
##| video_id | string | The video ID generated after the video extraction is complete. |
##| video_signature | string | The MD5 hash of the video. <br><br>Example: `123abcdefg4a56e789012c3cefa4d567`. |
##| status | string | Video extraction status.<br><br> Enum values: <ul><li>`PENDING`: Video extraction is in progress.</li><li>`SUCCESS`: Video extraction succeeded.</li><li> `FAILED`: Video extraction failed.</li></ul>|
##| create_time | string | The time when the video upload was completed, in the format of `YYYY-MM-DD HH:MM:SS` (UTC+0). <br><br>Example: `2024-01-01 00:00:00`. <br><br><p><span style="color:darkred"><b>Note</b></span>: When `status` is `PENDING` or `FAILED`, the value of this field will be an empty string (`""`). |
##| active_status | string | The user-controlled activation status of the video.<br><br>Enum values: <ul><li>`ACTIVATED`: The video is activated and can be used for ad delivery.</li><li>`DEACTIVATED`: The video is deactivated and cannot be used for ad delivery. Deactivated videos may affect the delivery of associated ads in active campaigns.</li></ul>|
##| preview_url | string | Returned only when `status` is `SUCCESS`. <br><br>The video preview link, which is valid for six hours and needs to be re-acquired after expiration. |
#| page_info | object | Pagination information. |
##| page | integer | Current page number. |
##| page_size | integer | Page size. |
##| total_number | integer | Total number of results. |
##| total_page | integer | Total pages of results. |
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
        "videos": [
            {
                "video_id": "{{video_id}}",
                "preview_url": "{{preview_url}}",
                "video_type": "{{video_type}}",
                "landing_page_url": "",
                "create_time": "{{create_time}}",
                "active_status": "DEACTIVATED",
                "catalog_video_id": "{{catalog_video_id}}",
                "video_link": "{{video_link}}",
                "creator": "{{creator}}",
                "description": "",
                "status": "SUCCESS",
                "video_name": "{{video_name}}",
                "brand": "br10",
                "category": "{{category}}",
                "video_signature": "{{video_signature}}",
                "sku_id_list": [
                    "{{sku_id_1}}",
                    "{{sku_id_2}}",
                    "{{sku_id_3}}"
                ]
            },     
            {
                "video_id": "{{video_id}}",
                "preview_url": "{{preview_url}}",
                "video_type": "{{video_type}}",
                "landing_page_url": "",
                "create_time": "{{create_time}}",
                "active_status": "ACTIVATED",
                "catalog_video_id": "{{catalog_video_id}}",
                "video_link": "{{video_link}}",
                "creator": "{{creator}}",
                "description": "",
                "status": "SUCCESS",
                "video_name": "{{video_name}}",
                "brand": "{{brand}}",
                "category": "{{category}}",
                "video_signature": "{{video_signature}}",
                "sku_id_list": [
                    "{{sku_id_1}}",
                    "{{sku_id_2}}",
                    "{{sku_id_3}}",
                    "{{sku_id_4}}",
                    "{{sku_id_5}}"
                ]
            }
        ],
        "page_info": {
            "total_number": 2,
            "total_page": 1,
            "page": 1,
            "page_size": 50
        }
    }
}
(/code)
```
