---
title: "Get products in a product set"
doc_id: 1740571478441986
path: "API Reference / Catalog Product Sets / Get products in a product set"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get products in a product set

Use this endpoint to get products in a product set.

## Comparing v1.2 and v1.3
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/catalog/set/info/| /v1.3/catalog/set/product/get/|
|Request parameter data type |`bc_id`: number<br>`catalog_id`: number|`bc_id`: string<br>`catalog_id`: string|
|Request parameter name and data type|`set_id`: number|`product_set_id`: string|
|Response parameter name|`count`<br>`list`|`product_count`<br>`products`|
|Response parameter data type|`catalog_id`: number<br>`product_id`: number|`catalog_id`: string<br>`product_id`: string|
|Response parameter name and data type|`id`: number|`product_set_id`: string|
|New response parameters|/|`hotel_id`<br> `flight_id`<br>`destination_id`<br>`vehicle_id`|
```


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/catalog/set/product/get/

**Method** GET

**Header**

```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type.<br>Allowed format: `"application/json"`.  |
```

**Parameters**

```xtable
|Field|Type|Description|
|--- |--- |--- |
|bc_id {Required}|string|Business Center ID.|
|catalog_id {Required}|string|The ID of the catalog that you want to get product sets from.|
|product_set_id {Required}|string|The ID of the product set that you want to get.|
|page|number|The number of the current page. The default value is 1.|
|page_size|number|The page size. The default size is 20.|
```

### Example

```xcodeblock
(code curl http)

curl -X GET -H "Access-Token:YOUR_ACCESS_TOKEN" \
-H "Content-Type:application/json" \
-d '{\"bc_id\": BC_ID,\"catalog_id\": CATALOG_ID,\"product_set_id\": SET_ID},\"page\": PAGE,\"page_size\": PAGE_SIZE' \
https://business-api.tiktok.com/open_api/v1.3/catalog/set/info/
(/code)
```

## Response

```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|The return code. For the complete list of error codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|The return message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id|string|The request ID.|
|data |object|Returned data.|
#|product_set_id|string|The product set ID.|
#|catalog_id|string|The catalog ID.|
#|product_count|name|The number of products in the product set.|
#|products|object[]|A list of products.|
##|product_id|number|The product ID.|
##|product_name|string|The product name.|
##|sku_id|string|Returned only for E-commerce catalog products.<br><br>A unique ID for the E-commerce product.|
##|hotel_id | string | Returned only for hotel catalog products.  <br><br>A unique ID for the hotel.   |
##| flight_id | string | Returned only for flight catalog products. <br><br> A unique ID for the flight.   |
##| destination_id | string | Returned only for destination catalog products.  <br><br> A unique ID for the destination.   |
##|vehicle_id|string|Returned only for products in an Auto-Inventory or Auto-Model catalog.<br><br>A unique ID for the vehicle.|
#|page_info|object|Pagination information.|
##|page|number|Current page number.|
##|page_size|number|Page size.|
##|total_number|number|Total number of results.|
##|total_page|number|Total number of pages.|

```

### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {
        "product_count": 2,
        "page_info": {
            "total_number": 2,
            "page": 1,
            "page_size": 20,
            "total_page": 1
        },
        "products": [
            {
                "sku_id": "test_2",
                "product_id": 6933990518728918789,
                "product_name": "pro_2"
            },
            {
                "sku_id": "test_1",
                "product_id": 6933990518728935173,
                "product_name": "pro_1"
            }
        ],
        "catalog_id": 6873077759237687045,
        "product_set_id": 1547
    },
    "request_id": "20210301065500010231016022235FAE"
}
(/code);
```

