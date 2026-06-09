---
title: "Upload products via a file URL"
doc_id: 1740496787164161
path: "API Reference / Catalog Products / Upload products via a file URL"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Upload products via a file URL

Use this endpoint to upload products via a file URL. Products will be added or updated to the catalog asynchronously. In the response, you will get a task processing ID `feed_log_id`. With this ID you can view the processing status and final results of the task via the [/catalog/product/log/](https://ads.tiktok.com/marketing_api/docs?id=1740570027173889) endpoint.

Use this endpoint, rather than [/catalog/product/upload/](https://ads.tiktok.com/marketing_api/docs?id=1740497429681153), for the initial upload of products to a catalog, or for the regular full upload of products to a catalog with the update frequency of once a day.


**Currently, the API only supports CSV file format for Catalog Feeds.** CSV Catalog Feed templates are available for different types of catalogs as below:
- [Template for E-commerce catalogs](https://bytedance.feishu.cn/sheets/shtcnZ3VnFbOO64OiFNZRhlz8Db)
- [Template for hotel catalogs](https://bytedance.feishu.cn/sheets/FJNUsA17Xhp540t41Kecq8ZInCf)
- [Template for flight catalogs](https://bytedance.feishu.cn/sheets/SII0sykCGhEObgtJaENcPSDpnyc)
- [Template for destination catalogs](https://bytedance.feishu.cn/sheets/OvJIsVlNfhl30ttKs7MciksInPh)
- [Template for entertainment catalogs](https://bytedance.feishu.cn/sheets/MrBAsQuGchn0pQtRRiyc47lynyg)
- [Template for Auto-Inventory catalogs](https://bytedance.sg.larkoffice.com/sheets/QuGIs2PgjhXLtOtDLaBceZnVnVg)
- [Template for Auto-Model catalogs](https://bytedance.sg.larkoffice.com/sheets/RjwhsCwlIhepPHtOq1SlD868g8b)
- [Template for mini series catalogs](https://bytedance.sg.larkoffice.com/sheets/SmHxs8C4oh0mKWtenOslDZ0Sg6b)
 <br>Product images (`image_link`) must be 500x500 px or larger in size. Otherwise, the product will not be approved. For details, see [Product Image Requirements](https://ads.tiktok.com/marketing_api/docs?id=1739578479392770).

>  <span style="color:DodgerBlue">**Note**</span><br>
>  <ul><li>Entertainment catalogs are currently an allowlist-only feature and is invitation-only because the catalog type is under Alpha Testing. If you would like to access it, please contact your TikTok representative. However, acceptance into the Alpha Test is not guaranteed.</li><li>The mini series catalog is currently an allowlist-only feature and is invitation-only because the catalog type is under testing. If you would like to access it, please contact your TikTok representative. However, acceptance into the test is not guaranteed. </li><li>The SKU is unique under the same product catalog and only belongs to a certain feed. All operations on a SKU need to be performed under the feed to which it belongs.</li><li>A feed can only handle one file task at the same time, and new tasks will fail if the current file task is not complete. Please wait for the current task to complete before sending the next task. The processing speed for products is about 5 million entries per hour, which is impacted by service load and is for reference only.</li></ul>

## Comparing v1.2 and v1.3
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/catalog/product/file/| /v1.3/catalog/product/file/|
|Request parameter data type |`bc_id`: number<br>`catalog_id`: number|`bc_id`: string<br>`catalog_id`: string|
|Request parameter name and data type |`feeds_id`: number|`feed_id`: string|
|New request parameters |/|`update_mode`: string|
|Response parameter data type|`feed_log_id`: number|`feed_log_id`: string|
```
## Request
**Endpoint** https://business-api.tiktok.com/open_api/v1.3/catalog/product/file/

**Method** POST

**Header**

``` xtable
| Field {30%}| Data Type {15%}| Description {55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162). |
|Content-Type {Required}|string|Request content type. <br>Allowed value: `"application/json"`.  |
```



**Parameters**
```xtable
| Field {30%}| Data Type {15%}| Description {55%}|
|--- |--- |--- |
|bc_id {Required}|string| Business Center ID. |
|catalog_id {Required}|string|Catalog ID. |
|feed_id|string|Feed ID.|
|file_url {Required}|string|The download address of the CSV file. The number of product lines in the file should be less than 20 million. <br><br><ul><li>Currently, the API only allows CSV file type Catalog Feeds. CSV Catalog Feed templates are available for different types of catalogs as below:<ul><li>[Template for E-commerce catalogs](https://bytedance.feishu.cn/sheets/shtcnZ3VnFbOO64OiFNZRhlz8Db)</li><li>[Template for hotel catalogs](https://bytedance.feishu.cn/sheets/FJNUsA17Xhp540t41Kecq8ZInCf)<ul><li>When your CSV file contains alias fields, the system will automatically map these fields to their corresponding existing fields. To learn more about the alias fields and their related existing fields, see <a href="#item-link-Alias fields for hotel catalogs">Alias fields for hotel catalogs</a>.</li></ul></li><li>[Template for flight catalogs](https://bytedance.feishu.cn/sheets/SII0sykCGhEObgtJaENcPSDpnyc)<ul><li>When your CSV file contains alias fields, the system will automatically map these fields to their corresponding existing fields. To learn more about the alias fields and their related existing fields, see <a href="#item-link-Alias fields for flight catalogs">Alias fields for flight catalogs</a>.</li></ul></li><li>[Template for destination catalogs](https://bytedance.feishu.cn/sheets/OvJIsVlNfhl30ttKs7MciksInPh)</li><li>[Template for entertainment catalogs](https://bytedance.feishu.cn/sheets/MrBAsQuGchn0pQtRRiyc47lynyg)</li><li>[Template for Auto-Inventory catalogs](https://bytedance.sg.larkoffice.com/sheets/QuGIs2PgjhXLtOtDLaBceZnVnVg)</li><li>[Template for Auto-Model catalogs](https://bytedance.sg.larkoffice.com/sheets/RjwhsCwlIhepPHtOq1SlD868g8b)</li><li>[Template for mini series catalogs](https://bytedance.sg.larkoffice.com/sheets/SmHxs8C4oh0mKWtenOslDZ0Sg6b)</li></ul>To learn about the detailed introduction of each catalog product parameter, see [`products` object array parameters](https://business-api.tiktok.com/portal/docs?id=1740497429681153#item-link-products%20object%20array%20parameters). <br>  To understand how a field and its corresponding value in JSON format are represented in the CSV template, see [Comparison of Catalog Product Parameters in JSON Format & CSV Template](https://bytedance.sg.larkoffice.com/sheets/F7CGsxjuahfnAYt7GzTlR5X5ghb?sheet=gJ73Xi).</li><li>Product images (`image_link`) must be 500 x 500 pixels or larger in size. Otherwise, the product will not be approved. For details, see [Product Image Requirements](https://ads.tiktok.com/marketing_api/docs?id=1739578479392770).</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>To upload short drama series that target multiple regions to a short drama catalog, call [/catalog/product/file/](https://business-api.tiktok.com/portal/docs?id=1740496787164161) multiple times with the same `series_id` and different `country` settings. After the uploads, access the consolidated targeting settings for the same short drama series through `target_config` and `additional_product_list` in [/catalog/product/get/](https://business-api.tiktok.com/portal/docs?id=1740564136678402). <ul><li>For example, separate uploads with `country` set to `US` (same as the primary targeting region for the short drama catalog) and `VN` (same as one of the additional targeting regions) results in `list.target_config.region_code` as `US` and `list.additional_product_list.region_code` as `VN` in `/catalog/product/get/`.</li></ul></li><li>If your additional image URL contains commas (,), URL-encode them by replacing with %2C before adding them to this field.<br>For example, for URLs such as `https://img.example.com/product.jpg?width=800&height=600&quality=85&format=webp`and `https://img.example.com/product_800x600<b>,</b>q85<b>,</b>webp.jpg`, you need to set this field to `["https://img.example.com/product.jpg?width=800&height=600&quality=85&format=webp","https://img.example.com/product_800x600<b>%2C</b>q85<b>%2C</b>webp.jpg"]`.</li></ul></p>|
|update_mode|string|Update mode. The mode is one-time only and only applies to the current request.<br>Enum values: <ul><li>`OVERWRITE`: In this mode, all the products in the catalog will be replaced by the products in the file you specify.</li><li>`INCREMENTAL`: In this mode, the products in the file you specify will be added to the catalog. Note that if you upload a product already in the catalog with a duplicate SKU ID via the file, the data for the product in the catalog will be replaced.</li></ul>Default value: `INCREMENTAL`.<br><p><span style="color:darkred"><b>Note</b></span>: If you pass in `feed_id`, the update mode you specify via this field will not affect the update mode of the feed. |
```
### Alias fields for hotel catalogs
The table below outlines the available alias fields and their corresponding existing field names within hotel catalogs. For example, if you include `image[0].url`, this field will seamlessly map to `image_link`.
```xtable
| Existing Field {30%} | Corresponding Alias Field {60%} |
|---|---|
| `name` | `hotel_name` |
| `image_link` | `image[0].url` |
| `video_link` | `video[0].url` |
| `address` | `addr1`<br>`address.addr1` |
| `secondary_address` | `addr2`<br>`address.addr2` |
| `tertiary_address` | `addr3`<br>`address.addr3` |
| `neighborhood` | `neighborhood[0]` |
| `rating_system` | `guest_rating[0].rating_system` |
| `max_score` | `guest_rating[0].max_score` |
| `guest_ratings.score` | `guest_rating[0].score` |
| `guest_ratings.number_of_reviewers` | `guest_rating[0].number_of_reviewers` |
| `price` | `base_price` |
| `link` | `url` |
| `ios_url` | `applink.ios_url` |
| `ios_app_store_id` | `applink.ios_app_store_id` |
| `ios_app_name` | `applink.ios_app_name` |
| `android_url` | `applink.android_url` |
| `android_package` | `applink.android_package` |
| `android_app_name` | `applink.android_app_name` |
| `internal_label[0]` | `product_tags[0]`<br>`internal_label ` |
| `internal_label[1]` | `product_tags[1]` |
```

### Alias fields for flight catalogs
The table below outlines the available alias fields and their corresponding existing field names within flight catalogs. For example, if you include `image[0].url`, this field will seamlessly map to `image_link`.
```xtable
| Existing Field {30%} | Corresponding Alias Field {60%} |
|---|---|
| `image_link` | `image[0].url` |
| `video_link` | `video[0].url` |
| `link` | `url` |
| `ios_url` | `applink.ios_url` |
| `ios_app_store_id` | `applink.ios_app_store_id` |
| `ios_app_name` | `applink.ios_app_name` |
| `android_url` | `applink.android_url` |
| `android_package` | `applink.android_package` |
| `android_app_name` | `applink.android_app_name` |
| `internal_label[0]` | `product_tags[0]` |
| `internal_label[1]` | `product_tags[1]` |
```

### Example

```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/oauth2/access_token/' \
--data '{
    "app_id" : "{{app_id}}",
    "auth_code" : "{{auth_code}}",
    "secret" : "{{secret}}"
}'
(/code)
```

## Response

``` xtable
| Field | Type | Description |
|--- |--- |--- |
| code | number  |Return code, see [Appendix - return code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message |string|Return message, see [Appendix-Return message](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
| request_id | string | The log id of a request, which uniquely identifies the request. |
| data |object| Returned data. |
#| feed_log_id | string | Catalog handling log ID. |
```

### Example

```xcodeblock
(code Success-Response http) 
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {
        "feed_log_id": "{{feed_log_id}}"
    },
    "request_id": "{{request_id}}"
}
 (/code);
```

