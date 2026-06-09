---
title: "Delete audiences"
doc_id: 1739940539757569
path: "API Reference / Audience / Delete audiences"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Delete audiences

Use this endpoint to delete audiences.

 ## Comparing v1.2 and v1.3
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/dmp/custom_audience/delete/|/v1.3/dmp/custom_audience/delete/|
|Request parameter data type |`advertiser_id`: number<br>`custom_audience_ids`: number[]|`advertiser_id`: string<br>`custom_audience_ids`: string[]|

```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/dmp/custom_audience/delete/

**Method** POST

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type.<br>Allowed format: `"application/json"`.|
```

**Parameters**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id {Required}|string| Advertiser ID. |
|custom_audience_ids {Required}|string[]|A list of Audience IDs. Length range is `[1, 100]`.|
```

### Example

``` xcodeblock
(code curl http)
curl -H "Access-Token:xxx" -H "Content-Type:application/json" -X POST \\
-d '{
    "advertiser_id": "ADVERTISER_ID", 
    "custom_audience_ids": [
        "CUSTOM_AUDIENCE_IDS"
    ]
}' \\
https://business-api.tiktok.com/open_api/v1.3/dmp/custom_audience/delete/
(/code)
```

## Response

``` xtable
|Field|Data Type|Description |
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object|Returned data.|
|request_id |string|The log id of a request, which uniquely identifies the request.|
```

### Example

``` xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {}
}
(/code);
```
