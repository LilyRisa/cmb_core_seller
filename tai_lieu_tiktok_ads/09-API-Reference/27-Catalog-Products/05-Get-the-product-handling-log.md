---
title: "Get the product handling log"
doc_id: 1740570027173889
path: "API Reference / Catalog Products / Get the product handling log"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the product handling log

Use this endpoint to check if the products are uploaded or deleted successfully, and what to do if the upload or deletion fails.

## Comparing v1.2 and v1.3
 ```xtable
 |Changes{20%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/catalog/product/log/| /v1.3/catalog/product/log/|
|Request parameter data type |`bc_id`: number<br>`catalog_id`: number<br>`feed_log_id`: number|`bc_id`: string<br>`catalog_id`: string <br>`feed_log_id`: string|
|Response parameter name|`status` <br>`product_feeds_log`<br>`feeds_Log_data`<br>`error_effected_products`<ul><li>`effected_product_count` </li><li>`items_product_item_list`</li><li>`id`</li><li>`link`</li><li>`suggest`</li></ul>`warn_effected_products`<ul><li>`effected_product_count` </li><li>`items_product_item_list`</li><li>`id`</li><li>`link`</li><li>`suggest` </li></ul>|`process_status`<br>`product_feed_log`<br>`feed_log_data`<br>`error_affected_products`<ul><li>`affected_product_count`</li><li>`affected_product_item_list`</li><li>`sku_id`</li><li>`product_url`</li><li>`suggestion`</li></ul>`warn_affected_products`<ul><li>`affected_product_count`</li><li>`affected_product_item_list`</li><li>`sku_id`</li><li>`product_url`</li><li>`suggestion`</li></ul>|
|Response parameter data type|`catalog_id`: number|`catalog_id` : string|
|Response parameter name and data type|`feeds_id`: number|`feed_id` : string|
|New response parameters|/|<ul><li>`hotel_id`</li><li>`name`</li><li>`flight_id`</li><li>`airline_company`</li><li>`media_title_id`</li><li>`vehicle_id`</li><li>`make`</li><li>`series_id`</li></ul>|
```


## Request

**Endpoint** 
https://business-api.tiktok.com/open_api/v1.3/catalog/product/log/

**Method** GET

**Header**

``` xtable
| Field {30%}| Type{15%} | Description{55%} |
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

``` xtable
| Field {30%}| Type{15%} | Description{55%} |
|--- |--- |--- |
| bc_id {Required} | string | Business Center ID. |
| catalog_id {Required} | string | Catalog ID. |
| feed_log_id {Required} | string | Product handling ID. |
| language | string | Supported languages.<ul><li>`en`: English</li><li>`zh-CN`: Chinese</li><li>`ja-JP`: Japanese</li><li>`de`: German</li><li>`es`: Spanish</li><li>`fr`: French</li><li>`hi-IN`: Hindi (India)</li><li>`id`: Indonesian</li><li>`it`: Italian</li><li>`ko`: Korean</li><li>`ms`: Malay</li><li> `ru`: Russian</li><li>`th`: Thai</li><li>`tr`: Turkish</li><li>`vi`: Vietnamese</li></ul><br>Default value: `en`.|
```

### Example

```xcodeblock
(code curl http)

    curl -X GET -H "Access-Token:YOUR_ACCESS_TOKEN" \
    -H "Content-Type:application/json" \
    -d '{\"bc_id\": BC_ID,\"catalog_id\": CATALOG_ID,\"feed_log_id\": FEED_LOG_ID,\"language\": \"LANGUAGE\"}' \
    https://business-api.tiktok.com/open_api/v1.3/catalog/product/log/

