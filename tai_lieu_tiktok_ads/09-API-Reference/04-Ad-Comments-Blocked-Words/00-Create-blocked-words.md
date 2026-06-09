---
title: "Create blocked words"
doc_id: 1739029218470913
path: "API Reference / Ad Comments - Blocked Words / Create blocked words"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create blocked words

Use this endpoint to add words to the blocked word list. 

Each ad account can have up to 500 blocked words.

## Comparing v1.2 and v1.3 
The following table outlines the differences between v1.2 and v1.3 endpoints. 
```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/blockedword/create/| /v1.3/blockedword/create/|
|Request parameter data type|`advertiser_id`: number |`advertiser_id`: string |
```

## Request

**Endpoint**
https://business-api.tiktok.com/open_api/v1.3/blockedword/create/

**Method** POST

**Header**

```xtable
|Field|Type|Description|
|---|---|---|
|Access-Token {required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {required}|string| Content type of the request. Allowed value: `application/json`|
```

**Parameters**

```xtable
|Field|Type|Description|
|---|---|---|
|advertiser_id {required}|string| The Advertiser ID. |
|blocked_words {required}|string[]| List of words that you want to block. |
```
### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/blockedword/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data-raw '{
  "advertiser_id": "{{advertiser_id}}",
  "blocked_words": [
    "bad TikTok",
    "hell"
  ]
}'
(/code)
```

## Response

```xtable
|Field|Type|Description|
|---|---|---|
|message|string|The return message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|code |number|The return code. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|data |object|The returned data|
|request_id |string|Unique ID of the request  |
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

