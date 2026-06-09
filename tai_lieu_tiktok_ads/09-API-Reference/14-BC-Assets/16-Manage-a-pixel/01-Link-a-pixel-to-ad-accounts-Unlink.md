---
title: "Link a pixel to ad accounts / Unlink"
doc_id: 1753994950327297
path: "API Reference / BC Assets / Manage a pixel / Link a pixel to ad accounts / Unlink"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Link a pixel to ad accounts / Unlink

Use this endpoint to link a pixel to ad accounts, or to unlink a pixel from ad accounts.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/bc/pixel/link/update/|/v1.3/bc/pixel/link/update/|
|Request parameter data type|`bc_id`: number <br>`advertiser_ids`: number[]|`bc_id`: string <br>`advertiser_ids`: string[]|
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/bc/pixel/link/update/

**Method** POST

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|bc_id {Required}|string| Business Center ID |
|pixel_code {Required}|string|Pixel Code|
|advertiser_ids {Required}|string[]|Advertiser ID list|
|relation_status {Required}|string|Enum values: `LINK`, `UNLINK`.|
```
### Example
```xcodeblock
(code curl http)
curl --location --request POST https://business-api.tiktok.com/open_api/v1.3/bc/pixel/link/update/
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data-raw '{
    "bc_id": "{{bc_id}}",
    "pixel_code": "{{pixel_code}}",
    "advertiser_ids": ["{{advertiser_id}}"],
    "relation_status": "LINK"
}'
(/code)
```
## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object|Returned data.|
|request_id |string|The log id of the request, which uniquely identifies a request  |
```
### Example
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 40104,
    "message": "The access_token is empty.",
    "request_id": "{{request_id}}"
}
(/code)
```

