---
title: "Bind/Unbind rules"
doc_id: 1738769164954626
path: "API Reference / Automated Rules / Bind/Unbind rules"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Bind/Unbind rules

Use this endpoint to bind objects to an existing rule, or unbind objects from a rule that they were previously bound to. 


## Comparing v1.2 and v1.3
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/optimizer/rule/batch_bind/| /v1.3/optimizer/rule/batch_bind/|
|Request parameter data type |`advertiser_id`: number <br>`rule_id`: number<br>`dimensions_id`: number []|`advertiser_id`: string <br>`rule_id`: string <br>` dimensions_id`: string[] |
```

## Request

**Endpoint** <https://business-api.tiktok.com/open_api/v1.3/optimizer/rule/batch_bind/>

**Method** POST

**Header**

```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type<br>Allowed format: `"application/json"`.  |
```

**Parameters**

```xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id {Required}|string| Advertiser ID. |
|bind_info {required}| object[]|Information about binding.|
#|rule_id {required}|string| ID of the rule that you want to bind objects to.|
#|dimension {required}|string| Category of the object. Enum values: `CAMPAIGN`, `ADGROUP`, `AD`.|
#|dimension_ids {required}|string[]| IDs of the objects that you want to bind to the rule.|
#|bind_type {required}|string| Whether you want to bind objects to a rule or unbind objects from a rule. Enum values: `BIND`, `UNBIND`.|
|lang|string|Language of the returned messages. Enum values: `ZH` (Simplified Chinese), `EN` (English)(default), `JA` (Japanese).|
```
### Example

```xcodeblock
(code JSON JSON)
{
    "advertiser_id": 75960823018,
    "bind_info":[
        {
            "rule_id":6984697088064290818,
            "dimension":"CAMPAIGN",
            "dimension_ids":[123456],
            "bind_type":"BIND"
        }
    ],
    "lang":"EN"
}
(/code)
```

## Response

```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id|string| Unique ID of the request|
```

### Example

```xcodeblock
(code JSON JSON)
{
    "code": 0,
    "message": "OK",
    "request_id": "202107290700180102440530181709ABE4",
    "data": {}
}
(/code)
```

