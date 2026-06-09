---
title: "Update a blocked word"
doc_id: 1739029234526209
path: "API Reference / Ad Comments - Blocked Words / Update a blocked word"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Update a blocked word

Use this endpoint to replace a blocked word with another word.

## Comparing v1.2 and v1.3 
The following table outlines the differences between v1.2 and v1.3 endpoints. 
```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/blockedword/update/| /v1.3/blockedword/update/|
|Request parameter data type|`advertiser_id`: number |`advertiser_id`: string |
```

## Request

**Endpoint**
https://business-api.tiktok.com/open_api/v1.3/blockedword/update/

**Method** POST

**Header**

```xtable
|Field|Type|Description|
|---|---|---|
|Access-Token {required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162). |
|Content-Type {required}|string| Content type of the request. Allowed value: `application/json`|
```

**Parameters**

```xtable
|Field|Type|Description|
|---|---|---|
|advertiser_id {required}|string| Advertiser ID. |
|old_word {required}|string| Old blocked word. |
|new_word {required}|string| New blocked word. |
```
### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/blockedword/update/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data-raw '{
  "advertiser_id": "{{advertiser_id}}",
  "old_word": "hell",
  "new_word": "death"
}'
(/code)
```
## Response

```xtable
|Field|Type|Description|
|---|---|---|
|message|string|The return message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|code |number|The return code. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|request_id |string|Unique ID of the request. |
```

### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "request_id": "xxx"
}
(/code);
```
