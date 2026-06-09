---
title: "Get the catalog video handling log"
doc_id: 1803655061642242
path: "API Reference / Catalog Videos / Get the catalog video handling log"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the catalog video handling log

Use this endpoint to check if the catalog videos are uploaded successfully, and what to do if the upload fails.


## Before you start
You need to have the log ID of the catalog video handling task for which you want to check the results. To upload catalog videos and obtain the log ID, use [/catalog/video/file/](https://business-api.tiktok.com/portal/docs?id=1803655037415489). 

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/catalog/video/log/

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
| feed_log_id {Required} | string | Catalog video handling log ID.  <br><br> To upload catalog videos and obtain the log ID, use [/catalog/video/file/](https://business-api.tiktok.com/portal/docs?id=1803655037415489). |
| language | string | Supported languages. <br><br>Enum values:<ul><li>`en`: English</li><li>`zh-CN`: Chinese</li><li>`ja-JP`: Japanese</li><li>`de`: German</li><li>`es`: Spanish</li><li>`fr`: French</li><li>`hi-IN`: Hindi (India)</li><li>`id`: Indonesian</li><li>`it`: Italian</li><li>`ko`: Korean</li><li>`ms`: Malay</li><li> `ru`: Russian</li><li>`th`: Thai</li><li>`tr`: Turkish</li><li>`vi`: Vietnamese</li></ul><br>Default value: `en`.|
```

### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/catalog/video/log/' \
--header 'Access-Token: {{Access-Token}}' \
--data '{
    "bc_id": "{{bc_id}}",
    "catalog_id": "{{catalog_id}}",
    "feed_log_id": "{{feed_log_id}}"
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
#| video_feed_log | object | Video handling results. |
##| catalog_id | string | Catalog ID. |
##| feed_id | string | Feed ID. |
##| add_count | number | Total number of new videos. |
##| update_count | number | Total number of videos modified.<br><br><p><span style="color:darkred"><b>Note</b></span>: The value of this field will always be 0. |
##| delete_count | number | Total number of videos deleted. |
##| error_count | number | Number of errors. |
##| warn_count| number | Number of warnings. |
##| process_status | string | Processing status. <br><br>Enum values:`PROCESSING`, `SUCCESS`,`FAILED`, `WAITING`. |
##| start_time | string | Start time of the video handling task, in the format of `YYYY-MM-DD HH:MM:SS` (UTC+0). <br><br> Example: 2024-01-01 00:00:00. |
##| end_time | string | End time of the video handling task, in the format of `YYYY-MM-DD HH:MM:SS` (UTC+0). <br><br>Example: 2024-01-01 00:00:00. |
#| feed_log_data | object | Detailed processing information. |
##| download_path | object | File download address with all error and warning messages. The key is language, and the value is file address.|
##| error_affected_videos | object[] | Error message. |
###| affected_video_count | number | Number of videos affected.  |
###| affected_video_item_list | object[] | List of affected videos, showing only the first five items. |
####| index | number | Index that identifies the position of the video among the videos you uploaded. <br><br>For example, an index of `2` represents the first video you uploaded through [/catalog/video/file/](https://business-api.tiktok.com/portal/docs?id=1803655037415489), listed in the second row of the CSV file after the header row. |
####| video_name | string | The name of the video. |
####| video_link | string | The URL of the video file. <br><br>Example: https://www.tiktok.com/tiktok_t_shirt.mp4 |
####| sku_id_list | string | The SKU ID list of E-commerce catalog products to associate the video (`video_link`) with. <br><br>Example: `"SKU_ID_1, SKU_ID_2"`. |
####| description | string | A short description of the video. |
####| category | string | The product category for the video. <br><br>Example: Apparel & Accessories> Clothing> Shirts. |
###| field | string | The field with issue. |
###| issue | string | Issue details. |
###| suggestion | string | Suggestions for solving the issue. |
##| warn_affected_videos | object[] | Warning message. |
###| affected_video_count | number | Number of products affected. |
###| affected_video_item_list | object[] | List of affected items, showing only the first five items. |
####| index | number | Index that identifies the position of the video among the videos you uploaded. <br><br>For example, an index of `2` represents the first video you uploaded through [/catalog/video/file/](https://business-api.tiktok.com/portal/docs?id=1803655037415489), listed in the second row of the CSV file after the header row. |
####| video_name | string | The name of the video. |
####| video_link | string | The URL of the video file. <br><br>Example: https://www.tiktok.com/tiktok_t_shirt.mp4 |
####| sku_id_list | string | The SKU ID list of E-commerce catalog products to associate the video (`video_link`) with. <br><br>Example: `"SKU_ID_1, SKU_ID_2"`. |
####| description | string | A short description of the video. |
####| category | string | The product category for the video. <br><br>Example: Apparel & Accessories> Clothing> Shirts. |
###| field | string | The field with issue. |
###| issue | string | Issue details. |
###| suggestion | string | Suggestions for solving the issue. |
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
        "video_feed_log": {
            "update_mode": null,
            "process_status": "SUCCESS",
            "catalog_id": "{{catalog_id}}",
            "delete_count": 0,
            "feed_id": "{{feed_id}}",
            "start_time": "{{start_time}}",
            "error_count": 0,
            "feed_log_data": {
                "error_affected_videos": null,
                "download_path": {},
                "warn_affected_videos": null
            },
            "end_time": "{{end_time}}",
            "warn_count": 0,
            "add_count": 1,
            "update_count": 0
        }
    }
}
(/code)
```
