---
title: "Get ad accounts linked to a pixel"
doc_id: 1753994966965249
path: "API Reference / BC Assets / Manage a pixel / Get ad accounts linked to a pixel"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get ad accounts linked to a pixel

Use this endpoint to get a list of ad accounts that have been linked to the pixel that you specify in the request.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/bc/pixel/link/get/| /v1.3/bc/pixel/link/get/|
|Request parameter data type|`bc_id`: number |`bc_id`: string |
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/bc/pixel/link/get/

**Method**  GET

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
```
### Example
```xcodeblock
(code curl http)
curl --location --request GET https://business-api.tiktok.com/open_api/v1.3/bc/pixel/link/get?bc_id={{bc_id}}&pixel_code={{pixel_code}}
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json'
(/code)
```

## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object|Returned data.|
#|list|object[]|List of advertisers|
##|advertiser_id|string|Advertiser ID|
#|page_info|object| Pagination information.|
##|page|number| Current page number.|
##|page_size| number | Page size.|
##|total_number| number | Total number of results. |
##|total_page| number | Total pages of results.|
|request_id |string|The log id of the request, which uniquely identifies a request. |
```
### Example
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "Success",
    "data": {
        "list": [
            {
                "advertiser_id": "{{advertiser_id_1}}"
            },
            {
                "advertiser_id": "{{advertiser_id_2}}"
            }
        ],
        "page_info": {
            "page": 1,
            "page_size": 10,
            "total_number": 2,
            "total_page": 1
        }
    },
    "request_id": "{{request_id}}"
}
(/code)
```
