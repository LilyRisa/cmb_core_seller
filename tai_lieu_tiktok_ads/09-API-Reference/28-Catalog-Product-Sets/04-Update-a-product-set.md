---
title: "Update a product set"
doc_id: 1740572974725122
path: "API Reference / Catalog Product Sets / Update a product set"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Update a product set

Use this endpoint to update the filter conditions or name of a product set. You only need to include the fields that you want to update the values for.

## Comparing v1.2 and v1.3
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/catalog/set/update/| /v1.3/catalog/set/update/|
|Request parameter name |`name`|`product_set_name`|
|Request parameter data type |`bc_id`: number<br>`catalog_id`: number|`bc_id`: string<br>`catalog_id`: string|
|Request parameter name and data type |`set_id`: number|`product_set_id`: string|
|Response parameter name|`name`<br>`count`|`product_set_name`<br>`product_count`|
|Response parameter name and data type|`id`: number|`product_set_id`: string|
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/catalog/set/update/

**Method** POST


**Header**

```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type.<br>Allowed format: `"application/json"`.  |
```

**Parameters**

```xtable
|Field{30%}|Type{15%}|Description{55%}|
|--- |--- |--- |
|bc_id {Required}|string|Business Center ID.|
|catalog_id {Required}|string|The catalog ID.|
|product_set_id {Required}|string| The product set ID. |
|product_set_name{+Conditional}|string| You need to pass at least `product_set_name` or `conditions` or both.<br> <br>The product set name. It must be less than 28 characters.|
|conditions{+Conditional}|object|You need to pass at least `product_set_name` or `conditions` or both.<br> <br>The filter conditions.<br><br><p><span style="color:darkred"><b>Note</b></span>: If you pass multiple conditions through this object, we recommend limiting the combined total number of values in the conditions to a maximum of 1,024. Otherwise, a timeout is likely to occur.|
#|and/or{+Conditional}|object[]|Required when `conditions` is passed. <br> <br>The logical operator that will be applied to the filter conditions. You must specify either `and` or `or`. `and` stands for the AND logical operator. `or` stands for the OR logical operator.|
##|field{+Conditional}|string|Required when `conditions` is passed. <br> <br>The field that the filter will be applied to. For enum values, see [Product set operators and fields – Filter fields](https://ads.tiktok.com/marketing_api/docs?id=1740673108747265).|
##|operation{+Conditional}|string|Required when `conditions` is passed. <br> <br>The filter operator. For enum values, see [Product set operators and fields – Operators](https://ads.tiktok.com/marketing_api/docs?id=1740673108747265).|
##|value{+Conditional}|string/string[]|Required when `conditions` is passed. <br> <br>The expected value.<br><br>Depending on the operator you specify via `operation`, this field can be a single value or multiple values in a list. See [Product set operators and fields – Operators](https://business-api.tiktok.com/portal/docs?id=1740673108747265) to learn about the type of value you need to specify. <br><br><p><span style="color:darkred"><b>Note</b></span>: If you pass multiple values through this field, the maximum allowed number of values is 1,024.|
```

### Example-the object Conditions
```xcodeblock
(code conditions example)
    "conditions": {
        "and": [
            {
                "field": "gender",
                "operation": "EQUAL",
                "value": "MALE"
            },
            {
                "field": "price",
                "operation": "GTE",
                "value": 50
            },
            {
                "field": "brand",
                "operation": "I_CONTAIN",
                "value": ["TikTok", "xxxxx"]
            }
        ]
    }
(/code);
```

### Example

```xcodeblock
(code curl http)
curl -X POST -H "Access-Token:YOUR_ACCESS_TOKEN" \
-H "Content-Type:application/json" \
-d '{\"bc_id\": BC_ID,\"catalog_id\": CATALOG_ID,\"name\": NAME},\"conditions\": [CONDITIONS]}' \
https://business-api.tiktok.com/open_api/v1.3/catalog/set/update/
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
#|product_set_name|string|The product set name.|
#|product_count|number|The number of products in the product set.|
```

### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {
        "count": 20,
        "product_set_id": "1566",
        "name": "test_create"
    },
    "request_id": "20210228131918010231010121650793"
}
(/code);
```
