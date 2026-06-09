---
title: "Get qualifications within a Business Center"
doc_id: 1770118680584194
path: "API Reference / BC Assets / Get qualifications within a Business Center"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get qualifications within a Business Center

Use this endpoint to retrieve existing qualifications within a Business Center. 

Once you obtain the `qualification_id` from the response, you can reuse the qualification for ad account creation by passing it to the `qualification_id` parameter in [/bc/advertiser/create/](https://business-api.tiktok.com/portal/docs?id=1739939020318721).

## Before you start
- Ensure that you have created an ad account using [/bc/advertiser/create/](https://business-api.tiktok.com/portal/docs?id=1739939020318721) by specifying `qualification_info`. The specified `qualification_info` will be used to generate a `qualification_id`.  If you do not specify the `qualification_info` when you create an ad account, you cannot get `qualification_id` for reuse.
- Ensure that you have Admin access to the Business Center.

## Request
**Endpoint** https://business-api.tiktok.com/open_api/v1.3/bc/advertiser/qualification/get/

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
| bc_id {Required}| string | Business Center ID.<br><br> To obtain the list of Business Centers that you have access to, use [/bc/get/](https://business-api.tiktok.com/portal/docs?id=1737115687501826). |
|filtering|object|Filtering conditions. |
#|verified|boolean|Whether the qualifications are verified or not. |
|page|int|Current number of pages. Default value: `1`. Value range : ≥ 1.|
|page_size|int|Page size. Default value: `10`. Value range: 1-100.|
```
### Example
```xcodeblock
(code curl http)
curl --location -g --request GET 'https://business-api.tiktok.com/open_api/v1.3/bc/advertiser/qualification/get/?bc_id={{bc_id}}&filtering={"verified": true}' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

## Response
``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|message |string|Response message. For details, see [Appendix-Return codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|data |object|Return data.|
#|qualifications|object[]|Qualification information. |
##|qualification_id|string|Qualification id.|
##|company_name|string|Company name.|
##|status|string|Qualifications verification status. Enum values：`VERIFIED` , `UNVERIFIED`.|
##|owner_advertiser_id|string|Advertiser ID of the qualifications owner.|
##|linked_advertiser_count|int|Number of linked advertiser accounts.|
##|region_code|string|Country or region code. For example, "DE".|
#|page_info|object| Pagination information|
##|page|number| Current page number|
##|page_size| number | Page size|
##|total_number| number | Total number of results |
##|total_page| number | Total pages of results|
```
### Example
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "page_info": {
            "page_size": 10,
            "page": 1,
            "total_page": 1,
            "total_number": 4
        },
        "qualifications": [
            {
                "status": "VERIFIED",
                "region_code": "US",
                "linked_advertiser_count": 1,
                "qualification_id": "{{qualification_id}}",
                "owner_advertiser_id": "{{owner_advertiser_id}}",
                "company_name": "{{company_name}}"
            },
            {
                "status": "VERIFIED",
                "region_code": "US",
                "linked_advertiser_count": 3,
                "qualification_id": "{{qualification_id}}",
                "owner_advertiser_id": "{{owner_advertiser_id}}",
                "company_name": "{{company_name}}"
            },
            {
                "status": "VERIFIED",
                "region_code": "US",
                "linked_advertiser_count": 1,
                "qualification_id": "{{qualification_id}}",
                "owner_advertiser_id": "{{owner_advertiser_id}}",
                "company_name": "{{company_name}}"
            },
            {
                "status": "VERIFIED",
                "region_code": "US",
                "linked_advertiser_count": 1,
                "qualification_id": "{{qualification_id}}",
                "owner_advertiser_id": "{{owner_advertiser_id}}",
                "company_name": "{{company_name}}"
            }
        ]
    }
}
(/code)
```

