---
title: "Transfer a pixel from advertiser to BC"
doc_id: 1753994928261122
path: "API Reference / BC Assets / Manage a pixel / Transfer a pixel from advertiser to BC"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Transfer a pixel from advertiser to BC

Use this endpoint to transfer a pixel to a BC. 

To successfully transfer a pixel, the following two requirements need to be met:
1. If the pixel is being transferred to an Agency BC, the transfer has to be done by the admin user of the Agency BC. Meanwhile, the ad account this pixel was originally tied to has to be owned by the Agency BC.
2. If the pixel is being transferred to a Direct BC, the Direct BC has to have admin permission of the ad account this pixel was originally tied to.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/bc/pixel/transfer/| /v1.3/bc/pixel/transfer/|
|Request parameter data type|`bc_id`: number |`bc_id`: string |
```

## Request
**Endpoint** https://business-api.tiktok.com/open_api/v1.3/bc/pixel/transfer/

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
|bc_id {Required}|string| The BC id that you would like to transfer the pixel to. |
|pixel_code {Required}|string|The pixel code that you would like to transfer to BC.|
```
### Example
```xcodeblock
(code curl http)
curl --location --request POST https://business-api.tiktok.com/open_api/open_api/v1.3/bc/pixel/transfer/
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data-raw '{
    "bc_id": "{{bc_id}}",
    "pixel_code": "{{pixel_code}}"
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
#|asset_id|string|Asset ID|
|request_id |string|The log id of the request, which uniquely identifies a request  |
```
### Example
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": "{{code}}",
    "message": "Success",
    "data": {
        "asset_id": "{{asset_id}}"
    },
    "request_id": "{{request_id}}"
}
(/code)
```
