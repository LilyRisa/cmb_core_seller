---
title: "Get blocked words"
doc_id: 1739029260837889
path: "API Reference / Ad Comments - Blocked Words / Get blocked words"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get blocked words

Use this endpoint to get the list of  blocked words for an ad account.

## Comparing v1.2 and v1.3 
The following table outlines the differences between v1.2 and v1.3 endpoints. 
```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/blockedword/list/| /v1.3/blockedword/list/|
|Request parameter data type|`advertiser_id`: number |`advertiser_id`: string |
|New request parameter |/ |`page_info`: object |
|Response parameter name|`content` |`bw_content` |
```

## Request

**Endpoint**
https://business-api.tiktok.com/open_api/v1.3/blockedword/list/

**Method** GET

**Header**

```xtable
|Field|Type|Description|
|---|---|---|
|Access-Token {required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

```xtable
|Field|Type|Description|
|---|---|---|
|advertiser_id {required}|string| Advertiser ID. |
|page_info|object| Pagination information. |
#|page_size |number|Page size. The default value: `10`. Value range: [1, 500].|
#|page |number|Current page number. The default value is `1`. The value must be ≥ 1. |
```

### Example

```xcodeblock
(code curl http)
curl --get -H "Access-Token:xxx" \
--data-urlencode "advertiser_id=ADVERTISER_ID" \
--data-urlencode "page_size=PAGE_SIZE" \
--data-urlencode "page=PAGE" \
https://business-api.tiktok.com/open_api/v1.3/blockedword/list/
(/code)
```

## Response

```xtable
|Field|Type|Description|
|---|---|---|
|message|string|The return message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|code |number|The return code. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|data |object|The returned data.|
#|list |object[]| List of blocked words. |
##|bw_content |string| Blocked word. |
#|page_info |object| Pagination information. |
##|page |number| Current page number. |
##|page_size |number| Page size. |
##|total_number |number| Total number of results.|
##|total_page |number| Total pages of results. |
|request_id |string| Unique ID of the request. |
```

### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {}
}
(/code);
```

