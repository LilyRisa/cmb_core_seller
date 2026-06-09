---
title: "Get negative keywords"
doc_id: 1775104887052289
path: "API Reference / Negative Keywords / Get negative keywords"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get negative keywords

Use this endpoint to get the list of negative keywords for Search Ads.



## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/search_ad/negative_keyword/get/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| advertiser_id {Required} | string | Advertiser ID. |
| object_type {Required} | string | Object type. Use this field to specify whether you want to get the list of negative keywords for a campaign or ad group. <br><br> Enum values: <ul><li>`CAMPAIGN`: campaign.</li><li>`ADGROUP`: ad group.</li></ul> |
| object_id {Required} | string | Object ID. Use this field to pass in a campaign ID or ad group ID that you want to get the list of negative keywords for. <br><br> Specify this field according to `object_type`: <ul><li> If `object_type` is `CAMPAIGN`, assign a campaign ID to this field.</li><li> If `object_type` is `ADGROUP`, assign an ad group ID to this field.</li></ul>|
| page | number | The number of pages you want to include the results in. <br><br>Default value: 1. <br><br>Value range: ≥ 1 |
| page_size | number | Page size. <br><br> Default value: 10. <br><br>Value range: [1,50]. |
```

### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/search_ad/negative_keyword/get/?advertiser_id={{advertiser_id}}&object_type=CAMPAIGN&object_id={{object_id}}&page=1&page_size=50' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

## Response

``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|-|-|-|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
| data | object | Returned data. <br><br>If no negative word is found, an empty list will be returned. |
#| keywords | object[] | Information about negative words. <br><br>Max size: 1,000. |
##| keyword_id | string | ID of the negative keyword. |
##| name | string | Name of the negative keyword. |
##| match_type | string | Match type for the negative keyword. <br><br>Enum values: <ul><li>`PRECISE_WORD`: exact match.</li><li>`PHRASE_WORD`: phrase match.</li><li>`BROAD_WORD`: broad match.</li></ul>|
#| page_info | object | Pagination information. |
##| page | number | Current page number.  |
##| page_size | number | Page size. |
##| total_number | number | Total number of results. |
##| total_page | number | Total pages of results. |
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
        "keywords": [
            {
                "match_type": "BROAD_WORD",
                "name": "{{name}}",
                "keyword_id": "{{keyword_id}}"
            },
            {
                "match_type": "BROAD_WORD",
                "name": "{{name}}",
                "keyword_id": "{{keyword_id}}"
            }
        ],
        "page_info": {
            "total_number": 2,
            "page": 1,
            "total_page": 1,
            "page_size": 50
        }
    }
}
(/code)
```
