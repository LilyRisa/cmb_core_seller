---
title: "Get Terms"
doc_id: 1737191909315585
path: "API Reference / Terms / Get Terms"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get Terms

Use this endpoint to get the agreement for the Lead Generation Ads feature. You must specify the feature that you want to request the agreement for using the `term_type` field. 

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Change{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/term/get/|/v1.3/term/get/|
|Request parameter data type |`advertiser_id`: number|`advertiser_id`: string|
```

## Request
**Endpoint** https://business-api.tiktok.com/open_api/v1.3/term/get/

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
|advertiser_id {Required}|string|Advertiser ID|
|lang |string|The language of the agreement. Enum values: `EN`,`JA`,`ZH`<br>Default value: `EN`.|
|term_type {Required}|string|Type of agreement. Enum values: `LeadAds`.|
```

### Example
```xcodeblock
(code curl http)
curl --get -H "Access-Token:xxx" \
--data-urlencode "advertiser_id=< advertiser_id >" \
--data-urlencode "lang=< lang >" \
--data-urlencode "term_type=< term_type >" \
https://business-api.tiktok.com/open_api/v1.3/term/get/
(/code)
```

## Response
```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Return code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|message |string|Return messages. For details, see [Appendix-Return information](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|data |object|Returned data. |
#|terms |string[]|The content of the agreement.|
|request_id |string|The log id of a request, which uniquely identifies the request. |
```

### Example
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {
        "terms": [
            "Please refer to the following link,",
            "https://ads.tiktok.com/i18n/official/policy/lead-gen-terms"
        ]
    },
    "request_id": "2021061803333901023604122050CA85D1"
}
(/code);
```
