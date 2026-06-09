---
title: "Get the overview of a catalog"
doc_id: 1740492470201345
path: "API Reference / Catalog Management / Get the overview of a catalog"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the overview of a catalog

Use this endpoint to get the number of products in different audit statuses (approved, rejected, and processing) in a catalog.

## Comparing v1.2 and v1.3
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/catalog/overview/| /v1.3/catalog/overview/|
|Request parameter data type |`bc_id`: number<br>`catalog_id`: number |`bc_id`: string<br>`catalog_id`: string |
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/catalog/overview/

**Method** GET

**Header**

```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162). |
```

**Parameters**

```xtable
|Field|Data Type|Description|
|---|---|---|
|bc_id {Required}|string| Business Center ID.|
|catalog_id {Required}|string|Catalog ID.|
```

## Response

```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|The return code. For the complete list of error codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|The return message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object|Returned data.|
#|approved|number| Number of approved products in the catalog.|
#|rejected|number| Number of rejected products in the catalog.|
#|processing|number| Number of processing products in the catalog.|
#|organic_approved|number| Number of approved products bound with TikTok Shopping.|
#|organic_rejected|number| Number of rejected products bound with TikTok Shopping.|
#|organic_processing|number| Number of processing products bound with TikTok Shopping.|
|request_id |string|The log id of a request, which uniquely identifies the request.|
```

### Example

```xcodeblock
(code JSON json)
{
    "code": 0,
    "message": "OK",
    "request_id": "202106290307060101152311200A00E3E8",
    "data": {
        "approved": 1,
        "rejected": 3,
        "processing": 38
    }
}
(/code),
```
