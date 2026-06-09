---
title: "Sign Terms"
doc_id: 1737192284435457
path: "API Reference / Terms / Sign Terms"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Sign Terms

Use this endpoint to sign the agreement for the Lead Generation Ads feature. 

After signing an agreement, you can use [/term/check/](https://ads.tiktok.com/marketing_api/docs?id=1737192304571394) to check the status of the agreement.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Change{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/term/confirm/|/v1.3/term/confirm/|
|Request parameter data type|`advertiser_id`: number|`advertiser_id`: string|
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/term/confirm/

**Method** POST

**Header**
```xtable
|Field|Data Type|Desciption|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type. Allowed format: `"application/json"`.  |
```

**Parameters**
```xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id {Required}|string|Advertiser ID.|
|term_type {Required}|string|Agreement type. Enum values: `LeadAds`. |
```

### Example
```xcodeblock
(code curl http)
curl -H "Access-Token:xxx" -H "Content-Type:application/json" -X POST \
-d '{
    "advertiser_id": "ADVERTISER_ID",
    "term_type": "TERM_TYPE"
}' \
https://business-api.tiktok.com/open_api/v1.3/term/confirm/
(/code)
```

## Response
```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Return code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|message |string|Return messages. For details, see [Appendix-Return information](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|data |object|Return data (Empty).|
|request_id |string|The log id of a request, which uniquely identifies the request.|
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
