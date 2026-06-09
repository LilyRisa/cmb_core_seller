---
title: "Get the TikTok Minis within an ad account"
doc_id: 1853450329535490
path: "API Reference / Tools / Get the TikTok Minis within an ad account"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the TikTok Minis within an ad account

Use this endpoint to retrieve the list of TikTok Minis within an ad account.

To learn more about the campaign type where you can promote a TikTok Minis, see [Create an Upgraded Smart+ Minis Campaign](https://business-api.tiktok.com/portal/docs?id=1853377811982657).


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/minis/get/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1738373164380162).|
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
|advertiser_id {Required}|string|Advertiser ID.|
|page|number|Current page number.<br><br>Default value: 1. Value range: ≥ 1.|
|page_size|number|Page size.<br><br>Default value: 10. Value range: 1-50.|
```

### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/minis/get/?advertiser_id={{advertiser_id}}&page=1&page_size=50' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```
## Response

``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|---|---|---|
|code|number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://business-api.tiktok.com/portal/docs?id=1737172488964097).|
|message|string|Response message. For details, see [Appendix - Return Codes](https://business-api.tiktok.com/portal/docs?id=1737172488964097).|
|request_id|string|The log ID of a request, which uniquely identifies the request.|
|data|object|Returned data.|
#|list|object[]|A list of TikTok Minis within an ad account.|
##|minis_id|string|The ID of the TikTok Minis.|
##|minis_name|string|The name of the TikTok Minis.|
##|minis_icon_url|string|The URL to the app icon image of the TikTok Minis.|
##|minis_status|string|The status of the TikTok Minis.<br><br>Enum values:<ul><li>`ACTIVE`: active. You can use the TikTok Minis to create ads.</li><li>`INACTIVE`: inactive. You cannot use the TikTok Minis to create ads.</li></ul>|
##|minis_type|string|The type of the TikTok Minis.<br><br>Enum values:<ul><li>`MINI_SERIES`: mini series.</li><li>`MINI_GAME`: mini game.</li></ul>|
##|region_codes|string[]|The region codes of the supported country or region for the TikTok Minis.<br><br>Example: `["ID", "US"]`.|
#|page_info|object|Pagination information.|
##|page|number|Current page number.|
##|page_size|number|Paging size.|
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
                "minis_icon_url": "{{minis_icon_url}}",
                "minis_id": "{{minis_id}}",
                "minis_name": "{{minis_name}}",
                "minis_status": "ACTIVE",
                "minis_type": "MINI_GAME",
                "region_codes": [
                    "JP",
                    "US",
                    "ID"
                ]
            },
            {
                "minis_icon_url": "{{minis_icon_url}}",
                "minis_id": "{{minis_id}}",
                "minis_name": "{{minis_name}}",
                "minis_status": "ACTIVE",
                "minis_type": "MINI_GAME",
                "region_codes": [
                    "JP",
                    "US",
                    "ID"
                ]
            },
            {
                "minis_icon_url": "{{minis_icon_url}}",
                "minis_id": "{{minis_id}}",
                "minis_name": "{{minis_name}}",
                "minis_status": "ACTIVE",
                "minis_type": "MINI_SERIES",
                "region_codes": [
                    "JP"
                ]
            },
            {
                "minis_icon_url": "{{minis_icon_url}}",
                "minis_id": "{{minis_id_4}}",
                "minis_name": "{{minis_name_4}}",
                "minis_status": "ACTIVE",
                "minis_type": "MINI_SERIES",
                "region_codes": [
                    "US"
                ]
            }
        ],
        "page_info": {
            "page": 1,
            "page_size": 50,
            "total_number": 4,
            "total_page": 1
        }
    }
}
(/code)
```

