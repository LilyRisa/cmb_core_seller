---
title: "Update the statuses of rules"
doc_id: 1738769144119298
path: "API Reference / Automated Rules / Update the statuses of rules"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Update the statuses of rules

Use this endpoint to turn on, turn off, or delete a group of rules. If updating the status of one rule fails, the whole operation will fail. If it happens, no rules will be updated.



## Comparing v1.2 and v1.3
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/optimizer/rule/update/status/| /v1.3/optimizer/rule/update/status/|
|Request parameter data type |`advertiser_id`: number|`advertiser_id`: string |
```

## Request

**Endpoint** <https://business-api.tiktok.com/open_api/v1.3/optimizer/rule/update/status/>

**Method** POST

**Header**

```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {required}|string|Request message type<br>Allowed format: `"application/json"`.  |
```

**Parameters**

```xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id {required}|string| Advertiser ID. |
|rule_ids {required}|string[]| IDs of the rules that you want to update status.|
|operate_type {required}|string| Operation type. Enum values: `TURN_ON`, `TURN_OFF`, `DELETE`.|
|lang|string |Language of the response error message if there is an error occurred. Enum values: `ZH` (Simplified Chinese), `EN` (English)(default), `JA` (Japanese).|
```

### Example
```xcodeblock
(code JSON JSON)
{
    "advertiser_id": 75960823018,
    "rule_ids":[6984697088064290818],
    "operate_type":"TURN_ON"
}
(/code)
```

## Response

```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object|Returned data.|
#|rule_ids |string[]|IDs of the rules whose status have been successfully updated.|
|request_id|string| Unique ID of the request.|
```
### Example

```xcodeblock
(code JSON JSON)
{
    "code": 0,
    "message": "OK",
    "request_id": "202107290649000102450631992D08A251",
    "data": {
        "rule_ids": [
            6984697088064290818
        ]
    }
}
(/code)
```
