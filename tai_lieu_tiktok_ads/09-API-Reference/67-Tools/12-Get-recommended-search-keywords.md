---
title: "Get recommended search keywords"
doc_id: 1838630375757825
path: "API Reference / Tools / Get recommended search keywords"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get recommended search keywords

Use this endpoint to obtain a list of recommended search keywords with predicted monthly searches for Search Ads Campaigns.


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/tool/search_keyword/recommend/

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
|search_queries {+Conditional}|string[]|Required when you want to obtain suggested search keywords based on search queries.<br>You can only specify one among `search_queries`, `ad_ids` and `landing_page_urls`.<br><br>A list of search queries.<br><br>You can specify products, services, or brands as search queries.<br><br>Max size: 5.<br>Length limit for each query: 80 characters.<br><br><ul><li>Only English words are supported.</li><li>Emojis and special symbols such as # are not supported.</li></ul> |
| ad_ids {+Conditional} | string[] | Required when you want to obtain suggested search keywords based on specific ads.<br>You can only specify one among `search_queries`, `ad_ids` and `landing_page_urls`.<br><br>A list of ad IDs.<br><br>Max size: 1.<br><br><p><span style="color:darkred"><b>Note</b></span>: Obtaining suggested search keywords based on specific ads is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</p> |
| landing_page_urls {+Conditional} | string[] | Required when you want to obtain suggested search keywords based on URLs.<br>You can only specify one among `search_queries`, `ad_ids` and `landing_page_urls`.<br><br>A list of landing page URLs.<br><br>Max size: 5.<br>No length limit for each landing page URL.<br><br><p><span style="color:darkred"><b>Note</b></span>: Obtaining suggested search keywords based on URLs is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</p> |
|regions {Required}|string[]|A list of country or region codes.<br><br>Currently, the only supported value is `US`.|
|order_field|string|Sorting logic.<br><br>Enum values:<ul><li>`RELEVANCE`: Sort the results by relevance.</li><li>`MONTHLY_SEARCHES`: Sort the results by the response parameter `monthly_searches`.</li></ul><br>Default value when `search_queries` is specified: `RELEVANCE`.<br><br><p><span style="color:darkred"><b>Note</b></span>: When `ad_ids` or `landing_page_urls` is specified, you can only set this field to `MONTHLY_SEARCHES`.</p> |
|order_type|string|Valid only when `order_field` is `MONTHLY_SEARCHES`.<br><br>Sorting order.<br><br>Enum values:<ul><li>`ASC`: ascending.</li><li>`DESC`: descending.</li></ul><br>Default value: `DESC`.<br><br><p><span style="color:darkred"><b>Note</b></span>: When `ad_ids` or `landing_page_urls` is specified, you can only set this field to `DESC`.</p> |
|total_size|number|The total number of recommended keywords to return.<br><br>Value range: 1-1,000.<br>Default value: 1,000.|
|page|number|Current page number.<br><br>Value range: ≥1.<br>Default value: 1.|
|page_size|number|Page size.<br><br>Value range: 1-1,000.<br>Default value: 50.|
```

### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/tool/search_keyword/recommend/?advertiser_id={{advertiser_id}}&search_queries=["TikTok"]&regions=["US"]&order_field=MONTHLY_SEARCHES&order_type=DESC' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```
## Response

``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://business-api.tiktok.com/portal/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://business-api.tiktok.com/portal/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data|object|Returned data.|
#|recommended_keywords|object[]|A list of recommended search keywords.|
##|recommended_keyword|string|Recommended search keyword.|
##|monthly_searches|integer|Predicted monthly searches.<br>Prediction is based on historical data for your reference.|
#|page_info|object|Pagination information.|
##|page|number|Current page number.|
##|page_size|number|Page size.|
##|total_number|number|Total number of results.|
##|total_page|number|Total pages of results.|
```

### Example
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "page_info": {
            "page": 1,
            "page_size": 50,
            "total_number": 897,
            "total_page": 18
        },
        "recommended_keywords": [
            {
                "monthly_searches": 22063,
                "recommended_keyword": "messytiktok"
            },
            ...
            {
                "monthly_searches": 9747,
                "recommended_keyword": "funny relatable tiktoks"
            }
        ]
    }
}
(/code)
```
