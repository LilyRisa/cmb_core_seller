---
title: "Refresh a lookalike audience"
doc_id: 1758616888158209
path: "API Reference / Audience / Refresh a lookalike audience"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Refresh a lookalike audience

Use this endpoint to manually refresh a Lookalike Audience.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/dmp/custom_audience/lookalike/update/

**Method** POST

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type. Allowed format: `"application/json"`.|
```

**Parameters**

```xtable
|Field{35%}|Data Type{15%}|Description{50%}|
|---|---|---|
|advertiser_id {Required}|string|Advertiser ID.|
|custom_audience_ids{Required} |string[]|A list of custom audience IDs. 
```

### Example
```xcodeblock
(code)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/dmp/custom_audience/lookalike/update' \
--header 'Access-Token: 123' \
--header 'Content-Type: application/json' \
--data-raw '{
    "advertiser_id": "123", 
    "custom_audience_ids": ["123"]
}'
(/code)
```

## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|data |object|Returned Data. |
|request_id |string|The log ID of a request, which uniquely identifies the request.|
```

### Example

``` xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "123",
    "data": {}
}
(/code);
```


