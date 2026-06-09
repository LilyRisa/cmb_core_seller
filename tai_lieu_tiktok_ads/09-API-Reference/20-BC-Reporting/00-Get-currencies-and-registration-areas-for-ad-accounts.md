---
title: "Get currencies and registration areas for ad accounts"
doc_id: 1775752357139457
path: "API Reference / BC Reporting / Get currencies and registration areas for ad accounts"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get currencies and registration areas for ad accounts

Use this endpoint to retrieve the currencies and places of registration for ad accounts within a Business Center.

You can pass the returned currencies and places of registration to the filter fields `registered_area` and `currency_of_account` in a [Business Center report](https://business-api.tiktok.com/portal/docs?id=1775747484045313).


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/bc/advertiser/attribute/

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
| bc_id {Required} | string | Business Center ID. <br><br>To get a list of Business Centers that you have access to, use [/bc/get/](https://business-api.tiktok.com/portal/docs?id=1737115687501826).|
```
### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/bc/advertiser/attribute/?bc_id={{bc_id}}' \
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
#| currencies | string[] | The location codes for the places of registration for ad accounts in the Business Center.<br><br> Example: `["USD"]` |
#| region_codes | string[] | Currency codes of ad accounts in the Business Center. <br><br>Example: `["US", "BR"]` |
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
        "region_codes": [
            "AU",
            "AE",
            "AF",
            "JP",
            "BR",
            "US",
            "AD",
            "FR"
        ],
        "currencies": [
            "USD"
        ]
    }
}
(/code)
```
