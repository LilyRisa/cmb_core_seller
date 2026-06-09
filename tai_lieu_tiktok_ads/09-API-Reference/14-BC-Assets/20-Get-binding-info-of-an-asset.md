---
title: "Get binding info of an asset"
doc_id: 1739659584022529
path: "API Reference / BC Assets / Get binding info of an asset"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get binding info of an asset

Use this endpoint to check how many ads an asset has been bound to, and how many more ads this asset can be bound to. Each asset can only be used to create a certain number of ads. 

## Comparing v1.2 and v1.3 
The following table outlines the differences between v1.2 and v1.3 endpoints. 
```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/asset/ad/bind_info/| /v1.3/asset/bind/quota/|
|Request parameter data type|`advertiser_id`: number|`advertiser_id`: string |
```


## Request

**Endpoint**

https://business-api.tiktok.com/open_api/v1.3/asset/bind/quota/

**Method** GET

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
|advertiser_id {Required}|string| Advertiser ID. |
|asset_id {Required}|string|Asset ID. |
|asset_type {Required}|string|Enum values: `IDENTITY`. |
```

## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object|Returned data.|
#|total_quota|number|Total number of ads an asset can bind to. |
#|used_quota|number|Number of ads an asset has bound to. |
#|available_quota|number|Number of ads an asset is available to bind to. |
|request_id |string|The log id of the request, which uniquely identifies a request  |
```
