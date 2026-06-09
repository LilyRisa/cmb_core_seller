---
title: "Get product sets"
doc_id: 1740570556295169
path: "API Reference / Catalog Product Sets / Get product sets"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get product sets

Use this endpoint to get a list of product sets or one specified product set in a catalog under your Business Center.

## Comparing v1.2 and v1.3
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/catalog/set/get/| /v1.3/catalog/set/get/|
|Request parameter data type |`bc_id`: number<br>`catalog_id`: number|`bc_id`: string<br>`catalog_id`: string|
|Request parameter name and data type |`set_id`: number|`product_set_id`: string|
|New request parameters| / |`return_product_count`|
|Response parameter name|`count`<br>`name`|`product_count`<br>`product_set_name`|
|Response parameter data type|`catalog_id`: number|`catalog_id`: string|
|Request parameter name and data type |`id`: number|`product_set_id`: string|
```
## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/catalog/set/get/

**Method** GET

**Header**

```xtable
|Field{30%}|Type{15%}|Description{55%}|
|--- |--- |--- |
|Access-Token{Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

```xtable
|Field{30%}|Type{15%}|Description{55%}|
|---|---|---|
| bc_id {Required} | string | Business Center ID.<br><br>To obtain the list of Business Centers that you can access, use [/bc/get/](https://business-api.tiktok.com/portal/docs?id=1737115687501826). |
| catalog_id {Required} | string | The ID of the catalog that you want to get product sets from. <br><br>To obtain the list of catalogs that you can access, use [/bc/asset/get/](https://business-api.tiktok.com/portal/docs?id=1739432717798401) and set `asset_type` to `CATALOG`. |
| product_set_id | string | The ID of the product set that you want to get.<br><br><ul><li>If this field is not passed, the system will return the complete list of product sets associated with the catalog (`catalog_id`).</li><li>If this field is passed, the system will only return the specified product set. </li></ul> |
| return_product_count | boolean | Whether to return the response parameter `product_count`. <br><br>Supported values: `true`, `false`. <br>Default value: `true`. <br><br><p><span style="color:darkred"><b>Note</b></span>: For product sets with too many products, returning `product_count` might trigger timeout errors. To avoid timeout issues in such cases, set this field to `false`. |
```

### Example

```xcodeblock
(code curl http)

curl -X GET -H "Access-Token:YOUR_ACCESS_TOKEN" \
--data-urlencode "bc_id=BC_ID" \
--data-urlencode "catalog_id=CATALOG_ID" \
--data-urlencode "product_set_id=SET_ID" \

https://business-api.tiktok.com/open_api/v1.3/catalog/set/get/
(/code)
```

## Response

```xtable
|Field{30%}|Type{15%}|Description{55%}|
|--- |--- |--- |
|code|number|The response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message|string|The response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id|string|The request ID.|
|data|object|The returned data.|
#|list|object[]|A list of product sets. |
##|product_count|number|Returned only when `return_product_count` is `true` or not passed in the request. <br><br>Number of products in this product set.|
##|product_set_id|string|The product set ID.|
##|catalog_id|string|The catalog ID.|
##|product_set_name|string|The name of the product set.|
##|conditions|object|The filter conditions.|
###|and/or|object[]|The logical operator that will be applied to multiple filter conditions. You must specify either `and` or `or`. `and` stands for the AND logical operator. `or` stands for the OR logical operator.|
####|field|string|The field that the filter will be applied to. For enum values, see [Appendix - Product Set - Filter fields](https://ads.tiktok.com/marketing_api/docs?id=1740673108747265).|
####|operation|string|The filter operator. For enum values, see [Appendix - Product Set - Operators](https://ads.tiktok.com/marketing_api/docs?id=1740673108747265).|
####|value|string/string[]|The expected value.|
```

### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {
        "list": [
            {                
				"product_set_id": "1767",
				"product_count": 8,
                "name": "test_create",
                "catalog_id": "6873077759237687045",
                "conditions": {
                    "or": [
                        {
                            "field": "price",
                            "operation": "LT",
                            "value": "250"
                        },
                        {
                            "field": "sale_price_effective_start_date",
                            "operation": "GTE",
                            "value": "2021-03-04 12:00:00"
                        }
                    ]
                }
            }
        ]
    },
    "request_id": "2021030908581701023102608005883C"
}
(/code);
```
