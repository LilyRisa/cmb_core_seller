---
title: "Get the verification results of a URL"
doc_id: 1774487686007810
path: "API Reference / Tools / Get the verification results of a URL"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the verification results of a URL

Use this endpoint to check whether a URL is a custom URL scheme, Apple’s universal link, or Android App Link, and whether the URL is valid.


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/tool/url_validate/

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
| url {Required} | string | The URL that you want to get verification results for. |
```
### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/tool/url_validate/?advertiser_id={{advertiser_id}}&url={{url}}' \
--header 'Access-Token: {{Access-Token}}' \
(/code)
```
## Response

``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|-|-|-|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data.|
#| landing_page_url_info | object | Information about the URL. |
##| validate_info | object | Information about the verification results for the URL. |
###| is_scheme_link | boolean | Whether the URL is a custom URL scheme. |
###| is_valid_url | boolean | Whether the URL is a valid URL (Apple’s universal link, Android App Link, or custom URL scheme). |
###| is_valid_scheme | boolean | Whether the URL is a valid custom URL scheme. |
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
        "url_info": {
            "validate_info": {
                "is_valid_scheme": true,
                "is_scheme_link": true,
                "is_valid_url": true
            }
        }
    }
}
(/code)
```
