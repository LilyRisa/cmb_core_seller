---
title: "Get result details"
doc_id: 1738769098314754
path: "API Reference / Automated Rules / Get result details"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get result details

Use this endpoint to get rule execution results by execution IDs. 

## Comparing v1.2 and v1.3
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/optimizer/rule/result/get/| /v1.3/optimizer/rule/result/get/|
|Request parameter data type |`advertiser_id`: number<br>`exec_id`: number<br>`rule_id`: number |`advertiser_id`: string<br>`exec_id`: string<br>`rule_id`: string |
|Response parameter data type|`rule_id`: number<br>`exec_id`: number<br>` object_dimension_id`: number |`rule_id`: string<br>`exec_id`: string <br>` object_dimension_id`: string|
```

## Request

**Endpoint** <https://business-api.tiktok.com/open_api/v1.3/optimizer/rule/result/get/>

**Method** GET

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
|result_detail {required}|object[]| Result details.|
#|exec_id {required}|string| Rule execution ID.|
#|rule_id {required}|string| Rule ID.|
|lang|string|Language of the execution results. Enum values: `ZH` (Simplified Chinese), `EN` (English)(default), `JA` (Japanese).|
```
### Example

```xcodeblock
(code JSON JSON)
{
    "advertiser_id": 75960823018,
    "result_detail":[
        {
            "exec_id":6983900294136463361,
            "rule_id":6982553361971232769
        },
        {
            "exec_id":6983908036217667585,
            "rule_id":6982553361971232769
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
|request_id|string| Unique ID of the request.|
|data|object|Returned data.|
#|result_details|object[]|Result details.|
##|rule_id|string| Rule ID.|
##|exec_id|string| Rule execution ID.|
##|action_subject_type| string| Action type. Enum values: `TURN_ON`, `TURN_OFF`, `MESSAGE`.|
##|status|string|Action status. Enum values: `SUCCESS`, `FAIL`|
##|error_message|string| If the action failed, this field returns the error message.|
##|object_dimension|string| Category of the object. Enum values: `CAMPAIGN`, `ADGROUP`, `AD`.|
##|object_dimension_id|string|Object ID.|
##|object_name|string|Object name.|
```
### Example

```xcodeblock
(code JSON JSON)
{
    "code": 0,
    "message": "OK",
    "request_id": "2021072907015001024501602704097351",
    "data": {
        "result_details": [
            {
                "object_dimension_id": 1695172996977714,
                "rule_id": 6982553361971232769,
                "status": "SUCCESS",
                "object_name": "20210324192339",
                "action_subject_type": "MESSAGE",
                "exec_id": 6983900294136463361,
                "object_dimension": "CAMPAIGN"
            },
            {
                "object_dimension_id": 1695172996977714,
                "rule_id": 6982553361971232769,
                "status": "SUCCESS",
                "object_name": "20210324192339",
                "action_subject_type": "MESSAGE",
                "exec_id": 6983908036217667585,
                "object_dimension": "CAMPAIGN"
            }
        ]
    }
}
(/code)
```
