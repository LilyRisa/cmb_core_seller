---
title: "Get targeting hashtags by ID"
doc_id: 1736280889167874
path: "API Reference / Tools / Get targeting hashtags by ID"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get targeting hashtags by ID

Use this endpoint to get the targeting hashtag names and statuses by ID. 

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/tools/hashtag/get/|/v1.3/tool/hashtag/get/|
|Request parameter data type |`advertiser_id`: number|`advertiser_id`: string|
|Request parameter name and data type|`keyword_id`: number| `keyword_ids`: string[]|
|Request parameters deprecated in v1.3|/| The`keyword_query_pairs`object is removed from v1.3, and hence  `keyword_ids` is a separate parameter.|
|Resposne parameter data type |`keyword_id`: number|`keyword_id`: string|
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/tool/hashtag/get/

**Method** GET

**Header**

```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162). |
```

**Parameters**

```xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id {required}|string| Advertiser ID |
|keyword_ids {required}|string[]|List of keyword IDs|

```


### Example

```xcodeblock
(code curl http)
curl --location -g --request GET 'https://business-api.tiktok.com/open_api/v1.3/tool/hashtag/get/?keyword_ids=["3158447993006"]&advertiser_id=1234' \
--header 'Access-Token: 1234' \
(/code)
```

## Response

```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Response code. For the complete list of error codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id|string| The log ID of a request, which uniquely identifies the request.|
|data |object|Returned data|
#|keywords_status|object[]| Keyword information. |
##|keyword|string|Keyword|
##|keyword_id|string| Keyword ID|
##|keyword_status|string|Enum: `ONLINE` (available for use), `OFFLINE` (not available for use)|
```

### Example
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "1234",
    "data": {
        "keywords_status": [
            {
                "keyword": "#musicchallenge",
                "keyword_id": "2911052068069",
                "keyword_status": "ONLINE"
            }
        ]
    }
}
(/code);
```


