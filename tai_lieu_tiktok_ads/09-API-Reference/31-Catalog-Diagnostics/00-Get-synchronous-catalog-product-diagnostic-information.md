---
title: "Get synchronous catalog product diagnostic information"
doc_id: 1771117232728066
path: "API Reference / Catalog Diagnostics / Get synchronous catalog product diagnostic information"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get synchronous catalog product diagnostic information

Use this endpoint to retrieve diagnostic information synchronously for your catalog products.
- If issues are detected in your catalog, the most recent diagnostic information may be generated for the previous day. This delay is because of the one-day delay in catalog diagnostic data, which is based on UTC+0 Time.
- If no issues are detected in your catalog, the returned diagnostic information will be empty.

To create an asynchronous download task for catalog product diagnostic information, use [/diagnostic/catalog/product/task/create/](https://ads.tiktok.com/marketing_api/docs?id=1771117279175682). Note that the diagnostic information provided by the asynchronous download task is less detailed than the information provided by this endpoint. The asynchronous download task only returns the Product ID (`product_id`), SKU ID (`sku_id` / `hotel_id` / `flight_id` / `destination_id`), Item title (`title`), Issue title (`issue_title`), Severity (`issue_level`), and Source (feed name) for each issue.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/diagnostic/catalog/

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
| catalog_id {Required} | string | ID of a catalog that you have permission to access. |
| bc_id {Required} | string | ID of a Business Center that either owns the catalog, or has been granted access to it as an asset. |
| feed_id | string | Feed ID. <br>If not specified, the ID of the default feed for the catalog will be used. <br> To retrieve the diagnostics for all feeds for the catalog, set this field to `ALL`. <br>To obtain feed IDs for a catalog, use [/catalog/feed/get/](https://ads.tiktok.com/marketing_api/docs?id=1740665183073281). |
| filtering | object | Filtering conditions. |
#| issue_level | string | The issue level to filter the results by. <br>Enum values: <ul><li> `CRITICAL`: To filter critical issues that require immediate attention. </li><li>`WARNING`: To filter warning issues for which suggestions are provided to optimize the product settings. </li></ul>|
#| issue_category | string | The issue category to filter the results by. <br> Enum values: <ul><li>`PRODUCT_ATTRIBUTES`: Product attributes issue. </li><li>`PRODUCT_REVIEW`: Product review issue. </li><li>`CATALOG`: Catalog issue.</li><li>`PIXEL_OR_EVENT`: Pixel or event issue.</li><li>`FILE_UPLOAD_OR_FEED`: File upload or feed issue. </li></ul> |
| lang | string | The language you want to set for the returned `issue_title` and `reason_and_suggestion`. <br>Default value: `en`. <br> To find out the enum values, refer to the <a href="#item-link-Enum values for lang">Enum values for `lang`</a> section below. |
| page | integer | Current page number. <br>Default value: 1. <br> Value range: ≥1. |
| page_size| integer | Page size. <br>Default value: 10. <br> Value range: [1, 20]. |
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
### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/diagnostic/catalog/?catalog_id={{catalog_id}}&bc_id={{bc_id}}&filtering={"issue_level":"CRITICAL"}&lang=en&page=1&page_size=20' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json'
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
#| diagnostic_date | string | The date (UTC +0 Time) when the diagnostic information was generated, in the format of `"YYYY-MM-DD"`. <br>Example: `"2023-06-15"`. |
#| issues | object[] | Diagnostic information about issues detected in the catalog. |
##| issue_id | string | Issue ID. <br>Some issue IDs can be passed to the endpoint [/diagnostic/catalog/product/task/create/](https://ads.tiktok.com/marketing_api/docs?id=1771117279175682) to create an asynchronous download task for diagnostic information about the issue. |
##| issue_title| string | The issue title, which provides a summary of the issue. |
##| reason_and_suggestion| string | Reason and suggestion. <br> This field contains a description of the issue and a suggestion on how to fix it. |
##| issue_level| string | Issue level. <br>Enum values: <ul><li> `CRITICAL`: This is a critical issue that requires immediate attention. </li><li>`WARNING`: This is a warning issue for which a suggestion is provided to optimize the product settings. </li></ul>|
##| issue_category | string | Issue category. <br> Enum values: <ul><li>`PRODUCT_ATTRIBUTES`: Product attributes issue. </li><li>`PRODUCT_REVIEW`: Product review issue. </li><li>`CATALOG`: Catalog issue. </li><li> `PIXEL_OR_EVENT`: Pixel or event issue. </li><li>`FILE_UPLOAD_OR_FEED`: File upload or feed issue.</li></ul>|
##| issue_product_field | string | The product field for which the issue was detected. |
##| affected_product_count | integer | The number of catalog products for which the issue was detected. |
##| affected_product_percentage | number | The percentage of catalog products for which the issue was detected. <br>Value range: [0,100]. |
##| example_affected_products | object[] | Returned when the value of `affected_product_count` is greater than 0. <br><br> An object array containing up to 10 sample catalog products for which the issue was detected. You can use the fields in this object array to analyze the issues in detail. <br><br>  The schema for this object array is the same as the `list` parameter in the response of [/catalog/product/get/](https://ads.tiktok.com/marketing_api/docs?id=1740564136678402#item-link-Response). Note that the specific fields returned for each product vary depending on the product type. For instance, `hotel_id` is only returned for hotel catalog products. |
#| page_info | object | Pagination information. |
##| page | number | Current page number. |
##| page_size | number | Paging size. |
##| total_number | number | Total number of results. |
##| total_page | number | Total pages of results. |
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
        "issues": [
            {
                "affected_product_percentage": 0.06253552657278164,
                "example_affected_products": [],
                "issue_id": "2002",
                "issue_level": "CRITICAL",
                "affected_product_count": 714,
                "issue_product_field": "",
                "issue_category": "PRODUCT_ATTRIBUTES",
                "reason_and_suggestion": "Check and update the link to an image that is at least 500x500 pixels",
                "issue_title": "Image is less than 500x500 pixels"
            },
            {
                "affected_product_percentage": 0.21221790040035,
                "example_affected_products": [],
                "issue_id": "3001",
                "issue_level": "CRITICAL",
                "affected_product_count": 2423,
                "issue_product_field": "",
                "issue_category": "PRODUCT_REVIEW",
                "reason_and_suggestion": "Update product info and appeal to continue. For approval, make sure newly updated products follow our Ad Policy.",
                "issue_title": "Product was disapproved due to prohibited content"
            },
            {
                "affected_product_percentage": 0.026363016104211864,
                "example_affected_products": [],
                "issue_id": "2004",
                "issue_level": "CRITICAL",
                "affected_product_count": 301,
                "issue_product_field": "",
                "issue_category": "PRODUCT_ATTRIBUTES",
                "reason_and_suggestion": "Check and update the image link. It must end with .jpeg or .png.",
                "issue_title": "Image link is invalid and can't be downloaded"
            },
            {
                "affected_product_percentage": 0.0053426710377306435,
                "example_affected_products": [],
                "issue_id": "2003",
                "issue_level": "CRITICAL",
                "affected_product_count": 61,
                "issue_product_field": "",
                "issue_category": "PRODUCT_ATTRIBUTES",
                "reason_and_suggestion": "Check the feed link and upload a file that is in CSV, XML, TSV, or GZIP format.",
                "issue_title": "Incorrect feed file or link format"
            },
            {
                "affected_product_percentage": 0.002539958362199814,
                "example_affected_products": [],
                "issue_id": "2001",
                "issue_level": "CRITICAL",
                "affected_product_count": 29,
                "issue_product_field": "",
                "issue_category": "PRODUCT_ATTRIBUTES",
                "reason_and_suggestion": "Check that the image hosting site doesn't block TikTok's server from fetching information.",
                "issue_title": "TikTok doesn't have permissions to access the image information hosted on your server"
            },
            {
                "affected_product_percentage": 6.694191640734276,
                "example_affected_products": [
                    {
                        "brand": "{{brand}}",
                        "product_version": "{{product_version}}",
                        "product_id": "{{product_id}}",
                        "item_group_id": "{{item_group_id}}",
                        "description": "{{description}}",
                        "title": "{{title}}",
                        "image_status": "SUCCESS",
                        "availability": "IN_STOCK",
                        "sku_id": "{{sku_id}}",
                        "profession": {
                            "condition": "NEW"
                        },
                        "image": [
                            {
                                "url": "{{url}}"
                            }
                        ],
                        "google_product_category": "",
                        "price": {
                            "sale_price": 0,
                            "price": 104.18,
                            "sale_price_effective_date": []
                        },
                        "audit": {
                            "audit_status": "approved"
                        },
                        "active_status": "ACTIVATED",
                        "image_url": "{{image_url}}",
                        "additional_image_urls": [],
                        "video_url": "",
                        "global_trade_item_number": "",
                        "manufacturer_part_number": "",
                        "landing_page": {
                            "android_url": "",
                            "ios_app_store_id": "",
                            "android_app_name": "",
                            "ios_app_name": "",
                            "iphone_app_name": "",
                            "ipad_app_store_id": "",
                            "ipad_app_name": "",
                            "android_package": "",
                            "ios_url": "",
                            "url": "{{url}}",
                            "iphone_app_store_id": "",
                            "landing_page_url": "{{landing_page_url}}"
                        },
                        "extra_info": {
                            "custom_label_4": "{{custom_label_4}}",
                            "custom_label_1": null,
                            "custom_label_3": null,
                            "custom_label_0": null,
                            "custom_label_2": null
                        }
                    },
                    {
                        "brand": "{{brand}}",
                        "product_version": "{{product_version}}",
                        "product_id": "{{product_id}}",
                        "item_group_id": "{{item_group_id}}",
                        "description": "{{description}}",
                        "title": "{{title}}",
                        "image_status": "SUCCESS",
                        "availability": "IN_STOCK",
                        "sku_id": "{{sku_id}}",
                        "profession": {
                            "condition": "NEW"
                        },
                        "image": [
                            {
                                "url": "{{url}}"
                            }
                        ],
                        "google_product_category": "",
                        "price": {
                            "sale_price": 0,
                            "price": 103.46,
                            "sale_price_effective_date": []
                        },
                        "audit": {
                            "audit_status": "approved"
                        },
                        "active_status": "ACTIVATED",
                        "image_url": "{{image_url}}",
                        "additional_image_urls": [],
                        "video_url": "",
                        "global_trade_item_number": "",
                        "manufacturer_part_number": "",
                        "landing_page": {
                            "android_url": "",
                            "ios_app_store_id": "",
                            "android_app_name": "",
                            "ios_app_name": "",
                            "iphone_app_name": "",
                            "ipad_app_store_id": "",
                            "ipad_app_name": "",
                            "android_package": "",
                            "ios_url": "",
                            "url": "{{url}}",
                            "iphone_app_store_id": "",
                            "landing_page_url": "{{landing_page_url}}"
                        },
                        "extra_info": {
                            "custom_label_4": "{{custom_label_4}}",
                            "custom_label_1": null,
                            "custom_label_3": null,
                            "custom_label_0": null,
                            "custom_label_2": null
                        }
                    }   
                ],
                "issue_id": "4003",
                "issue_level": "CRITICAL",
                "affected_product_count": 76431,
                "issue_product_field": "",
                "issue_category": "PRODUCT_ATTRIBUTES",
                "reason_and_suggestion": "Update the product price.",
                "issue_title": "Prices on the product and landing page don't match."
            }
        ],
        "page_info": {
            "total_page": 1,
            "page_size": 20,
            "page": 1,
            "total_number": 6
        },
        "diagnostic_date": "2023-07-05"
    }
}
(/code)
```
