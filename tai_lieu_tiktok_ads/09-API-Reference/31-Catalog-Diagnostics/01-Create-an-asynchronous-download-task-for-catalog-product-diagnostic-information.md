---
title: "Create an asynchronous download task for catalog product diagnostic information"
doc_id: 1771117279175682
path: "API Reference / Catalog Diagnostics / Create an asynchronous download task for catalog product diagnostic information"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create an asynchronous download task for catalog product diagnostic information

Use this endpoint to create an asynchronous download task for catalog product diagnostic information.

After the task is created, use [/diagnostic/catalog/product/task/get/](https://ads.tiktok.com/marketing_api/docs?id=1771117294731266) to download the resulting CSV file. Note that the diagnostic information provided by the asynchronous download task is less detailed than the synchronous catalog diagnostic information provided by the endpoint /diagnostic/catalog/. The asynchronous download task only returns the Product ID (`product_id`), SKU ID (`sku_id` / `hotel_id` / `flight_id` / `destination_id`), Item title (`title`), Issue title (`issue_title`), Severity (`issue_level`), and Source (feed name) for each issue.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/diagnostic/catalog/product/task/create/ 

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
| catalog_id {Required} | string | ID of a catalog that you have permission to access. |
| bc_id {Required} | string | ID of a Business Center that either owns the catalog, or has been granted access to it as an asset. |
| feed_id | string | Feed ID. <br>If not specified, the ID of the default feed for the catalog will be used. <br> To retrieve the diagnostics for all feeds for the catalog, set this field to `ALL`. <br>To obtain feed IDs for a catalog, use [/catalog/feed/get/](https://ads.tiktok.com/marketing_api/docs?id=1740665183073281). |
| lang | string | The language you want to set for the returned catalog diagnostic information. <br>Default value: `en`. <br> To find out the enum values, refer to the <a href="#item-link-Enum values for lang">Enum values for `lang`</a> section below. |
| issue_id | string |The ID of the issue you want to download the diagnostic information for.<br>You need to specify the ID of a downloadable issue, which can be selected from the <a href="#item-link-Downloadable issues">Downloadable issues</a> section below.<br>If not specified, the diagnostics for all detected issues will be returned.|
```

### Enum values for `lang`

```xtable
| Value {30%} | Description{70%}|
|---|---|
| `ar` | Arabic |
| `cs-CZ` | Czech |
| `de` | German |
| `en` | English |
| `es` | Spanish |
| `fil` | Filipino |
| `fr` | French |
| `id` | Indonesian |
| `it` | Italian |
| `ja` | Japanese |
| `ko` | Korean |
| `ms` | Malay |
| `pl-PL` | Polish |
| `pt` | Portuguese (Brazil) |
| `ru` | Russian |
| `sv-SE` | Swedish |
| `th` | Thai |
| `tr` | Turkish |
| `vi` | Vietnamese |
| `zh` | Chinese |
```

### Downloadable issues
If you want to download the diagnostics for a certain issue, pass one of the issue IDs in the table below to `issue_id`. 
```xtable
|<code>issue_id</code>{30%}  | Description{70%}  |
|---|---|
| `1101` | A required product attribute field is missing. |
| `1102` | An invalid value is entered for a required field. |
| `1103` | Product IDs, such as SKU IDs, are duplicated. |
| `1105` | An invalid value is entered for an optional field. |
| `1106` | An optional but recommended field is missing. |
| `1107` | The number of columns for an item on the feed or file does not match the number of columns in the first row. |
| `1108` | Only capital letters are used for a field value. |
| `1109` | The number of additional image links exceeds the maximum limit of 10. |
| `1110` | The format of the optional URL is invalid. |
| `1111` | The format of the required URL is invalid. |
| `1112` | Non-UTF-8 charset is used.  |
| `1113` | Emojis or control characters are used. |
| `2001` | Image download failed due to server issues. |
| `2002` | The image size is less than 500 x 500 pixels. |
| `2003` | The format of feed file or link is invalid. |
| `2004` | The image link is invalid, and the image information cannot be downloaded. |
| `3001` | The product was disapproved due to prohibited content. |
| `4001` | The product link is invalid. |
| `4002` | The product link cannot be accessed. |
| `4003` | The prices on the product and landing page do not match. |
| `4005` | The Item Group ID field is not filled in. |
| `4006` | The product addition image link field is not filled in. |
| `4007` | The Product Type field is not filled in. |
| `4008` | The Google Product Category field is not filled in. |
| `4009` | The Global Trade Item Number (GTIN) field is not filled in. |
```
### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/diagnostic/catalog/product/task/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "catalog_id":"{{catalog_id}}",
    "bc_id":"{{bc_id}}"
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
#|task_id| string | ID of the download task for catalog diagnostic information. |
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
        "task_id": "{{task_id}}"
    }
}
(/code)
```
