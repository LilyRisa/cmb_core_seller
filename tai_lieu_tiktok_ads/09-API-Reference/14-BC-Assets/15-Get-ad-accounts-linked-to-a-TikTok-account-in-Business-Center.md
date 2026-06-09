---
title: "Get ad accounts linked to a TikTok account in Business Center"
doc_id: 1855027294743554
path: "API Reference / BC Assets / Get ad accounts linked to a TikTok account in Business Center"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get ad accounts linked to a TikTok account in Business Center

Use this endpoint to retrieve the list of ad accounts linked to a TikTok account in a Business Center.

Once an ad account is linked to a TikTok account in a Business Center, Business Center members with access to the linked ad account can use the TikTok account for ad delivery from the ad account.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/bc/asset/advertiser/assigned/

**Method** GET

**Header**

```xtable
|Field{30%}|Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token.<br><br> For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1738373164380162).|
```

**Parameters**

```xtable
|Field{30%}|Type{15%}|Description{55%}|
|---|---|---|
|bc_id {Required}|string|Business Center ID.<br><br>To obtain the list of Business Centers that you can access, use [/bc/get/](https://business-api.tiktok.com/portal/docs?id=1737115687501826).|
|asset_id {Required}|string|The ID of a TikTok account in a Business Center.<br><br>To obtain the list of TikTok accounts within a Business Center, use [/bc/asset/get/](https://business-api.tiktok.com/portal/docs?id=1739432717798401) and set `asset_type` to `TT_ACCOUNT` or `MANAGED_BUSINESS_ACCOUNT`.|
|asset_type {Required}|string|Asset type.<br><br>Enum values:<ul><li>`TT_ACCOUNT`: TikTok account as an ad delivery asset. You can run ads from these TikTok accounts.</li><li>`MANAGED_BUSINESS_ACCOUNT`: TikTok account for Business Account management. You can run ads from these TikTok accounts, and manage messages, leads, forms, comments, product links, and insights in your TikTok account.</li></ul>|
|page|number|Current page number.<br><br>Value range: ≥1.<br>Default value: 1.|
|page_size|number|Page size.<br><br>Value range: 1-50.<br>Default value: 10.|
```

### Example

```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/bc/asset/advertiser/assigned/?bc_id={{bc_id}}&asset_type=TT_ACCOUNT&asset_id={{asset_id}}&page=1&page_size=50' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

## Response

```xtable
|Field{30%}|Type{15%}|Description{55%}|
|---|---|---|
|code|number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://business-api.tiktok.com/portal/docs?id=1737172488964097).|
|message|string|Response message. For details, see [Appendix - Return Codes](https://business-api.tiktok.com/portal/docs?id=1737172488964097).|
|request_id|string|The log ID of a request, which uniquely identifies the request.|
|data|object|Returned data.|
#|list|object[]|The list of ad accounts linked to the TikTok account (`asset_id`) in the Business Center.|
##|advertiser_id|string|The ID of the ad account.|
##|advertiser_name|string|The name of the ad account.|
#|page_info|object|Pagination information.|
##|page|number|Current page number.|
##|page_size|number|Page size.|
##|total_number|number|Total number of results.|
##|total_page|number|Total pages of results.|
```

### Example

```xcodeblock
(code Success-Response http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "list": [
            {
                "advertiser_id": "{{advertiser_id}}",
                "advertiser_name": "{{advertiser_name}}"
            },
            {
                "advertiser_id": "{{advertiser_id}}",
                "advertiser_name": "{{advertiser_name}}"
            }
        ],
        "page_info": {
            "page": 1,
            "page_size": 50,
            "total_number": 2,
            "total_page": 1
        }
    }
}
(/code)
```
