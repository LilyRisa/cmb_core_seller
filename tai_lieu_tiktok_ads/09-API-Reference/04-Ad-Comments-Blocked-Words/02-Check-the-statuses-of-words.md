---
title: "Check the statuses of words"
doc_id: 1739029247961089
path: "API Reference / Ad Comments - Blocked Words / Check the statuses of words"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Check the statuses of words

Use this endpoint to check if a list of words are blocked.

## Comparing v1.2 and v1.3 
The following table outlines the differences between v1.2 and v1.3 endpoints. 
```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/blockedword/check/| /v1.3/blockedword/check/|
|Request parameter data type|`advertiser_id`: number |`advertiser_id`: string |
|Response parameter name|`blocked_word` <br>`existed`|`word`<br>`blocked`|
```

## Request

**Endpoint**
https://business-api.tiktok.com/open_api/v1.3/blockedword/check/

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
|blocked_words {required}|string[]| List of words that you want to check. |
```

### Example

```xcodeblock
(code curl http)
curl --get -H "Access-Token:xxx" \
--data-urlencode "advertiser_id=ADVERTISER_ID" \
--data-urlencode "blocked_words=[\"BLOCKED_WORDS\"]" \
https://business-api.tiktok.com/open_api/v1.3/blockedword/check/
(/code)
```

## Response

```xtable
|Field|Type|Description|
|---|---|---|
|message|string|The return message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|code |number|The return code. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|data |object|Returned data.|
#|results |object[]| Results. |
##|word|string| Word being checked.|
##|blocked|boolean| Enum values: `True` (word is blocked), `False` (word is not blocked).|
|request_id |string|Unique ID of the request. |
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
