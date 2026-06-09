---
title: "Get the Brand Profiles for your TTO account"
doc_id: 1830335437705217
path: "API Reference / TikTok One / Get the Brand Profiles for your TTO account"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the Brand Profiles for your TTO account

Use this endpoint to retrieve the existing Brand Profiles for your TikTok One (TTO) Creator Marketplace account.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/tto/tcm/brand/profile/get/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Access token authorized by a TTO Creator Marketplace account. <br>For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1825117875361793).|
```


**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| tto_tcm_account_id {Required} | string | The ID of the TTO Creator Marketplace account.<br><br>To obtain the IDs of the accounts that you can access via `Access-Token`, use [/tto/oauth2/tcm/](https://business-api.tiktok.com/portal/docs?id=1815693478150146). |
| brand_profile_ids | string[] | The IDs of existing Brand Profiles to filter the results by.<br><br>Max size: 20. |
| page | number | Current page number.<br><br>Value range: ≥ 1.<br>Default value: 1. |
| page_size | number | Page size.<br><br>Value range: 1-100.<br>Default value: 10. |
```

### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/tto/tcm/brand/profile/get/?tto_tcm_account_id={{tto_tcm_account_id}}&page=1&page_size=100' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

## Response

``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data.|
#|brands | object[] | List of Brand Profiles. |
##|brand_profile_id | string | The ID of the Brand Profile. |
##|brand_name | string | Brand name. |
##|brand_industry | string | The ID of the brand industry. |
##|brand_website | string | Brand website link. |
##|logo_url | string | The URL of the brand logo image for your TTO Creator Marketplace account.<br><br><p><span style="color:darkred"><b>Note</b></span>: This URL is system-generated for the logo image and is different from the URL specified through `logo_url` in the request for Brand Profile creation. |
#| page_info | object | Page information. |
##|page | number | Current page number. |
##|page_size | number | Page size. |
##|total_number | number | Total number of results. |
##|total_page | number | Total number of pages. |
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
        "brands": [
            {
                "brand_industry_id": "10102102000",
                "brand_name": "{{brand_name}}",
                "brand_profile_id": "{{brand_profile_id}}",
                "brand_website": "{{brand_website}}",
                "logo_url": null
            },
            {
                "brand_industry_id": "17103000000",
                "brand_name": "{{brand_name}}",
                "brand_profile_id": "{{brand_profile_id}}",
                "brand_website": "{{brand_website}}",
                "logo_url": "{{logo_url}}"
            }
        ],
        "page_info": {
            "page": 1,
            "page_size": 100,
            "total_number": 2,
            "total_page": 1
        }
    }
}
(/code)
```
