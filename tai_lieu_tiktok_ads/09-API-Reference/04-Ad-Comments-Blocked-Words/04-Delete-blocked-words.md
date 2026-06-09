---
title: "Delete blocked words"
doc_id: 1739029277346817
path: "API Reference / Ad Comments - Blocked Words / Delete blocked words"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Delete blocked words

Use this endpoint to delete one or more blocked words from the blocked word list. 

When a blocked word is deleted, comments that contain this word will become public instead of being visible only to the commentor. 

## Comparing v1.2 and v1.3 
The following table outlines the differences between v1.2 and v1.3 endpoints. 
```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/blockedword/delete/| /v1.3/blockedword/delete/|
|Request parameter data type|`advertiser_id`: number |`advertiser_id`: string |
```

## Request

**Endpoint**
https://business-api.tiktok.com/open_api/v1.3/blockedword/delete/

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
|advertiser_id {required}|string| Advertiser ID |
|blocked_words {required}|string[]| List of blocked words that you want to delete. <br><p><span style="color:darkred"><b>Note</b></span>: If you attempt to delete a word that is not on the blocked word list, no errors will occur. |
```
### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/blockedword/delete/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data-raw '{
  "advertiser_id": "{{advertiser_id}}",
  "blocked_words": [
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
|request_id |string|Unique ID of the request |
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