(/code)
```

## Response

``` xtable
| Field {35%}| Type{15%} | Description{50%} |
|--- |--- |--- |
| code | number  |Return code, see [Appendix - Return code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message |string|Return message, see [Appendix-Return message](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
| request_id | string | The log id of a request, which uniquely identifies the request. |
| data |object| Returned data. |
#| product_feed_log | object | Product handling results. |
##| catalog_id | string | Catalog ID. |
##| feed_id|string|Feeds ID.|
##| add_count | number | Total number of new items. |
##| update_count | number | Total number of items modified. |
##| delete_count | number | Total number of items deleted. |
##| error_count | number | Number of errors.|
##| warn_count | number | Number of warnings.|
##| process_status | string | Processing status.<br><br>Enum values:<ul><li>`WAITING`: On hold and waiting for other tasks to complete. </li><li>`PROCESSING`: In progress. </li><li> `SUCCESS`: Succeeded.</li><li>`FAILED`: Failed.</li></ul> |
##| update_mode | number | Update mode.<br><br>Enum values:<ul><li>`INCREASE`: incremental.</li><li>`REPLACE`: full replacement.</li></ul>|
##| start_time | string | Start time. |
##| end_time | string | End time. |
##| feed_log_data | object | Detailed processing information. |
###| download_path | object | File download address with all error/warning messages, key is language, value is file address. |
###| error_affected_products | object[] | Error message. |
####| affected_product_count | number | Number of products affected. |
####| affected_product_item_list | object[] | List of affected items, showing only the first five items. |
#####| index | number | Index that identifies the position of the product among the products you uploaded.<br>For example, an index of `2` represents the first product you uploaded through [/catalog/product/file/](https://business-api.tiktok.com/portal/docs?id=1740496787164161) or [/catalog/product/upload/](https://business-api.tiktok.com/portal/docs?id=1740497429681153). |
#####| title | string |Returned only for products in E-commerce catalogs, entertainment catalogs, Auto-Inventory catalogs, Auto-Model catalogs, or mini series catalogs.<br><br>The title of the E-commerce, entertainment, Auto-Inventory catalogs, or Auto-Model product, or the name of the short drama series.|
#####|sku_id|string| Returned only for products in E-commerce catalogs.<br><br>A unique ID for the product, such as a SKU.|
#####|hotel_id|string|Returned only for products in hotel catalogs.<br><br> A unique ID for the hotel.|
#####|name|string|Returned only for products in hotel catalogs or destination catalogs.<br><br>The name of the hotel or the destination.|
#####|flight_id|string|Returned only for products in flight catalogs.<br><br>The unique ID specified for the flight.|
#####|airline_company|string|Returned only for products in flight catalogs.<br><br>The name of the airline company operating the flight.|
#####|media_title_id|string|Returned only for products in entertainment catalogs.<br><br>A unique ID for the media title item.|
#####| vehicle_id | string | Returned only for products in Auto-Inventory or Auto-Model catalogs.<br><br>A unique ID for the vehicle. |
#####| series_id | string | Returned only for products in mini series catalogs.<br><br>A unique self-defined ID for the short drama series.|
#####| make | string | Returned only for products in Auto-Inventory or Auto-Model catalogs.<br><br> The make (brand) of the vehicle.|
#####| product_url | string | Product link. |
#####| description | string | Product description. |
#####| value | string | Field value. |
####| field | string | The field with issue. |
####| issue | string | Issue details. |
####| suggestion | string |Suggestions for solving the issue. |
###| warn_affected_products | object[] | Warning message. |
####| affected_product_count | number | Number of products affected. |
####| affected_product_item_list | object[] | List of affected items, showing only the first five items. |
#####| index | number | Index that identifies the position of the product among the products you uploaded.<br>For example, an index of `2` represents the first product you uploaded through [/catalog/product/file/](https://business-api.tiktok.com/portal/docs?id=1740496787164161) or [/catalog/product/upload/](https://business-api.tiktok.com/portal/docs?id=1740497429681153). |
#####| title | string |Returned only for products in E-commerce catalogs, entertainment catalogs, Auto-Inventory catalogs, Auto-Model catalogs, or mini series catalogs.<br><br>The title of the E-commerce, entertainment, Auto-Inventory catalogs, or Auto-Model product, or the name of the short drama series.|
#####|sku_id|string| Returned only for products in E-commerce catalogs.<br><br>A unique ID for the product, such as a SKU.|
#####|hotel_id|string|Returned only for products in hotel catalogs.<br><br> A unique ID for the hotel.|
#####|name|string|Returned only for products in hotel catalogs or destination catalogs.<br><br>The name of the hotel or the destination.|
#####|flight_id|string|Returned only for products in flight catalogs.<br><br>The unique ID specified for the flight.|
#####|airline_company|string|Returned only for products in flight catalogs.<br><br>The name of the airline company operating the flight.|
#####|media_title_id|string|Returned only for products in entertainment catalogs.<br><br>A unique ID for the media title item.|
#####| description | string | Product description. |
#####| vehicle_id | string | Returned only for products in Auto-Inventory or Auto-Model catalogs.<br><br>A unique ID for the vehicle. |
#####| series_id | string | Returned only for products in mini series catalogs.<br><br>A unique self-defined ID for the short drama series.|
#####| make | string | Returned only for products in Auto-Inventory or Auto-Model catalogs.<br><br> The make (brand) of the vehicle.|
#####| product_url | string | Product link. |
#####| value | string | Field value. |
####| field | string |  The field with issue. |
####| issue | string |  Issue details. |
####| suggestion | string | Suggestions for solving the issue. |
```

### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
"message": "OK",
"code": 0,
"data": {
    "product_feed_log": {
        "process_status": "SUCCESS",
        "update_mode": "INCREASE",
        "error_count": 0,
        "add_count": 0,
        "feeds_id": 2335,
        "warn_count": 1,
        "delete_count": 0,
        "feed_log_data": {
            "error_affected_products": null,
            "warn_affected_products": [
                {
                    "suggestion": "The \"age_group\" field is using an invalid value. Supported values only include: newborn/infant/toddler/kids/adult",
                    "issue": "Invalid value: age_group",
                    "field": "age_group",
                    "affected_product_count": 1,
                    "affected_product_item_list": [
                        {
                            "index": 3,
                            "sku_id": "tiktok_item_12424",
                            "description": "qgs test product description",
                            "title": "qgs test product",
                            "value": "None",
                            "product_url": "https://ads.tiktok.com"
                        }
                    ]
                },
                {
                    "suggestion": "The \"gender\" field is using an invalid value. Supported values only include: male/female/unisex",
                    "issue": "Invalid value: gender",
                    "field": "gender",
                    "affected_product_count": 1,
                    "affected_product_item_list": [
                        {
                            "index": 3,
                            "sku_id": "tiktok_item_12424",
                            "description": "qgs test product description",
                            "title": "qgs test product",
                            "value": "None",
                            "product_url": "https://ads.tiktok.com"
                        }
                    ]
                }
            ],
            "download_path": {
                "ru": "http://tosv.boei18n.byted.org/obj/dpafeed/6865965734837634822_73472_ru.csv",
                "fr": "http://tosv.boei18n.byted.org/obj/dpafeed/6865965734837634822_73472_fr.csv",
                "en": "http://tosv.boei18n.byted.org/obj/dpafeed/6865965734837634822_73472_en.csv",
                "ms": "http://tosv.boei18n.byted.org/obj/dpafeed/6865965734837634822_73472_ms.csv",
                "ko": "http://tosv.boei18n.byted.org/obj/dpafeed/6865965734837634822_73472_ko.csv",
                "vi": "http://tosv.boei18n.byted.org/obj/dpafeed/6865965734837634822_73472_vi.csv",
                "de": "http://tosv.boei18n.byted.org/obj/dpafeed/6865965734837634822_73472_de.csv",
                "tr": "http://tosv.boei18n.byted.org/obj/dpafeed/6865965734837634822_73472_tr.csv",
                "it": "http://tosv.boei18n.byted.org/obj/dpafeed/6865965734837634822_73472_it.csv",
                "zh-CN": "http://tosv.boei18n.byted.org/obj/dpafeed/6865965734837634822_73472_zh-CN.csv",
                "hi-IN": "http://tosv.boei18n.byted.org/obj/dpafeed/6865965734837634822_73472_hi-IN.csv",
                "th": "http://tosv.boei18n.byted.org/obj/dpafeed/6865965734837634822_73472_th.csv",
                "ja-JP": "http://tosv.boei18n.byted.org/obj/dpafeed/6865965734837634822_73472_ja-JP.csv",
                "id": "http://tosv.boei18n.byted.org/obj/dpafeed/6865965734837634822_73472_id.csv",
                "es": "http://tosv.boei18n.byted.org/obj/dpafeed/6865965734837634822_73472_es.csv"
            }
        },
        "catalog_id": 6865965734837634822,
        "update_count": 2,
        "start_time": "2020-09-14 05:15:09",
        "end_time": "2020-09-14 05:15:09"
    }
},
"request_id": "2020091406394701023125321410044"
}
(/code);
```
