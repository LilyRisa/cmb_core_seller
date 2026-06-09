---
title: "Check the status of Terms"
doc_id: 1737192304571394
path: "API Reference / Terms / Check the status of Terms"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Check the status of Terms

Use this endpoint to check the status of an agreement.

If the agreement has been signed, you can get the agreement using [/term/get/](https://ads.tiktok.com/marketing_api/docs?id=1737191909315585).

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Change{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/term/check/|/v1.3/term/check/|
|Request parameter data type|`advertiser_id`: number|`advertiser_id`: string|
```

## Request
**Endpoint** https://business-api.tiktok.com/open_api/v1.3/term/check/

**Method** GET

**Header**
```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**
```xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id {Required}|string|Advertiser ID.|
|term_type {Required}|string|Type of agreement. Enum values: `LeadAds`. |
```

### Example
```xcodeblock
(code curl http)
curl --get -H "Access-Token:xxx" \
--data-urlencode "advertiser_id=< ADVERTISER_ID >" \
--data-urlencode "term_type=< TERM_TYPE >" \
https://business-api.tiktok.com/open_api/v1.3/term/check/
(/code)
```

## Response
```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Return code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Return messages. For details, see [Appendix-Return information](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|data |object|Returned data. |
#|confirmed |boolean|Signing status of the agreement. `True` means "Signed successfully ", <br>`False` means "Not signed". |
|request_id |string|The log id of a request, which uniquely identifies the request.|
```

### Example
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
  "message": "OK",
  "code": 0,
  "data": {
    "confirmed": true
  },
  "request_id": "2020022414253401011023224213472"
}
(/code);
```
